<?php

namespace App\Http\Controllers\Account;

use App\Actions\Privacy\ExportUserData;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Download of the user's own data export (signed link from the notification).
 */
class DataExportController extends Controller
{
    public function __invoke(Request $request, string $file): StreamedResponse
    {
        $path = ExportUserData::DIRECTORY.'/'.$file;

        abort_unless(
            preg_match('/^\d+-[A-Za-z0-9]{32}\.json$/', $file) === 1
            && str_starts_with($file, $request->user()?->getKey().'-')
            && Storage::disk('local')->exists($path),
            404,
        );

        return Storage::disk('local')->download($path, 'infokobuleti-data-'.now()->format('Y-m-d').'.json', ['Content-Type' => 'application/json']);
    }
}
