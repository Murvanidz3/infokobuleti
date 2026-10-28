<?php

return [
    'reasons' => [
        'incomplete' => 'The listing is incomplete: add a description, price and photos.',
        'wrong_category' => 'The listing is in the wrong category.',
        'duplicate' => 'This listing has already been posted.',
        'misleading_price' => 'The price is unrealistic or misleading.',
        'bad_photos' => 'The photos are of poor quality or do not match the listing.',
        'contacts_in_text' => 'Contact details must not appear in the text or photos.',
        'prohibited' => 'The listing breaks the site rules.',
        'spam' => 'The listing was considered spam.',
    ],
    'notification' => [
        'greeting' => 'Hello :name,',
        'approved' => [
            'subject' => 'Your listing is live',
            'line' => 'Your listing ":title" was reviewed and is now live.',
        ],
        'rejected' => [
            'subject' => 'Your listing was not published',
            'line' => 'Your listing ":title" could not be published.',
        ],
        'reason' => 'Reason: :reason',
        'action' => 'Go to infokobuleti',
    ],
];
