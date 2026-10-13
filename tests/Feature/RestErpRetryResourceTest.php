<?php

namespace Tests\Feature;

use App\Http\Resources\RestErpJobResource;
use App\Http\Resources\RestErpJobSummaryResource;
use App\Models\RestErpJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\CreatesRestErpTables;
use Tests\TestCase;

class RestErpRetryResourceTest extends TestCase
{
    use CreatesRestErpTables;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRestErpTables();
    }

    public static function statuses(): array
    {
        return [
            'failed' => ['failed', true, false],
            'uncertain' => ['uncertain', true, true],
            'pending' => ['pending', false, false],
            'in progress' => ['in-progress', false, false],
            'success' => ['success', false, false],
            'rejected' => ['rejected', false, false],
            'ignored' => ['ignored', false, false],
        ];
    }

    #[DataProvider('statuses')]
    public function test_full_and_summary_resources_expose_the_valid_retry_action(string $status, bool $retryable, bool $acknowledgement): void
    {
        Auth::user()->givePermissionTo(Permission::create(['name' => 'retry-erp-jobs', 'guard_name' => 'web']));
        $request = Request::create('/api/rest-erp-jobs/17');
        $request->setUserResolver(fn () => Auth::user());
        $job = new RestErpJob(['endpoint' => 'ExitPrints', 'status' => $status, 'parameters' => ['VIN' => 'VIN-1', 'ItemType' => '2']]);
        $job->setAttribute('id', 17);
        foreach ([RestErpJobResource::class, RestErpJobSummaryResource::class] as $resource) {
            $data = (new $resource($job))->resolve($request);
            $this->assertSame($retryable ? route('rest-erp-jobs.retry', ['restErpJob' => 17]) : null, $data['retry_url']);
            $this->assertSame($retryable ? 'PUT' : null, $data['retry_method']);
            $this->assertSame($acknowledgement, $data['retry_requires_acknowledgement']);
            $this->assertSame($job->parameters, $data['parameters']);
        }
    }

    public function test_user_without_retry_permission_or_anonymous_user_gets_no_retry_link(): void
    {
        $job = new RestErpJob(['endpoint' => 'GroupExits', 'status' => 'failed']);
        $job->setAttribute('id', 17);
        foreach ([Auth::user(), null] as $user) {
            $request = Request::create('/api/rest-erp-jobs/17');
            $request->setUserResolver(fn () => $user);
            $data = (new RestErpJobResource($job))->resolve($request);
            $this->assertNull($data['retry_url']);
            $this->assertNull($data['retry_method']);
        }
    }
}
