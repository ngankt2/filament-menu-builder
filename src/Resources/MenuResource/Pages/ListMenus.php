<?php

declare(strict_types=1);

namespace Wiz\FilamentMenuBuilder\Resources\MenuResource\Pages;

use Wiz\FilamentMenuBuilder\Concerns\HasLocationAction;
use Wiz\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMenus extends ListRecords
{
    use HasLocationAction;

    public static function getResource(): string
    {
        return FilamentMenuBuilderPlugin::get()->getResource();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            $this->getLocationAction(),
        ];
    }
}
