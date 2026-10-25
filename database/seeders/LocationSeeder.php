<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $this->seed(require __DIR__.'/data/locations.php');
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     */
    private function seed(array $nodes, ?int $parentId = null): void
    {
        foreach ($nodes as $sort => $node) {
            $location = Location::query()->updateOrCreate(['slug' => $node['slug']], [
                'parent_id' => $parentId,
                'type' => $node['type'],
                'name' => $node['name'],
                'lat' => $node['lat'],
                'lng' => $node['lng'],
                'sort' => $sort,
                'is_active' => true,
            ]);

            $this->seed($node['children'] ?? [], $location->id);
        }
    }
}
