<?php

use App\Enums\ApplicationStatus;
use App\Enums\BookingStatus;
use App\Livewire\Account\Applications;
use App\Livewire\Account\Bookings;
use App\Livewire\Account\HotelRooms;
use App\Models\BookingRequest;
use App\Models\Hotel;
use App\Models\RoomAvailability;
use App\Models\RoomType;
use App\Models\User;
use App\Models\Vacancy;
use App\Models\VacancyApplication;
use App\Notifications\ApplicationStatusChanged;
use App\Notifications\BookingAnswered;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->owner = User::factory()->business()->create();
});

it('shows the bookings and applications menu items only to owners of hotels and vacancies', function () {
    $this->actingAs($this->owner)->get('/account')->assertDontSee(localized_route('account.bookings'), false);

    Hotel::factory()->published()->for($this->owner)->create();
    Vacancy::factory()->published()->for($this->owner)->create();

    $this->actingAs($this->owner)->get('/account')
        ->assertSee(localized_route('account.bookings'), false)
        ->assertSee(localized_route('account.applications'), false);
});

it('accepts and declines booking requests and tells the guest', function () {
    Notification::fake();
    $hotel = Hotel::factory()->published()->for($this->owner)->create();
    $guest = User::factory()->create();
    $withAccount = BookingRequest::factory()->for($hotel)->create(['user_id' => $guest->id]);
    $byEmail = BookingRequest::factory()->for($hotel)->create(['user_id' => null, 'contact_email' => 'guest@example.com']);
    $foreign = BookingRequest::factory()->create();

    $this->actingAs($this->owner)->get('/account/bookings')->assertOk()->assertSee($withAccount->contact_name);

    Livewire::actingAs($this->owner)->test(Bookings::class)
        ->set("notes.{$withAccount->id}", 'Check-in from 14:00')
        ->call('accept', $withAccount->id)
        ->call('decline', $byEmail->id)
        ->call('accept', $foreign->id)
        ->assertNotFound();

    expect($withAccount->fresh())->status->toBe(BookingStatus::Accepted)->owner_note->toBe('Check-in from 14:00')
        ->and($byEmail->fresh()->status)->toBe(BookingStatus::Declined)
        ->and($foreign->fresh()->status)->toBe(BookingStatus::Pending);

    Notification::assertSentTo($guest, BookingAnswered::class);
    Notification::assertSentOnDemand(BookingAnswered::class, fn (BookingAnswered $notification, array $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'guest@example.com');
});

it('manages room types, seasonal prices and the calendar of the owner’s hotel only', function () {
    $hotel = Hotel::factory()->published()->for($this->owner)->create();
    $other = Hotel::factory()->published()->create();

    $this->actingAs($this->owner)->get("/account/hotels/{$hotel->id}/rooms")->assertOk();
    $this->actingAs($this->owner)->get("/account/hotels/{$other->id}/rooms")->assertNotFound();

    $component = Livewire::actingAs($this->owner)->test(HotelRooms::class, ['hotelId' => $hotel->id])
        ->call('editRoom')
        ->set('room.name', 'Double room with balcony')
        ->set('room.base_price', '150')
        ->set('room.capacity_adults', '2')
        ->call('saveRoom')
        ->assertHasNoErrors();

    $room = RoomType::query()->where('hotel_id', $hotel->id)->sole();
    expect($hotel->fresh()->price_from)->toBe(150.0);

    $from = today()->addDays(5)->toDateString();
    $to = today()->addDays(7)->toDateString();

    $component->call('applyRange', $to, $from, 'close');
    expect(RoomAvailability::query()->where('room_type_id', $room->id)->where('is_closed', true)->count())->toBe(3);

    $component->set('rangePrice', '200')->call('applyRange', $from, $from, 'price');
    expect(RoomAvailability::query()->where('room_type_id', $room->id)->whereDate('date', $from)->value('price_override'))->toEqual('200.00');

    $component->call('applyRange', $from, $to, 'open');
    expect(RoomAvailability::query()->where('room_type_id', $room->id)->count())->toBe(1)
        ->and(RoomAvailability::query()->where('room_type_id', $room->id)->where('is_closed', true)->count())->toBe(0);

    $component->set('rule.starts_on', today()->addMonth()->toDateString())
        ->set('rule.ends_on', today()->addMonths(2)->toDateString())
        ->set('rule.price', '220')
        ->call('addRule')
        ->assertHasNoErrors();
    expect($room->priceRules()->count())->toBe(1);

    $otherRoom = RoomType::factory()->create(['hotel_id' => $other->id]);
    $component->call('selectRoom', $otherRoom->id)->assertNotFound();
});

it('moves applications through the pipeline and serves the CV to the employer only', function () {
    Notification::fake();
    Storage::fake('local');
    $vacancy = Vacancy::factory()->published()->for($this->owner)->create();
    $applicant = User::factory()->create();
    $application = VacancyApplication::factory()->create(['vacancy_id' => $vacancy->id, 'user_id' => $applicant->id, 'status' => ApplicationStatus::New]);
    $application->addMedia(UploadedFile::fake()->createWithContent('cv.pdf', "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF"))->toMediaCollection('cv');

    $this->actingAs($this->owner)->get('/account/applications')->assertOk()->assertSee($applicant->name);

    $component = Livewire::actingAs($this->owner)->test(Applications::class)
        ->call('toggle', $application->id);
    expect($application->fresh()->status)->toBe(ApplicationStatus::Viewed);
    Notification::assertNothingSent();

    $component->call('setStatus', $application->id, 'shortlisted');
    expect($application->fresh()->status)->toBe(ApplicationStatus::Shortlisted);
    Notification::assertSentTo($applicant, ApplicationStatusChanged::class);

    $this->actingAs($this->owner)->get("/account/applications/{$application->id}/cv")->assertOk();
    $this->actingAs($applicant)->get("/account/applications/{$application->id}/cv")->assertOk();
    $this->actingAs(User::factory()->create())->get("/account/applications/{$application->id}/cv")->assertNotFound();

    $foreign = VacancyApplication::factory()->create(['status' => ApplicationStatus::New]);
    $component->call('setStatus', $foreign->id, 'hired')->assertNotFound();
    expect($foreign->fresh()->status)->toBe(ApplicationStatus::New);
});
