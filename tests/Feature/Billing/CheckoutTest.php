<?php

use App\Actions\Billing\PayFromBalance;
use App\Enums\ListingStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\PricePlanProduct;
use App\Enums\WalletEntryType;
use App\Livewire\Billing\Analytics;
use App\Livewire\Billing\Billing;
use App\Livewire\Billing\PromoteListing;
use App\Models\Banner;
use App\Models\BusinessProfile;
use App\Models\BusinessSubscription;
use App\Models\Hotel;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PricePlan;
use App\Models\Promotion;
use App\Models\Property;
use App\Models\User;
use App\Services\Billing\InvoiceDocument;
use App\Services\Billing\OrderBuilder;
use App\Services\Billing\Wallet;
use App\Services\Listings\ListingLimits;
use App\Services\Settings;
use Database\Seeders\PricePlanSeeder;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed([RoleSeeder::class, PricePlanSeeder::class]);
    $this->user = User::factory()->create();
    $this->listing = Property::factory()->published()->for($this->user)->create();
});

function plan(string $code): PricePlan
{
    return PricePlan::query()->where('code', $code)->firstOrFail();
}

it('pays a VIP by card through the bank page and comes back to the listing', function () {
    $component = Livewire::actingAs($this->user)
        ->test(PromoteListing::class, ['type' => 'property', 'id' => $this->listing->id])
        ->call('choose', plan('vip-7')->id)
        ->set('method', 'card')
        ->call('buy');

    $payment = Payment::query()->sole();
    $component->assertRedirect($payment->redirect_url);

    // The fake bank page, then "approve".
    $this->get($payment->redirect_url)->assertOk()->assertSee(__('billing.fake_bank.approve'));
    $this->post($payment->redirect_url, ['approve' => '1'])->assertRedirect(localized_route('payments.return', ['payment' => $payment->id]));

    $this->get(localized_route('payments.return', ['payment' => $payment->id]))
        ->assertRedirect(localized_route('account.promote', ['type' => 'property', 'id' => $this->listing->id]))
        ->assertSessionHas('status', __('billing.return.success'));

    expect($this->listing->fresh())
        ->promotion_level->toBe(1)
        ->promoted_until->toBeInstanceOf(DateTimeInterface::class)
        ->and($this->listing->fresh()->promoted_until->isSameDay(now()->addDays(7)))->toBeTrue()
        ->and(Order::query()->sole()->status)->toBe(OrderStatus::Fulfilled);
});

it('shows the error when the bank declines, without activating anything', function () {
    Livewire::actingAs($this->user)
        ->test(PromoteListing::class, ['type' => 'property', 'id' => $this->listing->id])
        ->call('choose', plan('super-vip-3')->id)
        ->call('buy');

    $payment = Payment::query()->sole();
    $this->post($payment->redirect_url, ['approve' => '0']);

    $this->get(localized_route('payments.return', ['payment' => $payment->id]))
        ->assertSessionHas('error', __('billing.return.failed'));

    expect($payment->fresh()->status)->toBe(PaymentStatus::Failed)
        ->and($this->listing->fresh()->promotion_level)->toBe(0);
});

it('does not show another user’s payment return page', function () {
    $order = Order::factory()->for(User::factory())->create();
    [$payment] = fakeBankPayment($order);

    $this->actingAs($this->user)->get(localized_route('payments.return', ['payment' => $payment->id]))->assertNotFound();
});

it('pays from the balance at once and refuses when the balance is too low', function () {
    app(Wallet::class)->credit($this->user, 12, WalletEntryType::Adjustment);

    Livewire::actingAs($this->user)
        ->test(PromoteListing::class, ['type' => 'property', 'id' => $this->listing->id])
        ->call('choose', plan('super-vip-3')->id)
        ->set('method', 'balance')
        ->call('buy')
        ->assertDispatched('toast', type: 'success');

    expect((float) $this->user->fresh()->balance)->toBe(2.0)
        ->and($this->listing->fresh()->promotion_level)->toBe(2)
        ->and(Payment::query()->count())->toBe(0);

    Livewire::actingAs($this->user->fresh())
        ->test(PromoteListing::class, ['type' => 'property', 'id' => $this->listing->id])
        ->call('choose', plan('vip-7')->id)
        ->set('method', 'balance')
        ->call('buy')
        ->assertDispatched('toast', message: __('billing.errors.insufficient_balance'), type: 'error');

    expect((float) $this->user->fresh()->balance)->toBe(2.0)
        ->and(Order::query()->where('status', OrderStatus::Fulfilled)->count())->toBe(1);
});

it('bumps a listing to the top and starts a daily auto-bump', function () {
    app(Wallet::class)->credit($this->user, 20, WalletEntryType::Adjustment);

    Livewire::actingAs($this->user)
        ->test(PromoteListing::class, ['type' => 'property', 'id' => $this->listing->id])
        ->call('choose', plan('auto-bump-7')->id)
        ->set('method', 'balance')
        ->call('buy');

    $auto = Promotion::query()->where('type', 'auto_bump')->sole();

    expect($this->listing->fresh()->bumped_at)->not->toBeNull()
        ->and(Promotion::query()->where('type', 'bump')->count())->toBe(1)
        ->and($auto->next_run_at->isSameHour(now()->addDay()))->toBeTrue();
});

it('promotes only the owner’s live listings and sells homepage slots only for hotels', function () {
    $paused = Property::factory()->for($this->user)->create(['status' => ListingStatus::Paused]);
    $foreign = Property::factory()->published()->create();
    $hotel = Hotel::factory()->published()->for($this->user)->create();

    $this->actingAs($this->user)->get("/account/promote/property/{$foreign->id}")->assertNotFound();
    $this->actingAs($this->user)->get("/account/promote/property/{$paused->id}")->assertOk()->assertSee(__('billing.promote.not_live'));

    Livewire::actingAs($this->user)
        ->test(PromoteListing::class, ['type' => 'property', 'id' => $paused->id])
        ->call('choose', plan('vip-1')->id)
        ->call('buy')
        ->assertDispatched('toast', type: 'error');

    Livewire::actingAs($this->user)
        ->test(PromoteListing::class, ['type' => 'property', 'id' => $this->listing->id])
        ->assertViewHas('groups', fn (array $groups) => ! in_array('featured_hotel', array_column($groups, 'product'), true));

    Livewire::actingAs($this->user)
        ->test(PromoteListing::class, ['type' => 'hotel', 'id' => $hotel->id])
        ->assertViewHas('groups', fn (array $groups) => in_array('featured_hotel', array_column($groups, 'product'), true));

    expect(Order::query()->count())->toBe(0);
});

it('tops up the balance by card', function () {
    $component = Livewire::actingAs($this->user)->test(Billing::class)
        ->set('amount', '3')
        ->call('topUp')
        ->assertHasErrors('amount')
        ->set('amount', '35')
        ->call('topUp');

    $payment = Payment::query()->sole();
    $component->assertRedirect($payment->redirect_url);
    $this->post($payment->redirect_url, ['approve' => '1']);

    expect((float) $this->user->fresh()->balance)->toBe(35.0);

    $this->actingAs($this->user)->get('/account/billing')
        ->assertOk()
        ->assertSee(__('enums.price_plan_product.wallet_top_up'))
        ->assertSee(Order::query()->sole()->number);
});

it('sells the business package: badge, logo flag, listing limit and analytics', function () {
    $profile = BusinessProfile::factory()->for($this->user)->create();
    app(Settings::class)->put(['listings.free_limit.individual' => 2]);
    app(Wallet::class)->credit($this->user, 120, WalletEntryType::Adjustment);

    Livewire::actingAs($this->user)->test(Analytics::class)->assertViewHas('locked', true);
    expect(app(ListingLimits::class)->remaining($this->user))->toBe(1);

    Livewire::actingAs($this->user)->test(Billing::class)
        ->set('package', plan('business-30')->id)
        ->set('method', 'balance')
        ->call('buyPackage')
        ->assertDispatched('toast', type: 'success');

    $subscription = BusinessSubscription::query()->sole();
    expect($subscription->listings_limit)->toBe(50)
        ->and($profile->fresh()->hasPackage())->toBeTrue()
        ->and($profile->fresh()->isVerified())->toBeTrue()
        ->and(app(ListingLimits::class)->remaining($this->user->fresh()))->toBe(49);

    Livewire::actingAs($this->user->fresh())->test(Analytics::class)->assertViewHas('locked', false);
    $this->actingAs($this->user->fresh())->get('/account')->assertSee(__('account.menu.analytics'));

    // A second purchase extends the package from its current end.
    app(Wallet::class)->credit($this->user, 99, WalletEntryType::Adjustment);
    Livewire::actingAs($this->user->fresh())->test(Billing::class)
        ->set('package', plan('business-30')->id)
        ->set('method', 'balance')
        ->call('buyPackage');

    expect(BusinessSubscription::query()->max('ends_at'))->toStartWith(now()->addDays(60)->toDateString());
});

it('lets the user pay an invoice issued by staff and activates the banner', function () {
    [$staff] = staffMember();
    $banner = Banner::query()->create(['slot' => 'home_top', 'title' => ['ka' => 'ბანერი'], 'is_active' => false]);
    $order = app(OrderBuilder::class)->invoice($this->user, 200, ['ka' => 'ბანერი მთავარზე'], $staff, plan('banner-30'), $banner);
    app(Wallet::class)->credit($this->user, 200, WalletEntryType::Adjustment);

    Livewire::actingAs($this->user)->test(Billing::class)
        ->assertSee('ბანერი მთავარზე')
        ->call('payInvoice', $order->id, 'balance');

    expect($order->fresh()->status)->toBe(OrderStatus::Fulfilled)
        ->and($banner->fresh()->is_active)->toBeTrue()
        ->and($banner->fresh()->ends_at->isSameDay(now()->addDays(30)))->toBeTrue();
});

it('downloads the invoice only for the owner', function () {
    app(Wallet::class)->credit($this->user, 10, WalletEntryType::Adjustment);
    $order = Order::factory()->for($this->user)->forPlan(plan('vip-7'), $this->listing)->create();
    app(PayFromBalance::class)->handle($order);
    $order->refresh();

    $response = $this->actingAs($this->user)->get(localized_route('account.invoice', ['order' => $order->id]))->assertOk();

    if (app(InvoiceDocument::class)->supportsPdf()) {
        expect($response->headers->get('Content-Type'))->toBe('application/pdf')
            ->and(substr((string) $response->getContent(), 0, 4))->toBe('%PDF');
    } else {
        $response->assertSee($order->number)->assertSee(__('billing.invoice.heading'));
    }

    $this->actingAs(User::factory()->create())->get(localized_route('account.invoice', ['order' => $order->id]))->assertNotFound();
});

it('renders the billing, promote and analytics pages', function () {
    $this->actingAs($this->user)->get('/account/billing')->assertOk()->assertSee(__('billing.wallet.title'));
    $this->actingAs($this->user)->get("/account/promote/property/{$this->listing->id}")->assertOk()->assertSee(PricePlanProduct::SuperVip->label());
    $this->actingAs($this->user)->get('/account/analytics')->assertOk()->assertSee(__('billing.analytics.locked_title'));
    $this->actingAs($this->user)->get('/en/account/billing')->assertOk()->assertSee('Balance');
    $this->actingAs($this->user)->get('/account/listings?type=property')->assertOk()->assertSee(__('billing.promote.button'));
});
