<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (require __DIR__.'/data/categories.php' as $module => $nodes) {
            $this->seed($module, $nodes);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     */
    private function seed(string $module, array $nodes, ?int $parentId = null): void
    {
        foreach ($nodes as $sort => $node) {
            $category = Category::query()->updateOrCreate(['module' => $module, 'slug' => $node['slug']], [
                'parent_id' => $parentId,
                'name' => $node['name'],
                'icon' => $node['icon'] ?? null,
                'sort' => $sort,
                'attribute_schema' => $node['schema'] ?? null,
                'is_active' => true,
            ]);

            $this->seed($module, $node['children'] ?? [], $category->id);
        }
    }
}
