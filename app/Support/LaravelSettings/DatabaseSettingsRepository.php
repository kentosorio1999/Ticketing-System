<?php

namespace App\Support\LaravelSettings;

use Spatie\LaravelSettings\SettingsRepositories\DatabaseSettingsRepository as SpatieDatabaseSettingsRepository;
use Symfony\Component\Console\Input\ArgvInput;

class DatabaseSettingsRepository extends SpatieDatabaseSettingsRepository
{
    public function getPropertiesInGroup(string $group): array
    {
        if (in_array(
            app(ArgvInput::class)->getFirstArgument(),
            ['migrate', 'package:discover', 'filament:upgrade', 'key:generate']
        )
        ) {
            return [];
        }

        return parent::getPropertiesInGroup($group);
    }
}
