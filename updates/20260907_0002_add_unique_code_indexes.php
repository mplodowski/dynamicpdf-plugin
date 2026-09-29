<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Renatio\DynamicPDF\Models\Layout;

return new class extends Migration
{
    protected const TEMPLATES = 'renatio_dynamicpdf_pdf_templates';

    protected const LAYOUTS = 'renatio_dynamicpdf_pdf_layouts';

    /**
     * Only the deduplication is transactional: MySQL commits implicitly on ALTER TABLE.
     */
    public function up()
    {
        DB::transaction(function () {
            $this->deduplicateTemplates();
            $this->deduplicateLayouts();
        });

        foreach ([self::TEMPLATES, self::LAYOUTS] as $name) {
            $indexes = $this->codeIndexes($name);

            Schema::table($name, function (Blueprint $table) use ($indexes) {
                if (! isset($indexes['unique'])) {
                    $table->unique('code');
                }

                if (isset($indexes['plain'])) {
                    $table->dropIndex($indexes['plain']);
                }
            });
        }
    }

    public function down()
    {
        foreach ([self::TEMPLATES, self::LAYOUTS] as $name) {
            $indexes = $this->codeIndexes($name);

            Schema::table($name, function (Blueprint $table) use ($indexes) {
                if (isset($indexes['unique'])) {
                    $table->dropUnique($indexes['unique']);
                }

                if (! isset($indexes['plain'])) {
                    $table->index('code');
                }
            });
        }
    }

    /**
     * Looked up by column, not by name: a table prefix with prefix_indexes renames the indexes.
     *
     * @return array{unique?: string, plain?: string}
     */
    protected function codeIndexes(string $table): array
    {
        return collect(Schema::getIndexes($table))
            ->where('columns', ['code'])
            ->mapWithKeys(fn (array $index) => [$index['unique'] ? 'unique' : 'plain' => $index['name']])
            ->all();
    }

    /**
     * Concurrent syncs could insert the same code twice. The customised row wins, then the oldest.
     */
    protected function deduplicateTemplates(): void
    {
        foreach ($this->duplicateCodes(self::TEMPLATES) as $code) {
            [$keep, $losers] = $this->splitDuplicates(self::TEMPLATES, $code, 'is_custom');

            DB::table(self::TEMPLATES)->whereIn('id', $losers)->delete();

            $this->logRemoval('template', $code, $keep, $losers);
        }
    }

    /**
     * The locked row wins, then the oldest; templates using a removed copy follow the kept one
     * and the copies are deleted through the model so their attachments go with them.
     */
    protected function deduplicateLayouts(): void
    {
        foreach ($this->duplicateCodes(self::LAYOUTS) as $code) {
            [$keep, $losers] = $this->splitDuplicates(self::LAYOUTS, $code, 'is_locked');

            DB::table(self::TEMPLATES)->whereIn('layout_id', $losers)->update(['layout_id' => $keep]);

            Layout::whereIn('id', $losers)->get()->each->delete();

            $this->logRemoval('layout', $code, $keep, $losers);
        }
    }

    /**
     * @return array{0: int, 1: array<int, int>}
     */
    protected function splitDuplicates(string $table, string $code, string $priorityColumn): array
    {
        $keep = (int) DB::table($table)->where('code', $code)->orderByDesc($priorityColumn)->orderBy('id')->value('id');
        $losers = DB::table($table)->where('code', $code)->where('id', '<>', $keep)->pluck('id')->all();

        return [$keep, array_map(intval(...), $losers)];
    }

    /**
     * @param  array<int, int>  $losers
     */
    protected function logRemoval(string $type, string $code, int $keep, array $losers): void
    {
        Log::warning(sprintf(
            'Renatio.DynamicPDF removed duplicate %s rows for code "%s". Kept id %d, deleted ids %s.',
            $type,
            $code,
            $keep,
            implode(', ', $losers),
        ));
    }

    /**
     * @return array<int, string>
     */
    protected function duplicateCodes(string $table): array
    {
        return DB::table($table)->groupBy('code')->havingRaw('COUNT(*) > 1')->pluck('code')->all();
    }
};
