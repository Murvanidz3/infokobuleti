<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewInquiry;
use App\Services\ListingStats;
use App\Support\Listings\Contact;
use App\Support\Listings\ListingTypes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * "Write to the owner": starts (or continues) the conversation about this item.
 */
class MessageController extends Controller
{
    public function __invoke(Request $request, ListingStats $stats, string $type, int $id): RedirectResponse
    {
        $record = ListingTypes::find($type, $id);
        $record->loadMissing('user');
        $owner = Contact::owner($record);
        /** @var User $sender */
        $sender = $request->user();

        abort_if($owner === null, 404);

        if ($owner->is($sender)) {
            return back()->with('toast', ['message' => __('site.message.own_item'), 'type' => 'error']);
        }

        $data = $request->validateWithBag('message', ['body' => ['required', 'string', 'min:2', 'max:2000']]);

        DB::transaction(function () use ($record, $owner, $sender, $data): void {
            $conversation = Conversation::query()
                ->where('subject_type', $record->getMorphClass())
                ->where('subject_id', $record->getKey())
                ->whereHas('participants', fn (Builder $query) => $query->whereKey($sender->getKey()))
                ->whereHas('participants', fn (Builder $query) => $query->whereKey($owner->getKey()))
                ->first();

            if ($conversation === null) {
                $conversation = Conversation::query()->create(['subject_type' => $record->getMorphClass(), 'subject_id' => $record->getKey()]);
                $conversation->participants()->attach([$sender->getKey(), $owner->getKey()]);
            }

            Message::query()->create(['conversation_id' => $conversation->getKey(), 'user_id' => $sender->getKey(), 'body' => $data['body']]);
            $conversation->participants()->updateExistingPivot($sender->getKey(), ['last_read_at' => now()]);
        });

        $stats->record($record, 'messages');
        $owner->notify(new NewInquiry('message', $record, ['name' => $sender->name, 'text' => $data['body']]));

        return back()->with('toast', ['message' => __('site.message.sent'), 'type' => 'success']);
    }
}
