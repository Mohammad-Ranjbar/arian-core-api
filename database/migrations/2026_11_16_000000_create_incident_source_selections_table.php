<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_source_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_source_id')->constrained()->cascadeOnDelete();
            $table->morphs('model');
            // The original records do not identify their author.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['incident_source_id', 'model_type', 'model_id'], 'incident_source_selections_unique');
        });

        DB::table('incident_sources')->whereNotNull('model_type')->whereNotNull('model_id')
            ->orderBy('id')->chunkById(500, function ($sources) {
                DB::table('incident_source_selections')->insert($sources->map(fn ($source) => [
                    'incident_source_id' => $source->id,
                    'model_type' => $source->model_type,
                    'model_id' => $source->model_id,
                    'user_id' => null,
                    'created_at' => $source->created_at,
                    'updated_at' => $source->updated_at,
                ])->all());
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_source_selections');
    }
};
