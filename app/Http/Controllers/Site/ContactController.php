<?php

namespace App\Http\Controllers\Site;

use App\Enums\ContactTopic;
use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Notifications\ContactFormSubmitted;
use App\Rules\HumanVerified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function create(Request $request): View
    {
        $user = $request->user();

        return view('pages.contact', [
            'topics' => ContactTopic::options(),
            'topic' => ContactTopic::tryFrom((string) $request->query('topic'))->value ?? ContactTopic::General->value,
            'user' => $user,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'topic' => ['required', Rule::enum(ContactTopic::class)],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required_without:phone', 'nullable', 'email', 'max:190'],
            'phone' => ['required_without:email', 'nullable', 'string', 'regex:/^\+?[\d\s\-()]{7,20}$/'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'cf-turnstile-response' => [new HumanVerified],
        ], attributes: __('site.contact.fields'));

        $contact = ContactMessage::query()->create([
            ...Arr::only($data, ['topic', 'name', 'email', 'phone', 'message']),
            'user_id' => $request->user()?->getKey(),
            'locale' => app()->getLocale(),
            'ip' => $request->ip(),
        ]);

        $mailbox = site_setting('contacts.email', config('site.contact.email'));

        if (is_string($mailbox) && $mailbox !== '') {
            Notification::route('mail', $mailbox)->notify(new ContactFormSubmitted($contact));
        }

        return redirect()->to(localized_route('contact'))->with('contact_sent', true);
    }
}
