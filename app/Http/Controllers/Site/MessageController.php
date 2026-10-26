<?php

namespace App\Http\Controllers\Site;

use App\Actions\Messaging\SendMessage;
use App\Actions\Messaging\StartConversation;
use App\Exceptions\MessagingBlocked;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ListingStats;
use App\Support\Listings\Contact;
use App\Support\Listings\ListingTypes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * "Write to the owner": starts (or continues) the conversation about this item.
 */
class MessageController extends Controller
{
    public function __invoke(Request $request, ListingStats $stats, StartConversation $start, SendMessage $send, string $type, int $id): RedirectResponse
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

        $conversation = $start->handle($sender, $owner, $record);

        try {
            $send->handle($sender, $conversation, $data['body']);
        } catch (MessagingBlocked) {
            return back()->with('toast', ['message' => __('account.messages.blocked'), 'type' => 'error']);
        }

        $stats->record($record, 'messages');

        return back()->with('toast', [
            'message' => __('site.message.sent'),
            'type' => 'success',
            'action' => ['label' => __('account.messages.open'), 'url' => localized_route('account.messages.show', ['conversation' => $conversation->getKey()])],
        ]);
    }
}
