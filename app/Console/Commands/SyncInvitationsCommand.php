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

    protected $signature = 'app:sync-invitations-command';

    public $share;


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
            if(!empty($company->getAttribute('invitation'))){
                $this->syncInvitation($company);
            }
            if(!empty($company->getAttribute('contract'))){
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


                dispatch(new MoveInvitationToSaleOnlineApplicationJob([
                    'path'       => $path,
                    'reference'  => $reference,
                    'company_id' => $company->id
                ]));

                fclose($content);
                $this->share->remove($file->getPath());

            } catch (Exception $exception) {
                logger($exception->getMessage());
            }
        }
    }

    private function syncContract(SaleCompany $company)
    {

        try {
            $files = $this->share->getFiles($company->getAttribute('folder_name').'/'.$company->getAttribute('contract'));
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


                dispatch(new MoveContractToSaleOnlineApplicationJob([
                    'path'       => $path,
                    'reference'  => $reference,
                    'company_id' => $company->id
                ]));

                fclose($content);
                $this->share->remove($file->getPath());

            } catch (Exception $exception) {
                logger($exception->getMessage());
            }
        }
    }
}
