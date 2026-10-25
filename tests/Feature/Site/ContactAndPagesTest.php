<?php

use App\Models\ContactMessage;
use App\Notifications\ContactFormSubmitted;
use App\Support\SafeHtml;
use Illuminate\Support\Facades\Notification;

it('shows the contact form and stores messages', function () {
    Notification::fake();

    $this->get('/en/contact?topic=advertising')->assertOk()->assertSee('value="advertising" selected', false);

    $this->post('/en/contact', [
        'topic' => 'advertising',
        'name' => 'Giorgi',
        'email' => 'giorgi@example.com',
        'message' => 'We would like to advertise our hotel.',
    ])->assertRedirect(localized_route('contact', [], 'en'))->assertSessionHas('contact_sent');

    expect(ContactMessage::query()->sole()->locale)->toBe('en');
    Notification::assertSentOnDemand(ContactFormSubmitted::class);
});

it('validates the contact form and its bot check', function () {
    $this->post('/contact', ['topic' => 'general', 'name' => '', 'message' => 'short'])
        ->assertSessionHasErrors(['name', 'email', 'phone', 'message']);

    $this->post('/contact', ['topic' => 'general', 'name' => 'Bot', 'email' => 'bot@example.com', 'message' => 'Buy cheap things now', 'cf-turnstile-response' => 'fail'])
        ->assertSessionHasErrors(['cf-turnstile-response']);

    expect(ContactMessage::query()->count())->toBe(0);
});

it('rate-limits the contact form', function () {
    foreach (range(1, 3) as $attempt) {
        $this->post('/contact', ['topic' => 'general', 'name' => 'A', 'email' => 'a@example.com', 'message' => 'Hello there, a question.']);
    }

    $this->post('/contact', ['topic' => 'general', 'name' => 'A', 'email' => 'a@example.com', 'message' => 'Hello there, a question.'])->assertTooManyRequests();
});

it('renders localized error pages', function () {
    $this->get('/ru/no-such-page')->assertNotFound()->assertSee('Страница не найдена')->assertSee('<html lang="ru">', false);
    $this->get('/no-such-page')->assertNotFound()->assertSee(__('site.errors.404.title', [], 'ka'));
});

it('cleans admin rich text', function () {
    $html = SafeHtml::clean('<p onclick="x()">Hi <a href="javascript:alert(1)">bad</a> <a href="https://example.com">good</a></p><script>alert(1)</script><div><h2 style="color:red">Title</h2></div><img src="x" onerror="alert(1)">');

    expect($html)->toBe('<p>Hi <a>bad</a> <a href="https://example.com" rel="noopener nofollow" target="_blank">good</a></p><h2>Title</h2><img loading="lazy">');
});
