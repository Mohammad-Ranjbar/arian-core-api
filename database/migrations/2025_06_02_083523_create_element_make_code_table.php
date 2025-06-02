<?php

use App\Models\Element;
use App\Models\MakeCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('element_make_code', function (Blueprint $table) {
            $table->foreignId('element_id')->constrained()->cascadeOnDelete();
            $table->foreignId('make_code_id')->constrained()->cascadeOnDelete();
            $table->unique(['element_id', 'make_code_id']);
        });

        Element::query()->each(function ($element) {
            $element->makeCodes()->sync(MakeCode::query()->where('reference','!=','XGMA')->pluck('id')->toArray());
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('element_make_code');
    }
};
