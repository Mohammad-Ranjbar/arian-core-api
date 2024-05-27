<?php

namespace App\Console\Commands;

use App\Jobs\MoveToSaleOnlineApplicationCommand;
use App\Models\SaleCompany;
use App\Services\Erp\ErpOdataClient;
use App\Services\SMBService;
use Exception;
use Icewind\SMB\Exception\DependencyException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class SyncInvitationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-invitations-command';

    /**
     * Execute the console command.
     * @throws DependencyException
     */
    public function handle(): void
    {

        $share = new SMBService(
            config('erp.share_server'),
            config('erp.auth')[0],
            config('erp.auth')[1],
            config('erp.workgroups'),
            config('erp.share_folder')
        );
        $companies = SaleCompany::query()->get();
        foreach ($companies as $company) {
            try {
                $files = $share->getFiles($company->getAttribute('folder_name').'/Invitation');
            } catch (Exception $exception) {
                logger($exception->getMessage());
                $files = [];
            }
            foreach ($files as $file) {
                try {
                    $content = $share->getFile($file->getPath());
                    $path = uniqid().$file->getName();
                    Storage::put($path, $content);
                    $reference = explode('.', $file->getName())[0];


                    dispatch(new MoveToSaleOnlineApplicationCommand([
                        'path'       => $path,
                        'reference'  => $reference,
                        'company_id' => $company->id
                    ]));

                    fclose($content);
                    $share->remove($file->getPath());

                } catch (Exception $exception) {
                    logger($exception->getMessage());
                }
            }
        }
    }
}
