<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\User;
use Database\Factories\Concerns\ListingStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    use ListingStates;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            ...$this->listingAttributes(),
            'user_id' => User::factory(),
            'title' => [
                'ka' => 'ბულვარზე ახალი ველობილიკის მშენებლობა დასრულდა',
                'en' => 'New cycle path on the boulevard is complete',
                'ru' => 'На бульваре достроили новую велодорожку',
            ],
            'excerpt' => [
                'ka' => 'ველობილიკი ქალაქის ცენტრს ციხისძირთან აკავშირებს.',
                'en' => 'The path links the town centre with Tsikhisdziri.',
                'ru' => 'Дорожка соединяет центр города с Цихисдзири.',
            ],
            'body' => [
                'ka' => '<p>ველობილიკი ქალაქის ცენტრს ციხისძირთან აკავშირებს და მთელ სიგრძეზე განათებულია.</p>',
                'en' => '<p>The path links the town centre with Tsikhisdziri and is lit along its whole length.</p>',
                'ru' => '<p>Дорожка соединяет центр города с Цихисдзири и освещена по всей длине.</p>',
            ],
            'is_featured' => false,
        ];
    }
}
