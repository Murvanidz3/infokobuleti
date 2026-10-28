<?php

namespace App\Actions\Vacancies;

use App\Enums\ApplicationStatus;
use App\Models\VacancyApplication;
use App\Notifications\ApplicationStatusChanged;

/**
 * The employer moves an application through the pipeline. The applicant hears about the decisions
 * (shortlisted, hired, rejected), not about the employer merely opening it.
 */
class ChangeApplicationStatus
{
    public function handle(VacancyApplication $application, ApplicationStatus $status): void
    {
        if ($application->status === $status) {
            return;
        }

        $application->forceFill(['status' => $status, 'status_changed_at' => now()])->save();

        if (in_array($status, [ApplicationStatus::Shortlisted, ApplicationStatus::Hired, ApplicationStatus::Rejected], true)) {
            $application->loadMissing('user', 'vacancy');
            $application->user?->notify(new ApplicationStatusChanged($application));
        }
    }
}
