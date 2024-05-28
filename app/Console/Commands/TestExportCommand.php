<?php

namespace App\Console\Commands;

use App\Models\Barcode;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TestExportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-export-command';

    public $handle;
    public $progress;

    public function handle(): void
    {
        $this->handle = fopen('a.csv', 'w');
        fwrite($this->handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
        $query = Barcode::query()->with(['user','vehicle','erpProductItem']);
        $this->progress = $this->output->createProgressBar($query->count() * 3);
        $this->progress->start();
        $query->chunk(10000, function ($rows){
            foreach ($rows as $row) {
                fputcsv($this->handle, [
                    $row->user->name,
                    $row->vin,
                    $row->created_at,
                    $row->code,
                    $row->erpProductItem?->title.null,
                ]);
                $this->progress->advance();
            }
            // 2X
            foreach ($rows as $row) {
                fputcsv($this->handle, [
                    $row->user->name,
                    $row->vin,
                    $row->created_at,
                    $row->code,
                    $row->erpProductItem?->title.null,
                ]);
                $this->progress->advance();
            }
            //3X
            foreach ($rows as $row) {
                fputcsv($this->handle, [
                    $row->user->name,
                    $row->vin,
                    $row->created_at,
                    $row->code,
                    $row->erpProductItem?->title.null,
                ]);
                $this->progress->advance();
            }
        });
        fclose($this->handle);
        $this->progress->finish();
        $this->progress->clear();


        $this->alert('Csv file generated!');
    }
}
