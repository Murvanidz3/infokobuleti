<?php

use App\Enums\ApplyMethod;
use App\Models\BookingRequest;
use App\Models\Conversation;
use App\Models\DailyStat;
use App\Models\Favorite;
use App\Models\Hotel;
use App\Models\Report;
use App\Models\RoomType;
use App\Models\User;
use App\Models\Vacancy;
use App\Models\VacancyApplication;
use App\Notifications\NewInquiry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

it('asks guests to sign in before saving favorites', function () {
    $hotel = Hotel::factory()->create();

    $this->postJson("/favorites/hotel/{$hotel->id}")->assertUnauthorized()->assertJsonStructure(['message']);
});

it('toggles a favorite and counts it for the owner', function () {
    $user = User::factory()->create();
    $hotel = Hotel::factory()->create();

    $this->actingAs($user)->postJson("/en/favorites/hotel/{$hotel->id}")->assertOk()->assertJson(['saved' => true]);
    expect(Favorite::query()->where('user_id', $user->id)->count())->toBe(1)
        ->and(DailyStat::query()->where('statable_id', $hotel->id)->value('favorites'))->toBe(1);

    $this->actingAs($user)->postJson("/en/favorites/hotel/{$hotel->id}")->assertOk()->assertJson(['saved' => false]);
    expect(Favorite::query()->count())->toBe(0);
});

it('cannot favorite hidden items or unknown types', function () {
    $user = User::factory()->create();
    $draft = Hotel::factory()->draft()->create();

    $this->actingAs($user)->postJson("/favorites/hotel/{$draft->id}")->assertNotFound();
    $this->actingAs($user)->postJson('/favorites/user/1')->assertNotFound();
});

it('reveals the phone with messenger links, counts it and rate-limits', function () {
    $hotel = Hotel::factory()->create(['contact_phone' => '+995599887766']);

    $this->postJson("/phone/hotel/{$hotel->id}")
        ->assertOk()
        ->assertJson([
            'display' => '+995 599 88 77 66',
            'tel' => 'tel:+995599887766',
            'whatsapp' => 'https://wa.me/995599887766',
            'viber' => 'viber://chat?number=%2B995599887766',
        ]);

    expect(DailyStat::query()->where('statable_id', $hotel->id)->value('phone_reveals'))->toBe(1);

    foreach (range(2, 10) as $attempt) {
        $this->postJson("/phone/hotel/{$hotel->id}")->assertOk();
    }

    $this->postJson("/phone/hotel/{$hotel->id}")->assertTooManyRequests();
});

it('gives landline numbers without messenger links', function () {
    $hotel = Hotel::factory()->create(['contact_phone' => '+995422290000']);

    $this->postJson("/phone/hotel/{$hotel->id}")->assertOk()->assertJson(['tel' => 'tel:+995422290000', 'whatsapp' => null, 'viber' => null]);
});

it('lets signed-in visitors report an item once', function () {
    $hotel = Hotel::factory()->create();
    $user = User::factory()->create();

    $this->post("/report/hotel/{$hotel->id}", ['reason' => 'fraud'])->assertRedirect();
    expect(Report::query()->count())->toBe(0);

    $this->actingAs($user)->from('/x')->post("/report/hotel/{$hotel->id}", ['reason' => 'fraud', 'comment' => 'Asks for a prepayment'])->assertRedirect('/x')->assertSessionHas('toast');
    $this->actingAs($user)->post("/report/hotel/{$hotel->id}", ['reason' => 'fraud']);
    $this->actingAs($user)->post("/report/hotel/{$hotel->id}", ['reason' => 'nonsense'])->assertSessionHasErrorsIn('report', 'reason');

    expect(Report::query()->count())->toBe(1)
        ->and(Report::query()->first()->comment)->toBe('Asks for a prepayment');
});

it('starts one conversation per item and notifies the owner', function () {
    Notification::fake();
    $hotel = Hotel::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->post("/message/hotel/{$hotel->id}", ['body' => 'Is the room free in July?'])->assertSessionHas('toast');
    $this->actingAs($user)->post("/message/hotel/{$hotel->id}", ['body' => 'And in August?']);

    $conversation = Conversation::query()->sole();
    expect($conversation->messages()->count())->toBe(2)
        ->and($conversation->participants()->pluck('users.id')->sort()->values()->all())->toBe(collect([$user->id, $hotel->user_id])->sort()->values()->all());

    Notification::assertSentTo(User::query()->find($hotel->user_id), NewInquiry::class, fn (NewInquiry $notification) => $notification->kind === 'message');
});

it('does not let owners message themselves', function () {
    $hotel = Hotel::factory()->create();

    $this->actingAs(User::query()->find($hotel->user_id))->post("/message/hotel/{$hotel->id}", ['body' => 'Hello me'])->assertSessionHas('toast');

    expect(Conversation::query()->count())->toBe(0);
});

it('accepts a booking request from a guest and notifies the hotel', function () {
    Notification::fake();
    $hotel = Hotel::factory()->create();
    $room = RoomType::factory()->for($hotel)->create();

    $this->post(localized_route('hotels.booking', [$hotel]), [
        'room_type_id' => $room->id,
        'check_in' => now()->addDays(5)->toDateString(),
        'check_out' => now()->addDays(8)->toDateString(),
        'adults' => 2,
        'children' => 1,
        'contact_name' => 'Nino',
        'contact_phone' => '+995 599 11 22 33',
        'message' => 'Late arrival',
    ])->assertRedirect(localized_route('hotels.show', [$hotel]).'#booking');

    $booking = BookingRequest::query()->sole();
    expect($booking->hotel_id)->toBe($hotel->id)
        ->and($booking->contact_phone)->toBe('+995599112233')
        ->and($booking->room_type_id)->toBe($room->id)
        ->and($booking->children)->toBe(1);

    Notification::assertSentTo(User::query()->find($hotel->user_id), NewInquiry::class, fn (NewInquiry $notification) => $notification->kind === 'booking');
});

it('validates booking requests', function () {
    $hotel = Hotel::factory()->create();
    $otherRoom = RoomType::factory()->create();

    $this->post(localized_route('hotels.booking', [$hotel]), [
        'room_type_id' => $otherRoom->id,
        'check_in' => now()->subDay()->toDateString(),
        'check_out' => now()->subDays(2)->toDateString(),
        'adults' => 0,
        'contact_phone' => 'call me',
    ])->assertSessionHasErrorsIn('booking', ['room_type_id', 'check_in', 'check_out', 'adults', 'contact_name', 'contact_phone']);

    $this->post(localized_route('hotels.booking', [$hotel]), [
        'check_in' => now()->addDay()->toDateString(), 'check_out' => now()->addDays(2)->toDateString(), 'adults' => 2,
        'contact_name' => 'Bot', 'contact_phone' => '555 12 34 56', 'cf-turnstile-response' => 'fail',
    ])->assertSessionHasErrorsIn('booking', ['cf-turnstile-response']);

    expect(BookingRequest::query()->count())->toBe(0);
});

it('takes job applications with a CV, once per person', function () {
    Notification::fake();
    Storage::fake('local');
    $vacancy = Vacancy::factory()->create(['apply_method' => ApplyMethod::Site]);
    $user = User::factory()->create();

    $this->post(localized_route('jobs.apply', [$vacancy]), ['contact_phone' => '599 11 22 33'])->assertRedirect(localized_route('login'));

    $this->actingAs($user)->post(localized_route('jobs.apply', [$vacancy]), [
        'contact_phone' => '599 11 22 33',
        'cover_note' => 'Three seasons as a waiter.',
        'cv' => UploadedFile::fake()->createWithContent('cv.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n"),
    ])->assertRedirect(localized_route('jobs.show', [$vacancy]).'#apply');

    $application = VacancyApplication::query()->sole();
    expect($application->getFirstMedia('cv'))->not->toBeNull()
        ->and($application->getFirstMedia('cv')->disk)->toBe('local');

    $this->actingAs($user)->post(localized_route('jobs.apply', [$vacancy]), ['contact_phone' => '599 11 22 33'])->assertSessionHas('toast');
    expect(VacancyApplication::query()->count())->toBe(1);

    Notification::assertSentTo(User::query()->find($vacancy->user_id), NewInquiry::class, fn (NewInquiry $notification) => $notification->kind === 'application');
});

it('rejects CVs that are not documents', function () {
    $vacancy = Vacancy::factory()->create(['apply_method' => ApplyMethod::Site]);

    $this->actingAs(User::factory()->create())->post(localized_route('jobs.apply', [$vacancy]), [
        'contact_phone' => '599 11 22 33',
        'cv' => UploadedFile::fake()->create('cv.exe', 10, 'application/x-msdownload'),
    ])->assertSessionHasErrorsIn('apply', ['cv']);
});
