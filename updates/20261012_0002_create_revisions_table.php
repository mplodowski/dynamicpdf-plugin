<?php

use Illuminate\Support\Facades\Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

return new class extends Migration
{
    /**
     * Guarded because the script shipped as 8.1.0 before moving to 9.0.0, so an install that ran it there runs it
     * again.
     */
    public function up(): void
    {
        if (Schema::hasTable('renatio_dynamicpdf_revisions')) {
            return;
        }

        Schema::create('renatio_dynamicpdf_revisions', function (Blueprint $table) {
            $table->id();
            $table->morphs('revisionable', 'renatio_dynamicpdf_revisions_revisionable_index');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->longText('data');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('renatio_dynamicpdf_revisions');
    }
};
