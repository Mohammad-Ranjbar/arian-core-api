<?php

use App\Models\Incident;
use App\Models\MakeCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('incident_make_code', function (Blueprint $table) {
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('make_code_id')->constrained()->cascadeOnDelete();
            $table->unique(['incident_id', 'make_code_id']);
        });

        Incident::query()->each(function ($element) {
            $element->makeCodes()->sync(MakeCode::query()->where('reference','!=','XGMA')->pluck('id')->toArray());
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_make_code');
    }
};
