<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\VacancyApplication;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CV of a job application (private disk): for the employer who posted the vacancy and the applicant.
 */
class ApplicationCvController extends Controller
{
    public function __invoke(Request $request, int $application): StreamedResponse
    {
        $model = VacancyApplication::query()->with('vacancy')->findOrFail($application);
        $user = $request->user();

        abort_unless($user !== null && ((int) $model->vacancy?->user_id === (int) $user->getKey() || (int) $model->user_id === (int) $user->getKey()), 404);

        $cv = $model->getFirstMedia('cv');
        abort_if($cv === null, 404);

        return $cv->toResponse($request);
    }
}
