<?php

use App\Models\User;
use Phpsa\FilamentAuthentication\Pages\Profile;
use Phpsa\FilamentAuthentication\Resources\PermissionResource;
use Phpsa\FilamentAuthentication\Resources\RoleResource;
use Phpsa\FilamentAuthentication\Resources\UserResource;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return [
    'models' => [
        'User' => User::class,
        'Role' => Role::class,
        'Permission' => Permission::class,
    ],
    'resources' => [
        'UserResource' => UserResource::class,
        'RoleResource' => RoleResource::class,
        'PermissionResource' => PermissionResource::class,
    ],
    'pages' => [
        'Profile' => Profile::class,
    ],
    'Widgets' => [
        'LatestUsers' => [
            'enabled' => true,
            'limit' => 5,
            'sort' => 0,
            'paginate' => false,
        ],
    ],
    'preload_roles' => true,
    'impersonate' => [
        'enabled' => true,
        'guard' => 'web',
        'redirect' => '/',
    ],
];
