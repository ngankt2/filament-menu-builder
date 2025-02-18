<?php

namespace Datlechin\FilamentMenuBuilder\Models;

Trait ClearCache
{
    protected static function booted()
    {
        parent::booted();
        MenuLocation::clearCache();
    }
}
