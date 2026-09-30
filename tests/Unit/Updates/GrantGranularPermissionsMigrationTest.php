<?php

use Illuminate\Support\Facades\DB;

describe('grant_granular_permissions', function () {
    beforeEach(function () {
        $this->migrate = fn () => (require plugins_path('renatio/dynamicpdf/updates/20260930_0002_grant_granular_permissions.php'))->up();

        $encode = fn (array|string|null $permissions): ?string => is_array($permissions) ? json_encode($permissions, JSON_THROW_ON_ERROR) : $permissions;

        $this->role = fn (array|string|null $permissions): int => DB::table('backend_user_roles')->insertGetId([
            'name' => uniqid('role'), 'code' => uniqid('role'), 'permissions' => $encode($permissions),
        ]);

        $this->user = fn (array|string|null $permissions): int => DB::table('backend_users')->insertGetId([
            'login' => $login = uniqid('user'), 'email' => "{$login}@example.com", 'password' => 'secret',
            'permissions' => $encode($permissions),
        ]);

        $this->permissions = fn (string $table, int $id): mixed => json_decode((string) DB::table($table)->where('id', $id)->value('permissions'), true);
    });

    it('grants the granular permissions to a role holding the parent one', function () {
        $id = ($this->role)(['general.backend' => 1, 'renatio.dynamicpdf.manage_templates' => 1]);

        ($this->migrate)();
        ($this->migrate)();

        expect(($this->permissions)('backend_user_roles', $id))->toBe([
            'general.backend' => 1,
            'renatio.dynamicpdf.manage_templates' => 1,
            'renatio.dynamicpdf.manage_templates.create' => 1,
            'renatio.dynamicpdf.manage_templates.update' => 1,
            'renatio.dynamicpdf.manage_templates.delete' => 1,
            'renatio.dynamicpdf.manage_templates.preview' => 1,
        ]);
    });

    it('grants nothing once a granular permission is stored, so a role narrowed later is never widened again', function () {
        $narrowed = ($this->role)(['renatio.dynamicpdf.manage_templates' => 1]);
        ($this->role)(['renatio.dynamicpdf.manage_layouts' => 1]);

        ($this->migrate)();

        DB::table('backend_user_roles')->where('id', $narrowed)->update(['permissions' => json_encode([
            'renatio.dynamicpdf.manage_templates' => 1,
        ])]);

        ($this->migrate)();

        expect(($this->permissions)('backend_user_roles', $narrowed))->toBe(['renatio.dynamicpdf.manage_templates' => 1]);
    });

    it('treats a parent stored as true like October does', function () {
        $id = ($this->role)(['renatio.dynamicpdf.manage_layouts' => true]);

        ($this->migrate)();

        expect(($this->permissions)('backend_user_roles', $id))->toHaveKey('renatio.dynamicpdf.manage_layouts.update', 1);
    });

    it('grants to an administrator allowed the parent directly but not to one denied it', function () {
        $allowed = ($this->user)(['renatio.dynamicpdf.manage_layouts' => 1]);
        $denied = ($this->user)(['renatio.dynamicpdf.manage_templates' => -1]);

        ($this->migrate)();

        expect(($this->permissions)('backend_users', $allowed))
            ->toHaveKeys(['renatio.dynamicpdf.manage_layouts.update', 'renatio.dynamicpdf.manage_layouts.preview'])
            ->and(($this->permissions)('backend_users', $denied))->toBe(['renatio.dynamicpdf.manage_templates' => -1]);
    });

    it('leaves roles without the parent permission and unreadable permissions alone', function () {
        $without = ($this->role)(['general.backend' => 1]);
        $ids = array_map($this->role, [null, '', 'not json', '"renatio.dynamicpdf.manage_templates"']);

        ($this->migrate)();

        expect(DB::table('backend_user_roles')->whereIn('id', $ids)->orderBy('id')->pluck('permissions')->all())
            ->toBe([null, '', 'not json', '"renatio.dynamicpdf.manage_templates"'])
            ->and(($this->permissions)('backend_user_roles', $without))->toBe(['general.backend' => 1]);
    });
});
