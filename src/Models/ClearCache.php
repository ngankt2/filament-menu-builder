<?php

namespace Wiz\FilamentMenuBuilder\Models;

Trait ClearCache
{
    protected static function booted()
    {
        parent::booted();
        MenuLocation::clearCache();
    }
}
