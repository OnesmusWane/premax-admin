<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        $roleMap = collect(config('access_control.roles'))
            ->mapWithKeys(function (array $role) {
                return [
                    $role['slug'] => Role::query()->updateOrCreate(
                        ['slug' => $role['slug']],
                        [
                            'name' => $role['name'],
                            'description' => $role['description'] ?? null,
                        ]
                    ),
                ];
            });

        $permissionMap = collect(config('access_control.permissions'))
            ->mapWithKeys(function (array $permission) {
                return [
                    $permission['slug'] => Permission::query()->updateOrCreate(
                        ['slug' => $permission['slug']],
                        [
                            'name' => $permission['name'],
                            'group_name' => $permission['group'] ?? null,
                            'description' => $permission['description'] ?? null,
                        ]
                    ),
                ];
            });

        foreach (config('access_control.role_permissions') as $roleSlug => $permissionSlugs) {
            $role = $roleMap[$roleSlug] ?? null;

            if (! $role) {
                continue;
            }

            $permissions = in_array('*', $permissionSlugs, true)
                ? $permissionMap->pluck('id')->all()
                : $permissionMap->only($permissionSlugs)->pluck('id')->all();

            $role->permissions()->sync($permissions);
        }

        User::query()->with(['roles', 'employee'])->get()->each(function (User $user) use ($roleMap) {
            if ($user->roles->isNotEmpty()) {
                return;
            }

            $legacyRole = $user->employee?->role ?: $user->getRawOriginal('role') ?: 'receptionist';
            $normalized = match ($legacyRole) {
                'staff' => 'receptionist',
                'manager' => 'admin',
                default => $legacyRole,
            };

            if ($roleMap->has($normalized)) {
                $user->roles()->syncWithoutDetaching([$roleMap[$normalized]->id]);
                $user->updateQuietly(['role' => $normalized]);
            }
        });
    }
}
