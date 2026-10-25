<?php

namespace Database\Seeders;

use App\Enums\CategoryModule;
use App\Enums\Role;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $authors = User::role([Role::Admin->value, Role::Moderator->value])->get();
        $categories = Category::query()->where('module', CategoryModule::News)->pluck('id', 'slug');

        foreach (require __DIR__.'/data/articles.php' as $row) {
            Article::factory()->create([
                'user_id' => $authors->random()->id,
                'category_id' => $categories[$row['category']],
                'title' => $row['title'],
                'excerpt' => $row['excerpt'],
                'body' => $row['body'],
                'is_featured' => $row['featured'],
                'published_at' => now()->subDays($row['days'])->setTime(fake()->numberBetween(9, 19), fake()->randomElement([0, 15, 30, 45])),
                'views_count' => fake()->numberBetween(40, 90) * (45 - $row['days']),
            ]);
        }
    }
}
