<?php

use Illuminate\Support\Facades\DB;
use October\Rain\Database\Updates\Migration;

/**
 * Before 8.1.0 the parent permission alone allowed create, update, delete and preview, so roles and administrators
 * granted it keep that access. Granular permissions already set, including a deny, are never overridden.
 */
return new class extends Migration
{
    protected const TABLES = ['backend_user_roles', 'backend_users'];

    protected const PARENTS = ['renatio.dynamicpdf.manage_templates', 'renatio.dynamicpdf.manage_layouts'];

    protected const CHILDREN = ['create', 'update', 'delete', 'preview'];

    public function up()
    {
        foreach (self::TABLES as $table) {
            DB::table($table)
                ->where('permissions', 'like', '%renatio.dynamicpdf.manage_%')
                ->get(['id', 'permissions'])
                ->each(function (object $row) use ($table) {
                    $permissions = json_decode((string) $row->permissions, true);

                    if (! is_array($permissions)) {
                        return;
                    }

                    $granted = $this->grant($permissions);

                    if ($granted !== $permissions) {
                        DB::table($table)->where('id', $row->id)->update(['permissions' => json_encode($granted)]);
                    }
                });
        }
    }

    /**
     * No-op, the granted permissions cannot be told apart from those an administrator set by hand.
     */
    public function down()
    {
    }

    /**
     * @param  array<array-key, mixed>  $permissions
     * @return array<array-key, mixed>
     */
    protected function grant(array $permissions): array
    {
        foreach (self::PARENTS as $parent) {
            if (! in_array($permissions[$parent] ?? null, [1, '1'], true)) {
                continue;
            }

            foreach (self::CHILDREN as $child) {
                if (! array_key_exists("{$parent}.{$child}", $permissions)) {
                    $permissions["{$parent}.{$child}"] = 1;
                }
            }
        }

        return $permissions;
    }
};
