<?php

return [
    'help' => 'This is the infokobuleti.com bot. To get saved-search alerts, link your account here: :url',

    'link' => [
        'done' => 'Done, :name! Telegram is linked to your account. Send /stop to unlink.',
        'expired' => 'The link has expired. Create a new one on the site, on the notifications page.',
        'stopped' => 'Telegram is unlinked from your account. No more alerts will come here.',
    ],

    'moderation' => [
        'heading' => 'New listing to moderate',
        'owner' => 'Author: :name, :phone',
        'approve' => '✓ Approve',
        'reject' => '✗ Reject',
        'open_admin' => 'Open in admin',
        'choose_reason' => 'Choose the reason:',
        'other_reason' => 'Other reason…',
        'back' => '← Back',
        'type_reason' => 'Type the rejection reason for “:title” in your next message. The author will see it.',
        'approved' => 'Approved · :name',
        'rejected' => 'Rejected · :name: :reason',
        'status' => 'Status: :status',
        'approved_short' => 'Approved',
        'rejected_short' => 'Rejected, the author was told.',
        'already_done' => 'This listing was already reviewed.',
        'not_allowed' => 'You cannot moderate, or Telegram is not linked to your account.',
    ],

    'reasons' => [
        'incomplete' => 'Incomplete',
        'wrong_category' => 'Wrong category',
        'duplicate' => 'Duplicate',
        'misleading_price' => 'Unrealistic price',
        'bad_photos' => 'Bad photos',
        'contacts_in_text' => 'Contacts in text',
        'prohibited' => 'Breaks the rules',
        'spam' => 'Spam',
    ],
];
