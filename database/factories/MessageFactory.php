<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'user_id' => User::factory(),
            'body' => fake()->randomElement([
                'გამარჯობა, ჯერ კიდევ აქტუალურია?',
                'დიახ, აქტუალურია. როდის შეგიძლიათ ნახვა?',
                'ხვალ საღამოს, 7 საათზე მოგიწყობთ?',
                'კი, შესანიშნავია. მისამართს მოგწერთ.',
            ]),
        ];
    }
}
