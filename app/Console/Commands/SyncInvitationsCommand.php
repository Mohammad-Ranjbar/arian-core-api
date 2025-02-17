<?php

namespace App\Console\Commands;

use App\Jobs\MoveContractToSaleOnlineApplicationJob;
use App\Jobs\MoveInvitationToSaleOnlineApplicationJob;
use App\Models\SaleCompany;
use App\Services\SMBService;
use Exception;
use Icewind\SMB\Exception\DependencyException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SyncInvitationsCommand extends Command
{

    public $share;
    protected $signature = 'app:sync-invitations-command';

    /**
     * @throws DependencyException
     */
    public function handle(): void
    {
        $this->share = new SMBService(
            config('erp.share_server'),
            config('erp.auth')[0],
            config('erp.auth')[1],
            config('erp.workgroups'),
            config('erp.share_folder')
        );
        $companies = SaleCompany::query()->get();
        foreach ($companies as $company) {
            if (!empty($company->getAttribute('invitation'))) {
                $this->syncInvitation($company);
            }
            if (!empty($company->getAttribute('contract'))) {
                $this->syncContract($company);
            }
        }
    }

    private function syncInvitation(SaleCompany $company): void
    {

        try {
            $files = $this->share->getFiles($company->getAttribute('folder_name').'/'.$company->getAttribute('invitation'));
        } catch (Exception $exception) {
            logger($exception->getMessage());
            $files = [];
        }
        foreach ($files as $file) {
            try {
                $content = $this->share->getFile($file->getPath());
                $path = uniqid().$file->getName();
                Storage::put($path, $content);
                $reference = explode('.', $file->getName())[0];

                fclose($content);
                $fileName = Storage::disk('s3')->putFile('/invitations/'.$company->getAttribute('id'),
                    Storage::path($path));

                if ($fileName) {
                    $invoice = $company->invoices()->create([
                        'reference' => $reference,
                        'type'      => 'customer-finance',
                        'value'     => 0,
                        'data'      => [
                            'local_file' => $path,
                            'file'       => $fileName
                        ]
                    ]);
                    Storage::delete($path);
                    $this->share->remove($file->getPath());
                    dispatch(new MoveInvitationToSaleOnlineApplicationJob($invoice->getAttribute('id')));
                }


            } catch (Exception $exception) {
                logger($exception->getMessage());
            }
        }
    }

    private function syncContract(SaleCompany $company): void
    {

        try {
            $files = $this->share->getFiles($company->getAttribute('folder_name').'/'.$company->getAttribute('contract'));
        } catch (Exception $exception) {
            logger($exception->getMessage());
            logger($company->getAttribute('folder_name').'/'.$company->getAttribute('contract'));
            $files = [];
        }
        foreach ($files as $file) {
            try {
                $content = $this->share->getFile($file->getPath());
                $path = uniqid().$file->getName();
                Storage::put($path, $content);
                $reference = explode('.', $file->getName())[0];


                dispatch(new MoveContractToSaleOnlineApplicationJob([
                    'path'       => $path,
                    'reference'  => $reference,
                    'company_id' => $company->getAttribute('id'),
                    'shareFile'  => $file->getPath()
                ]));

                fclose($content);


            } catch (Exception $exception) {
                logger($exception->getMessage());
            }
        }
    }
}
