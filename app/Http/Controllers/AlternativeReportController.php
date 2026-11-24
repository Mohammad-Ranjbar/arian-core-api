<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AlternativeReportController extends Controller
{
    private function authorizeJob(Request $request, Job $job): void
    {
        abort_unless($request->user()?->hasRole('super-admin'), 403);
        abort_unless($job->type === 'export-alternatives' && (string) $job->user_id === (string) $request->user()->getAuthIdentifier(), 404);
    }

    public function show(Request $request, Job $job)
    {
        $this->authorizeJob($request, $job);

        return response()->json(['status' => $job->status, 'result' => $job->result]);
    }

    public function download(Request $request, Job $job)
    {
        $this->authorizeJob($request, $job);
        $path = 'alternative-reports/'.$job->uuid.'.xlsx';
        abort_unless($job->status === 'finished' && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, 'alternatives-report.xlsx');
    }
}
