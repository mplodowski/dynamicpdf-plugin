<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use October\Rain\Database\Updates\Migration;

/**
 * Grants the granular permissions to everyone who had the parent one, unless any granular permission is already stored.
 */
return new class extends Migration
{
    protected const TABLES = ['backend_user_roles', 'backend_users'];

    protected const PARENTS = ['renatio.dynamicpdf.manage_templates', 'renatio.dynamicpdf.manage_layouts'];

    protected const CHILDREN = ['create', 'update', 'delete', 'preview'];

    public function up()
    {
        $rows = collect(self::TABLES)->mapWithKeys(fn (string $table) => [$table => $this->rows($table)]);

        if ($rows->flatten(1)->contains(fn (array $permissions) => $this->hasGranular($permissions))) {
            return;
        }

        $rows->each(fn (Collection $permissions, string $table) => $permissions->each(
            function (array $permissions, int $id) use ($table) {
                $granted = $this->grant($permissions);

                if ($granted !== $permissions) {
                    DB::table($table)->where('id', $id)->update(['permissions' => json_encode($granted)]);
                }
            },
        ));
    }

    /**
     * No-op, the granted permissions cannot be told apart from those an administrator set by hand.
     */
    public function down()
    {
    }

    /**
     * @return Collection<int, array<array-key, mixed>>
     */
    protected function rows(string $table): Collection
    {
        return DB::table($table)
            ->where('permissions', 'like', '%renatio.dynamicpdf.manage_%')
            ->pluck('permissions', 'id')
            ->map(fn (mixed $permissions) => json_decode((string) $permissions, true))
            ->filter(fn (mixed $permissions) => is_array($permissions));
    }

    /**
     * @param  array<array-key, mixed>  $permissions
     */
    protected function hasGranular(array $permissions): bool
    {
        foreach (self::PARENTS as $parent) {
            foreach (self::CHILDREN as $child) {
                if (array_key_exists("{$parent}.{$child}", $permissions)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  array<array-key, mixed>  $permissions
     * @return array<array-key, mixed>
     */
    protected function grant(array $permissions): array
    {
        foreach (self::PARENTS as $parent) {
            $value = $permissions[$parent] ?? null;

            if (! is_scalar($value) || (int) $value !== 1) {
                continue;
            }

            foreach (self::CHILDREN as $child) {
                $permissions["{$parent}.{$child}"] = 1;
            }
        }

        return $permissions;
    }
};
