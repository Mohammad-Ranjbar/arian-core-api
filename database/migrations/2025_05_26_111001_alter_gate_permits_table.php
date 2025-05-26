<?php

use App\Models\GatePermit;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {

    public function up(): void
    {
        $rows = GatePermit::query()->whereNull('source_id')->get();

        foreach ($rows as $row) {
            $row->update(['source_id' => 1]);
        }
    }

    public function down(): void
    {

    }
};
