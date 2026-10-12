<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

return new class extends Migration
{
    protected const LAYOUTS = 'renatio_dynamicpdf_pdf_layouts';

    public function up(): void
    {
        $this->invertColumn('is_locked', 'is_custom', true);
    }

    public function down(): void
    {
        $this->invertColumn('is_custom', 'is_locked', false);
    }

    protected function invertColumn(string $from, string $to, bool $default): void
    {
        if (! Schema::hasColumn(self::LAYOUTS, $to)) {
            Schema::table(self::LAYOUTS, function (Blueprint $table) use ($to, $default) {
                $table->boolean($to)->default($default);
            });
        }

        if (! Schema::hasColumn(self::LAYOUTS, $from)) {
            return;
        }

        DB::table(self::LAYOUTS)->where($from, true)->update([$to => false]);
        DB::table(self::LAYOUTS)->where($from, false)->update([$to => true]);

        Schema::table(self::LAYOUTS, function (Blueprint $table) use ($from) {
            $table->dropColumn($from);
        });
    }
};
