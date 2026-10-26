<?php

use App\Actions\Billing\PayFromBalance;
use App\Actions\Billing\RefundOrder;
use App\Enums\OrderStatus;
use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Enums\PricePlanProduct;
use App\Enums\Role;
use App\Enums\WalletEntryType;
use App\Exceptions\PurchaseNotAllowed;
use App\Filament\Pages\RevenueReportPage;
use App\Filament\Resources\Orders\Pages\ManageOrders;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PricePlan;
use App\Models\Property;
use App\Models\User;
use App\Notifications\InvoiceIssued;
use App\Notifications\OrderRefunded;
use App\Services\Billing\RevenueReport;
use App\Services\Billing\Wallet;
use App\Services\Payments\PaymentGatewayManager;
use Database\Seeders\PricePlanSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(PricePlanSeeder::class);
    Notification::fake();
    [$this->admin] = staffMember(Role::SuperAdmin);
    $this->user = User::factory()->create();
    $this->listing = Property::factory()->published()->for($this->user)->create();
});

function paidByCard(User $user, Property $listing, string $code = 'vip-7'): Order
{
    $order = Order::factory()->for($user)->forPlan(PricePlan::query()->where('code', $code)->firstOrFail(), $listing)->create();
    [, $bankId] = fakeBankPayment($order);
    postFakeCallback($bankId)->assertOk();

    return $order->fresh();
}

it('refunds a card payment through the bank and stops the promotion', function () {
    $order = paidByCard($this->user, $this->listing);
    expect($this->listing->fresh()->promotion_level)->toBe(1);

    app(RefundOrder::class)->handle($order, 10, $this->admin, 'Customer request');

    expect($order->fresh())->status->toBe(OrderStatus::Refunded)->refunded_amount->toBe('10.00')
        ->and(Payment::query()->sole()->status)->toBe(PaymentStatus::Refunded)
        ->and($this->listing->fresh()->promotion_level)->toBe(0);

    Notification::assertSentTo($this->user, OrderRefunded::class);
});

it('keeps the promotion on a partial refund and refuses more than was paid', function () {
    $order = paidByCard($this->user, $this->listing);

    app(RefundOrder::class)->handle($order, 4, $this->admin);

    expect($order->fresh()->status)->toBe(OrderStatus::PartiallyRefunded)
        ->and(Payment::query()->sole()->status)->toBe(PaymentStatus::PartiallyRefunded)
        ->and($this->listing->fresh()->promotion_level)->toBe(1);

    expect(fn () => app(RefundOrder::class)->handle($order->fresh(), 6.01, $this->admin))->toThrow(PurchaseNotAllowed::class);
});

it('returns balance purchases to the wallet and limits top-up refunds to the balance left', function () {
    app(Wallet::class)->credit($this->user, 10, WalletEntryType::Adjustment);
    $order = Order::factory()->for($this->user)->forPlan(PricePlan::query()->where('code', 'vip-7')->firstOrFail(), $this->listing)->create();
    app(PayFromBalance::class)->handle($order);
    expect((float) $this->user->fresh()->balance)->toBe(0.0);

    app(RefundOrder::class)->handle($order->fresh(), 10, $this->admin);
    expect((float) $this->user->fresh()->balance)->toBe(10.0);

    $topUp = Order::factory()->for($this->user)->create(['product' => PricePlanProduct::WalletTopUp, 'amount' => 30]);
    [, $bankId] = fakeBankPayment($topUp);
    postFakeCallback($bankId);
    app(Wallet::class)->debit($this->user, 35, WalletEntryType::Purchase);

    expect(fn () => app(RefundOrder::class)->handle($topUp->fresh(), 30, $this->admin))->toThrow(PurchaseNotAllowed::class);

    app(RefundOrder::class)->handle($topUp->fresh(), 5, $this->admin);
    expect((float) $this->user->fresh()->balance)->toBe(0.0);
});

it('refunds from the admin orders page and issues invoices', function () {
    $this->actingAs($this->admin);
    Filament::setServingStatus();
    $order = paidByCard($this->user, $this->listing);

    Livewire::test(ManageOrders::class)
        ->assertCanSeeTableRecords([$order])
        ->callAction(TestAction::make('refund')->table($order), data: ['amount' => 10, 'reason' => 'Duplicate'])
        ->assertHasNoActionErrors();

    expect($order->fresh()->status)->toBe(OrderStatus::Refunded);

    Livewire::test(ManageOrders::class)
        ->callAction('issueInvoice', data: ['user_id' => $this->user->id, 'amount' => 150, 'description' => ['ka' => 'ბანერი ზაფხულში']])
        ->assertHasNoActionErrors();

    $invoice = Order::query()->whereNotNull('issued_by')->sole();
    expect($invoice)->status->toBe(OrderStatus::Pending)->product->toBe(PricePlanProduct::Custom)->amount->toBe('150.00');
    Notification::assertSentTo($this->user, InvoiceIssued::class);
});

it('adjusts a user balance from the admin', function () {
    $this->actingAs($this->admin);
    Filament::setServingStatus();

    Livewire::test(ListUsers::class)
        ->callAction(TestAction::make('adjustBalance')->table($this->user), data: ['amount' => 25, 'note' => 'Compensation'])
        ->assertHasNoActionErrors();

    expect((float) $this->user->fresh()->balance)->toBe(25.0)
        ->and($this->user->walletTransactions()->sole()->created_by)->toBe($this->admin->id);
});

it('reports revenue by product and exports it as CSV', function () {
    paidByCard($this->user, $this->listing, 'vip-7');
    paidByCard($this->user, Property::factory()->published()->for($this->user)->create(), 'super-vip-3');
    $topUp = Order::factory()->for($this->user)->create(['product' => PricePlanProduct::WalletTopUp, 'amount' => 20]);
    [, $bankId] = fakeBankPayment($topUp);
    postFakeCallback($bankId);

    $report = app(RevenueReport::class)->build(now()->startOfMonth(), now());

    expect($report['totals'])->toMatchArray(['orders' => 2, 'card' => 20.0, 'net' => 20.0])
        ->and($report['card_received'])->toBe(40.0)
        ->and(collect($report['rows'])->firstWhere('product', 'wallet_top_up')['card'])->toBe(20.0);

    $csv = app(RevenueReport::class)->csv(now()->startOfMonth(), now());
    expect($csv)->toContain(PricePlanProduct::SuperVip->label())->toContain('20.00');

    $this->actingAs($this->admin);
    Filament::setServingStatus();
    Livewire::test(RevenueReportPage::class)->assertSee(PricePlanProduct::Vip->label())->callAction('export')->assertFileDownloaded();
});

it('re-checks payments whose callback never came and closes abandoned orders', function () {
    $order = Order::factory()->for($this->user)->forPlan(PricePlan::query()->where('code', 'vip-7')->firstOrFail(), $this->listing)->create();
    [$payment, $bankId] = fakeBankPayment($order);
    $bank = app(PaymentGatewayManager::class)->for(PaymentGateway::Fake);
    $bank->decide($bankId, true); // the bank took the money, but the callback was lost

    $abandoned = Order::factory()->for($this->user)->create(['product' => PricePlanProduct::WalletTopUp, 'created_at' => now()->subDays(2)]);

    $this->travel(10)->minutes();
    $this->artisan('payments:reconcile')->assertSuccessful();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($order->fresh()->status)->toBe(OrderStatus::Fulfilled)
        ->and($abandoned->fresh()->status)->toBe(OrderStatus::Cancelled);
});
