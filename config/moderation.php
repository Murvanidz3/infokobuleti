<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rejection reason templates
    |--------------------------------------------------------------------------
    |
    | Keys of lang/{locale}/moderation.php "reasons". The owner receives the
    | reason in their own language, followed by the moderator's note.
    |
    */

    'reasons' => [
        'incomplete',
        'wrong_category',
        'duplicate',
        'misleading_price',
        'bad_photos',
        'contacts_in_text',
        'prohibited',
        'spam',
    ],

];
