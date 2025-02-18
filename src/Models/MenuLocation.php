<?php

declare(strict_types=1);

namespace Datlechin\FilamentMenuBuilder\Models;

use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property int $menu_id
 * @property string $location
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Datlechin\FilamentMenuBuilder\Models\Menu $menu
 */
class MenuLocation extends Model
{
    use ClearCache;
    protected $guarded = [];

    public function getTable(): string
    {
        return config('filament-menu-builder.tables.menu_locations', parent::getTable());
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(FilamentMenuBuilderPlugin::get()->getMenuModel());
    }


    /**
     * add function witch cache
     * @param $location
     * @return array|mixed
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    static function getMenuByLocation($location)
    {
        try {
            $cacheKey = "zi_cache:menu:{$location}";
            if (request()->get('cache')) {
                Cache::forget($cacheKey);
            }
            return Cache::remember($cacheKey, 3600 * 24, function () use ($location) {
                return FilamentMenuBuilderPlugin::get()
                    ->getMenuLocationModel()::with(['menu' => fn(Builder $query) => $query->where('is_visible', true)->with('menuItems')])
                    ->where('location', $location)
                    ->first()?->menu;
            });
        } catch (\Exception $exception) {
            return [];
        }

    }

    static function clearCache(): void
    {

        try {
            $locations = FilamentMenuBuilderPlugin::get()->getLocations();
            foreach ($locations as $location) {
                $cacheKey = "zi_cache:menu:{$location}";
                Cache::forget($cacheKey);
            }
        } catch (\Exception $exception) {

        }

    }
}
