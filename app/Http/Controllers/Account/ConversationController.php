<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\Account\Inbox;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Message pages (the inbox and threads are Livewire components) and private photo attachments.
 */
class ConversationController extends Controller
{
    public function index(): View
    {
        return view('pages.account.messages', ['conversationId' => null]);
    }

    public function show(Request $request, Inbox $inbox, int $conversation): View
    {
        $model = Conversation::query()->findOrFail($conversation);
        abort_unless($inbox->isParticipant($this->user($request), $model), 404);

        return view('pages.account.messages', ['conversationId' => $model->id]);
    }

    public function attachment(Request $request, Inbox $inbox, int $conversation, int $media): StreamedResponse
    {
        $model = Conversation::query()->findOrFail($conversation);
        abort_unless($inbox->isParticipant($this->user($request), $model), 404);

        $file = Media::query()
            ->where('model_type', (new Message)->getMorphClass())
            ->whereIn('model_id', Message::query()->select('id')->where('conversation_id', $model->id))
            ->where('collection_name', 'attachments')
            ->findOrFail($media);

        return $file->toInlineResponse($request);
    }

    private function user(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}
