<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Static pages the site links to. Texts are written later in the admin. Safe to re-run.
 */
class PageSeeder extends Seeder
{
    /** @var array<string, array{ka: string, en: string, ru: string}> */
    private const array PAGES = [
        'about' => ['ka' => 'ჩვენს შესახებ', 'en' => 'About us', 'ru' => 'О нас'],
        'terms' => ['ka' => 'წესები და პირობები', 'en' => 'Terms of use', 'ru' => 'Условия использования'],
        'privacy' => ['ka' => 'კონფიდენციალურობის პოლიტიკა', 'en' => 'Privacy policy', 'ru' => 'Политика конфиденциальности'],
        'cookies' => ['ka' => 'Cookie-ების პოლიტიკა', 'en' => 'Cookie policy', 'ru' => 'Политика cookie'],
        'contacts' => ['ka' => 'კონტაქტი', 'en' => 'Contacts', 'ru' => 'Контакты'],
    ];

    public function run(): void
    {
        $sort = 0;

        foreach (self::PAGES as $slug => $title) {
            Page::query()->firstOrCreate(['slug' => $slug], ['title' => $title, 'sort' => $sort++, 'is_published' => true]);
        }
    }
}
