<?php

declare(strict_types=1);

namespace Wiz\FilamentMenuBuilder\Concerns;

use Filament\Forms\Components\Tabs;
use Wiz\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Filament\Actions\Action;
use Filament\Forms\Components;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Collection;

trait HasLocationAction
{
    protected ?Collection $menus = null;

    protected ?Collection $menuLocations = null;

    public function getLocationAction(): Action
    {

        $tabs = Tabs::make('setting')
            ->tabs(
                collect(config('lang.content_languages'))->map(function ($language)  {
                    return Tabs\Tab::make(zi_language($language['code']))
                        ->schema($this->getRegisteredLocations()->map(
                            fn($location, $key) => Components\Grid::make(2)
                                ->statePath($key)
                                ->schema([
                                    Components\TextInput::make("{$language['code']}.location")
                                        ->label(__('filament-menu-builder::menu-builder.actions.locations.form.location.label'))
                                        ->hiddenLabel($key !== $this->getRegisteredLocations()->keys()->first())
                                        ->disabled(),

                                    Components\Select::make("{$language['code']}.menu")
                                        ->label(__('filament-menu-builder::menu-builder.actions.locations.form.menu.label'))
                                        ->searchable()
                                        ->hiddenLabel($key !== $this->getRegisteredLocations()->keys()->first())
                                        ->options($this->getMenus()->where('language',$language['code'])->pluck('name', 'id')->all()),

                                ]),
                        )->all() ?: [
                            Components\View::make('filament-tables::components.empty-state.index')
                                ->viewData([
                                    'heading' => __('filament-menu-builder::menu-builder.actions.locations.empty.heading'),
                                    'icon' => 'heroicon-o-x-mark',
                                ]),
                        ]);
                })->toArray()
            );

        return Action::make('locations')
            ->label(__('filament-menu-builder::menu-builder.actions.locations.label'))
            ->modalHeading(__('filament-menu-builder::menu-builder.actions.locations.heading'))
            ->modalDescription(__('filament-menu-builder::menu-builder.actions.locations.description'))
            ->modalSubmitActionLabel(__('filament-menu-builder::menu-builder.actions.locations.submit'))
            //->modalWidth(MaxWidth::TwoExtraLarge)
            ->modalSubmitAction($this->getRegisteredLocations()->isEmpty() ? false : null)
            ->color('gray')
            ->fillForm(function () {
                $locations = $this->getMenuLocations();

                $data = [];

                foreach ($this->getRegisteredLocations() as $key => $location) {
                    // Khởi tạo cho mỗi location
                    $data[$key] = [
                        'location' => [], // Chứa tên location cho từng ngôn ngữ
                        'menu' => [],     // Chứa menu ID cho từng ngôn ngữ
                    ];

                    foreach (config('lang.content_languages') as $language) {
                        $langCode = $language['code'];

                        // Lấy menu tương ứng với location và ngôn ngữ
                        $menuLocation = $locations->get($location, collect())->firstWhere('language_code', $langCode);

                        // Thiết lập giá trị cho location và menu
                        $data[$key]['location'][$langCode] = $location; // Tên location
                        $data[$key]['menu'][$langCode] = $menuLocation ? $menuLocation->menu_id : null; // ID menu nếu có
                    }
                }

                return $data;
            })
            ->action(function (array $data) {
                dump($data);

                $locations = collect($data)
                    ->map(fn($item) => $item['menu'] ?? null)
                    ->all();

                $this->getMenuLocations()->each->delete();
                foreach ($locations as $location => $menuHaveLang) {

                    if ($menuHaveLang) {
                        foreach ($menuHaveLang as $langKey => $menu) {
                            if($menu) {
                                FilamentMenuBuilderPlugin::get()->getMenuLocationModel()::updateOrCreate(
                                    ['location' => $location],
                                    ['menu_id' => $menu],
                                );
                            }
                        }
                    }
                }

                Notification::make()
                    ->title(__('filament-menu-builder::menu-builder.notifications.locations.title'))
                    ->success()
                    ->send();
            })
            ->form([$tabs]);
    }

    protected function getMenus(): Collection
    {
        return $this->menus ??= FilamentMenuBuilderPlugin::get()->getMenuModel()::all();
    }

    protected function getMenuLocations(): Collection
    {
        return $this->menuLocations ??= FilamentMenuBuilderPlugin::get()->getMenuLocationModel()::all();
    }

    protected function getRegisteredLocations(): Collection
    {
        return collect(FilamentMenuBuilderPlugin::get()->getLocations());
    }
}
