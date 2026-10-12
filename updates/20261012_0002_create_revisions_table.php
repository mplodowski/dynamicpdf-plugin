<?php

use Illuminate\Support\Facades\Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('renatio_dynamicpdf_revisions', function (Blueprint $table) {
            $table->id();
            $table->morphs('revisionable', 'renatio_dynamicpdf_revisions_revisionable_index');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->mediumText('data');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('renatio_dynamicpdf_revisions');
    }
};
