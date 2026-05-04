<?php

use Althinect\FilamentSpatieRolesPermissions\Resources\PermissionResource;
use Althinect\FilamentSpatieRolesPermissions\Resources\RoleResource;

return [
    'includes' => [
        // App\Filament\Resources\Blog\AuthorResource::class,
    ],
    'excludes' => [
        PermissionResource::class,
        RoleResource::class,
    ],
    'should_convert_count' => true,
    'enable_convert_tooltip' => true,
];
