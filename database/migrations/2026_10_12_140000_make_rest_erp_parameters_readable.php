<?php

use App\Casts\ReadableRestErpParameters;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->convert(false);
    }

    public function down(): void
    {
        $this->convert(true);
    }

    private function convert(bool $encrypt): void
    {
        DB::table('rest_erp_jobs')->select(['id', 'parameters'])->orderBy('id')
            ->chunkById(100, function ($rows) use ($encrypt) {
                DB::transaction(function () use ($rows, $encrypt) {
                    foreach ($rows as $row) {
                        $parameters = ReadableRestErpParameters::decode($row->parameters);
                        if ($parameters === null) {
                            throw new RuntimeException('Cannot decode parameters for RestErpJob #'.$row->id.'. Keep the original APP_KEY and inspect this record.');
                        }
                        $json = json_encode($parameters, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        DB::table('rest_erp_jobs')->where('id', $row->id)->update([
                            'parameters' => $encrypt ? Crypt::encryptString($json) : $json,
                        ]);
                    }
                });
            });
    }
};
