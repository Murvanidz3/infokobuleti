<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * The single line icon set: 24px grid, round caps and joins, 1.5px stroke by default.
 */
class Icon extends Component
{
    /**
     * @var array<string, string>
     */
    public const PATHS = [
        'pin' => 'M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21zM12 12a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z',
        'bed' => 'M3 19V6M3 15h18M21 19v-6a2 2 0 0 0-2-2h-8v4M6.5 12.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z',
        'building' => 'M5 21V4a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v17M15 9h3a1 1 0 0 1 1 1v11M3 21h18M8.5 7h3M8.5 11h3M8.5 15h3',
        'house' => 'M3 11l9-7 9 7M5 9.5V20h14V9.5M10 20v-5h4v5',
        'car' => 'M5 16h14M3.5 16v-3l2-5a1.5 1.5 0 0 1 1.4-1h10.2a1.5 1.5 0 0 1 1.4 1l2 5v3M3.5 16v2.5M20.5 16v2.5M7 13h.01M17 13h.01',
        'wrench' => 'M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z',
        'briefcase' => 'M3 8h18v12H3zM8 8V5a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v3M3 13h18',
        'fork' => 'M6 3v8M4 3v5a2 2 0 0 0 4 0V3M6 11v10M17 21V3c-2 1.5-3 4-3 7v3h3',
        'calendar' => 'M4 6h16v15H4zM4 10h16M8 3v4M16 3v4',
        'users' => 'M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2 21v-1a6 6 0 0 1 12 0v1M16 3.5a4 4 0 0 1 0 7.5M22 21v-1a6 6 0 0 0-4-5.6',
        'tag' => 'M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9-9-9zM8 8h.01',
        'search' => 'M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14zM20 20l-4-4',
        'grid' => 'M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z',
        'clock' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 7v5l3 2',
        'area' => 'M3 9V3h6M21 9V3h-6M3 15v6h6M21 15v6h-6',
        'door' => 'M6 21V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v17M3 21h18M14 12h.01',
        'layers' => 'M12 3l9 5-9 5-9-5zM3 13l9 5 9-5',
        'sofa' => 'M5 10V8a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v2M3 12a1.5 1.5 0 0 1 3 0v2h12v-2a1.5 1.5 0 0 1 3 0v5H3zM5 17v2M19 17v2',
        'bike' => 'M6 18a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zM18 18a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zM6 14.5l4-7h5l3 7M10 7.5h-2M15 7.5l-3 7H6',
        'menu' => 'M4 7h16M4 12h16M4 17h16',
        'close' => 'M6 6l12 12M18 6L6 18',
        'plus' => 'M12 5v14M5 12h14',
        'arrow-right' => 'M5 12h14M13 6l6 6-6 6',
        'chevron-left' => 'M15 6l-6 6 6 6',
        'chevron-right' => 'M9 6l6 6-6 6',
        'chevron-down' => 'M6 9l6 6 6-6',
        'heart' => 'M12 20s-7-4.5-9-9a4.5 4.5 0 0 1 9-3 4.5 4.5 0 0 1 9 3c-2 4.5-9 9-9 9z',
        'waves' => 'M2 10c2 0 2-1.5 4-1.5s2 1.5 4 1.5 2-1.5 4-1.5 2 1.5 4 1.5 2-1.5 4-1.5M2 16c2 0 2-1.5 4-1.5s2 1.5 4 1.5 2-1.5 4-1.5 2 1.5 4 1.5 2-1.5 4-1.5',
        'sun' => 'M12 16a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4',
        'exchange' => 'M4 7h13l-3-3M20 17H7l3 3',
        'plane' => 'M17.8 19.2L16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z',
        'train' => 'M7 3h10a3 3 0 0 1 3 3v9a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3zM4 11h16M8 14.5h.01M16 14.5h.01M8 18l-2 3M16 18l2 3',
        'phone' => 'M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2',
        'mail' => 'M3 6h18v12H3zM3 7l9 6 9-6',
        'facebook' => 'M15 3h-2.5A3.5 3.5 0 0 0 9 6.5V9H7v3.5h2V21h3.5v-8.5H15l.5-3.5h-3V7a1 1 0 0 1 1-1H15z',
        'instagram' => 'M7 3h10a4 4 0 0 1 4 4v10a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7a4 4 0 0 1 4-4zM12 16a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM17.5 6.5h.01',
        'youtube' => 'M3 8a3 3 0 0 1 3-3h12a3 3 0 0 1 3 3v8a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3zM10 9l5 3-5 3z',
        'telegram' => 'M21 4L3 11l6 2 2 6 3-4 5 4zM9 13l12-9',
        'check' => 'M5 12.5l4.5 4.5L19 7.5',
        'check-circle' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM8 12.5l2.7 2.7L16 9.8',
        'info' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 11v5M12 8h.01',
        'alert' => 'M12 9v4M12 17h.01M10.3 3.9L2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z',
        'upload' => 'M12 16V4M7 9l5-5 5 5M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3',
        'image' => 'M5 4h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1zM4 16l4.5-4.5 4 4 2.5-2.5 5 5M15.5 9h.01',
        'inbox' => 'M3 13h5l1.5 3h5l1.5-3h5M5.5 5h13L21 13v6a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-6z',
        'filter' => 'M4 6h16M7 12h10M10 18h4',
        'user' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4.5 20.5a7.5 7.5 0 0 1 15 0',
        'logout' => 'M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 16l-4-4 4-4M6 12h10',
        'shield' => 'M12 3l7.5 3v5.5c0 4.5-3.2 8.3-7.5 9.5-4.3-1.2-7.5-5-7.5-9.5V6zM9 12l2 2 4-4',
        'smartphone' => 'M8 3h8a1.5 1.5 0 0 1 1.5 1.5v15A1.5 1.5 0 0 1 16 21H8a1.5 1.5 0 0 1-1.5-1.5v-15A1.5 1.5 0 0 1 8 3zM11 18h2',
        'monitor' => 'M4 4h16a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1zM8 20h8M12 16v4',
        'key' => 'M14.5 13a4.5 4.5 0 1 0-4.24-3L3.5 16.76V20.5h3.74V18.5h2v-2h2l1.02-1.02A4.5 4.5 0 0 0 14.5 13zM16 8h.01',
        'copy' => 'M9 9h10a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V10a1 1 0 0 1 1-1zM5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1',
        'star' => 'M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z',
        'share' => 'M18 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM6 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM18 22a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM8.6 13.5l6.8 4M15.4 6.5l-6.8 4',
        'flag' => 'M5 21V4M5 4h11l-2 4 2 4H5',
        'message' => 'M4 5h16v11H8l-4 4z',
        'map' => 'M9 4L3 6v14l6-2 6 2 6-2V4l-6 2zM9 4v14M15 6v14',
        'list' => 'M8 6h13M8 12h13M8 18h13M3.5 6h.01M3.5 12h.01M3.5 18h.01',
        'globe' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18',
        'external' => 'M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5',
        'expand' => 'M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5M9 9l-5-5M15 9l5-5M9 15l-5 5M15 15l5 5',
        'minus' => 'M5 12h14',
        'whatsapp' => 'M4 20l1.3-4A8.5 8.5 0 1 1 8 18.7zM9 8.5c0 3.5 3 6.5 6.5 6.5l1-1.6-2-1-1 1a4 4 0 0 1-2.4-2.4l1-1-1-2z',
        'viber' => 'M12 3c5 0 8 2.5 8 7.5S17 18 12 18l-3 3v-3.3C5.5 16.6 4 14 4 10.5 4 5.5 7 3 12 3zM10 7.5c0 3 2.5 5.5 5.5 5.5',
        'file' => 'M6 3h8l5 5v13H6zM14 3v5h5',
        'send' => 'M21 3L10 14M21 3l-7 18-4-7-7-4z',
        'eye' => 'M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12zM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z',
        'wind' => 'M3 8h11a3 3 0 1 0-3-3M3 12h16a3 3 0 1 1-3 3M3 16h8',
        'navigation' => 'M3 11l18-8-8 18-2-8z',
        'wifi' => 'M2 9a15 15 0 0 1 20 0M5 12.5a10 10 0 0 1 14 0M8.5 16a5 5 0 0 1 7 0M12 19.5h.01',
        'ticket' => 'M3 7h18v3a2 2 0 0 0 0 4v3H3v-3a2 2 0 0 0 0-4zM14 7v10',
        'newspaper' => 'M4 5h13v14H6a2 2 0 0 1-2-2zM17 9h3v8a2 2 0 0 1-2 2M8 9h5M8 13h5M8 16h3',
        'compass' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM15.5 8.5l-2 5-5 2 2-5z',
        'refresh' => 'M20 11a8 8 0 0 0-14.5-4.5L4 8M4 4v4h4M4 13a8 8 0 0 0 14.5 4.5L20 16M20 20v-4h-4',
    ];

    public function __construct(
        public string $name,
        public int|float $size = 20,
        public int|float $stroke = 1.5,
    ) {
        if (! array_key_exists($name, self::PATHS)) {
            throw new InvalidArgumentException("Unknown icon [{$name}].");
        }
    }

    /**
     * Whether an icon exists (names stored in the database, such as category icons, may not).
     */
    public static function has(?string $name): bool
    {
        return $name !== null && array_key_exists($name, self::PATHS);
    }

    public function path(): string
    {
        return self::PATHS[$this->name];
    }

    public function render(): View
    {
        return view('components.icon');
    }
}
