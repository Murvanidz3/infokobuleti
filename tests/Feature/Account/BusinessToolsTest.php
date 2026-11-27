<?php

use App\Enums\ApplicationStatus;
use App\Livewire\Account\Applications;
use App\Models\User;
use App\Models\Vacancy;
use App\Models\VacancyApplication;
use App\Notifications\ApplicationStatusChanged;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->owner = User::factory()->business()->create();
});

it('shows the applications menu item only to owners of vacancies', function () {
    $this->actingAs($this->owner)->get('/account')->assertDontSee(localized_route('account.applications'), false);

    Vacancy::factory()->published()->for($this->owner)->create();

    $this->actingAs($this->owner)->get('/account')
        ->assertSee(localized_route('account.applications'), false);
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
