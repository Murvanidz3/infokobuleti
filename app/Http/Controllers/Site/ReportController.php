<?php

namespace App\Http\Controllers\Site;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Support\Listings\ListingTypes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * A visitor flags an item for the moderators (one open report per person and item).
 */
class ReportController extends Controller
{
    public function __invoke(Request $request, string $type, int $id): RedirectResponse
    {
        $record = ListingTypes::find($type, $id);

        $data = $request->validateWithBag('report', [
            'reason' => ['required', Rule::enum(ReportReason::class)],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        Report::query()->firstOrCreate(
            [
                'user_id' => $request->user()?->getKey(),
                'reportable_type' => $record->getMorphClass(),
                'reportable_id' => $record->getKey(),
                'status' => ReportStatus::Open,
            ],
            ['reason' => $data['reason'], 'comment' => $data['comment'] ?? null],
        );

        return back()->with('toast', ['message' => __('site.report.sent'), 'type' => 'success']);
    }
}
