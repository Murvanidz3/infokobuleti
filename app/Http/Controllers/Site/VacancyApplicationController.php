<?php

namespace App\Http\Controllers\Site;

use App\Enums\ApplyMethod;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vacancy;
use App\Models\VacancyApplication;
use App\Notifications\NewInquiry;
use App\Support\GeorgianPhone;
use App\Support\Listings\Contact;
use App\Support\Listings\Visibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Applying to a job on the site, with an optional CV (PDF or Word, kept on the private disk).
 */
class VacancyApplicationController extends Controller
{
    public function __invoke(Request $request, Vacancy $vacancy): RedirectResponse
    {
        abort_unless(Visibility::isLive($vacancy), 404);
        /** @var User $user */
        $user = $request->user();
        $back = localized_route('jobs.show', [$vacancy]).'#apply';

        if ($vacancy->apply_method !== ApplyMethod::Site) {
            return redirect()->to($back);
        }

        if ($vacancy->user_id === $user->id) {
            return redirect()->to($back)->with('toast', ['message' => __('site.apply.own_vacancy'), 'type' => 'error']);
        }

        if ($vacancy->applications()->where('user_id', $user->id)->exists()) {
            return redirect()->to($back)->with('toast', ['message' => __('site.apply.already'), 'type' => 'info']);
        }

        $data = $request->validateWithBag('apply', [
            'cover_note' => ['nullable', 'string', 'max:3000'],
            'contact_phone' => ['required', 'string', 'regex:/^\+?[\d\s\-()]{7,20}$/'],
            'cv' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ], attributes: __('site.apply.fields'));

        $application = DB::transaction(function () use ($vacancy, $user, $data, $request): VacancyApplication {
            $application = VacancyApplication::query()->create([
                'vacancy_id' => $vacancy->id,
                'user_id' => $user->id,
                'cover_note' => $data['cover_note'] ?? null,
                'contact_phone' => Contact::normalizePhone($data['contact_phone']),
            ]);

            if ($request->hasFile('cv')) {
                $application->addMediaFromRequest('cv')->usingFileName('cv-'.$application->id.'.'.$request->file('cv')?->extension())->toMediaCollection('cv');
            }

            return $application;
        });

        $vacancy->loadMissing('user');
        $vacancy->user?->notify(new NewInquiry('application', $vacancy, [
            'name' => $user->name,
            'phone' => GeorgianPhone::format($application->contact_phone),
            'text' => $application->cover_note,
        ], localized_route('account.applications', ['vacancy' => $vacancy->getKey()], $vacancy->user->preferredLocale())));

        return redirect()->to($back)->with('toast', ['message' => __('site.apply.sent'), 'type' => 'success']);
    }
}
