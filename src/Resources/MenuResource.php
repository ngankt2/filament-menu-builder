<?php

declare(strict_types=1);

namespace Wiz\FilamentMenuBuilder\Resources;

use Wiz\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Filament\Forms\Components;
use Filament\Forms\Components\Component;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class MenuResource extends Resource
{
    public static function getModel(): string
    {
        return FilamentMenuBuilderPlugin::get()->getMenuModel();
    }

    public static function getNavigationLabel(): string
    {
        return FilamentMenuBuilderPlugin::get()->getNavigationLabel() ?? Str::title(static::getPluralModelLabel()) ?? Str::title(static::getModelLabel());
    }

    public static function getNavigationIcon(): string
    {
        return FilamentMenuBuilderPlugin::get()->getNavigationIcon();
    }

    public static function getNavigationSort(): ?int
    {
        return FilamentMenuBuilderPlugin::get()->getNavigationSort();
    }

    public static function getNavigationGroup(): ?string
    {
        return FilamentMenuBuilderPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationBadge(): ?string
    {
        return FilamentMenuBuilderPlugin::get()->getNavigationCountBadge() ? number_format(static::getModel()::count()) : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Components\Grid::make(12)
                    ->schema([
                        Components\Group::make([
                            Components\TextInput::make('name')
                                ->label(__('filament-menu-builder::menu-builder.resource.name.label'))
                                ->required(),

                            Components\Select::make('language')
                                ->required()
                                ->label(__('filament-menu-builder::menu-builder.form.language'))
                                ->options(collect(config('lang.content_languages'))->pluck('name', 'code')),

                            Components\Select::make('location')
                                ->label(__('filament-menu-builder::menu-builder.actions.locations.form.location.label'))
                                ->options(FilamentMenuBuilderPlugin::get()->getLocations()),

                        ])->columnSpan(8),
                        Components\ToggleButtons::make('is_visible')
                            ->grouped()
                            ->options([
                                true  => __('filament-menu-builder::menu-builder.resource.is_visible.visible'),
                                false => __('filament-menu-builder::menu-builder.resource.is_visible.hidden'),
                            ])
                            ->colors([
                                true  => 'primary',
                                false => 'danger',
                            ])
                            ->required()
                            ->label(__('filament-menu-builder::menu-builder.resource.is_visible.label'))
                            ->default(true),
                    ]),

                Components\Group::make()
                    ->visible(fn(Component $component) => $component->evaluate(FilamentMenuBuilderPlugin::get()->getMenuFields()) !== [])
                    ->schema(FilamentMenuBuilderPlugin::get()->getMenuFields()),
            ]);
    }

    public static function table(Table $table): Table
    {
        $locations = FilamentMenuBuilderPlugin::get()->getLocations();

        return $table
            ->modifyQueryUsing(fn($query) => $query->withCount('menuItems'))
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->searchable()
                    ->sortable()
                    ->label(__('ID')),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label(__('filament-menu-builder::menu-builder.resource.name.label')),

                Tables\Columns\TextColumn::make('language')
                    ->formatStateUsing(fn(string $state) => zi_language($state))
                    ->sortable(),

                Tables\Columns\TextColumn::make('location')
                    ->formatStateUsing(fn(string $state) => !empty($locations[$state])? ($locations[$state] . " ({$state})") : $state)
                    ->badge()
                    ->label(__('filament-menu-builder::menu-builder.resource.locations.label'))
                    ->default(__('filament-menu-builder::menu-builder.resource.locations.empty'))
                    ->color(fn(string $state) => array_key_exists($state, $locations) ? 'primary' : 'gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('menu_items_count')
                    ->label(__('filament-menu-builder::menu-builder.resource.items.label'))
                    ->icon('heroicon-o-link')
                    ->numeric()
                    ->default(0)
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_visible')
                    ->label(__('filament-menu-builder::menu-builder.resource.is_visible.label'))
                    ->sortable()
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => MenuResource\Pages\ListMenus::route('/'),
            'edit'  => MenuResource\Pages\EditMenu::route('/{record}/edit'),
        ];
    }
}
