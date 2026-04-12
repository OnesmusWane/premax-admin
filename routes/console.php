<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('access-control:backfill-users {--email=* : Limit the sync to one or more user email addresses} {--dry-run : Show what would change without writing to the database}', function () {
    $dryRun = (bool) $this->option('dry-run');
    $emails = collect((array) $this->option('email'))
        ->filter()
        ->map(fn (string $email) => mb_strtolower(trim($email)))
        ->values();

    app(AccessControlSeeder::class)->run();

    $roleMap = Role::query()
        ->get(['id', 'slug'])
        ->keyBy('slug');

    $users = User::query()
        ->with(['employee', 'roles'])
        ->when($emails->isNotEmpty(), fn ($query) => $query->whereIn('email', $emails))
        ->orderBy('id')
        ->get();

    if ($users->isEmpty()) {
        $this->warn('No matching users found.');
        return 0;
    }

    $rows = [];
    $attached = 0;
    $alreadyLinked = 0;
    $skipped = 0;

    foreach ($users as $user) {
        $legacyRole = $user->employee?->role ?: $user->getRawOriginal('role');
        $normalizedRole = match ($legacyRole) {
            'staff' => 'receptionist',
            'manager' => 'admin',
            default => $legacyRole,
        };

        if (! $normalizedRole) {
            $rows[] = [$user->email, '-', 'skipped', 'No legacy role found on user or employee record'];
            $skipped++;
            continue;
        }

        $role = $roleMap->get($normalizedRole);

        if (! $role) {
            $rows[] = [$user->email, $normalizedRole, 'skipped', 'Role slug not found in access control config'];
            $skipped++;
            continue;
        }

        if ($user->roles->contains(fn ($assignedRole) => $assignedRole->id === $role->id)) {
            $rows[] = [$user->email, $normalizedRole, 'unchanged', 'Role already linked'];
            $alreadyLinked++;
            continue;
        }

        if (! $dryRun) {
            $user->roles()->syncWithoutDetaching([$role->id]);

            if ($user->getRawOriginal('role') !== $normalizedRole) {
                $user->updateQuietly(['role' => $normalizedRole]);
            }
        }

        $rows[] = [
            $user->email,
            $normalizedRole,
            $dryRun ? 'preview' : 'linked',
            $dryRun ? 'Role would be attached via role_user' : 'Role linked via role_user',
        ];
        $attached++;
    }

    $this->table(['Email', 'Resolved Role', 'Status', 'Notes'], $rows);

    $this->newLine();
    $this->info(sprintf(
        'Processed %d user(s): %d %s, %d already linked, %d skipped.',
        $users->count(),
        $attached,
        $dryRun ? 'previewed' : 'updated',
        $alreadyLinked,
        $skipped,
    ));

    if ($dryRun) {
        $this->comment('Dry run only. Re-run without --dry-run to persist role links.');
    } else {
        $this->comment('Roles are now linked. Effective permissions will flow from each assigned role.');
    }

    return 0;
})->purpose('Backfill role links for existing users so role-based permissions work');
