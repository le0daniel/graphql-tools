<?php declare(strict_types=1);

namespace GraphQlTools\Test\BigSchema;

use Closure;
use GraphQlTools\Contract\ExecutableByDataLoader;
use GraphQlTools\Contract\GraphQlContext;
use GraphQlTools\Helper\HasDataloaders;
use GraphQlTools\Utility\Arrays;
use RuntimeException;

final class BigSchemaContext implements GraphQlContext
{
    use HasDataloaders;

    private array $clubs = [
        [
            'id' => 'basel',
            'name' => 'FC Basel',
            'city' => 'Basel',
        ],
        [
            'id' => 'basel',
            'name' => 'Grasshopper Club',
            'city' => 'Zuerich',
        ],
        [
            'id' => 'lausanne-ouchy',
            'name' => 'Lausanne Ouchy',
            'city' => 'Lausanne',
        ],
        [
            'id' => 'lausanne-sport',
            'name' => 'Lausanne Sport',
            'city' => 'Lausanne',
        ],
        [
            'id' => 'lugano',
            'name' => 'FC Lugano',
            'city' => 'Lugano',
        ],
        [
            'id' => 'lucern',
            'name' => 'FC Luzern',
            'city' => 'Luzern',
        ],
        [
            'id' => 'st-gallen',
            'name' => 'FC St. Gallen',
            'city' => 'St. Gallen',
        ],
        [
            'id' => 'geneva',
            'name' => 'Servette FC',
            'city' => 'Geneva',
        ],
        [
            'id' => 'winterthur',
            'name' => 'FC Winterthur',
            'city' => 'Winterthur',
        ],
        [
            'id' => 'bern',
            'name' => 'Young Boys',
            'city' => 'Bern',
        ],
        [
            'id' => 'yverdon-sport',
            'name' => 'Yverdon Sport',
            'city' => 'Yverdon-les-Bains',
        ],
        [
            'id' => 'fcz',
            'name' => 'FC Zuerich',
            'city' => 'Zuerich',
        ],
    ];

    protected function makeInstanceOfDataLoaderExecutor(string $key, array $arguments): Closure|ExecutableByDataLoader
    {
        return match ($key) {
            'clubsById' => $this->loadClubsById(...),
            default => throw new RuntimeException("Invalid data loader key"),
        };
    }

    public function loadClubs(array $arguments): ?array {
        return $this->paginate($this->clubs, $arguments);
    }

    private function loadClubsById(array $ids): array
    {
        return $this->keyById(
            $this->filterByIds($this->clubs, $ids)
        );
    }

    private function paginate(array $data, array $arguments): ?array
    {
        ['limit' => $limit, 'page' => $page] = $arguments;
        $offset = ($page - 1) * $limit;

        if ($offset > count($data)) {
            return null;
        }

        return array_slice($data, $offset, $limit);
    }

    private function keyById(array $array): array {
        return $this->keyBy($array, 'id');
    }

    private function keyBy(array $array, mixed $id): array {
        return Arrays::mapWithKeys($array, fn($key, array $item): array => [$item[$id], $item]);
    }

    private function filterByIds(array $data, array $ids): array
    {
        return array_filter($data, fn(array $item): bool => in_array($item['id'], $ids, true));
    }
}