<?php

declare(strict_types=1);

namespace Wiz\FilamentMenuBuilder\Models;

use Illuminate\Support\Facades\Cache;
use Wiz\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string language
 * @property string location
 * @property bool $is_visible
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\Wiz\FilamentMenuBuilder\Models\MenuLocation[] $locations
 * @property-read int|null $locations_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Wiz\FilamentMenuBuilder\Models\MenuItem[] $menuItems
 * @property-read int|null $menuItems_count
 */
class Menu extends Model
{

    protected $guarded = [];

    protected static function booted(): void
    {
        static::deleted(function (self $menu) {
            MenuItem::where('menu_id', $menu->id)->delete();
        });
        static::updating(function (self $menu) {
            if ($menu->location) {
                Menu::where('location', $menu->location)->where('language', $menu->language)->update(['location' => null]);
            }
        });
        static::creating(function (self $menu) {
            if ($menu->location) {
                Menu::where('location', $menu->location)->where('language', $menu->language)->update(['location' => null]);
            }
        });
    }


    public function getTable(): string
    {
        return config('filament-menu-builder.tables.menus', parent::getTable());
    }

    protected function casts(): array
    {
        return [
            'is_visible' => 'bool',
        ];
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class)
            ->whereNull('parent_id')
            ->orderBy('parent_id')
            ->orderBy('order')
            ->with('children');
    }

    public static function getMenuByLocation(string $location, $language = 'en')
    {
        /*return self::query()
            ->where('is_visible', true)
            ->where('location', $location)
            ->where('language', $language)
            ->with('menuItems:id,menu_id,title,url,target,parent_id,style_class,icon')
            ->first()?->menuItems;*/

        return Cache::remember('menuCached:' . $location . '__' . $language, 60 * 24, function () use ($location, $language) {
            return self::query()
                ->where('is_visible', true)
                ->where('location', $location)
                ->where('language', $language)
                ->with('menuItems')
                ->first()?->menuItems;
        });
    }
}
