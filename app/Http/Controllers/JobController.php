<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\JsonResponse;

class JobController extends Controller
{
    /**
     * @param  Job  $job
     * @return JsonResponse
     */
    public function __invoke(Job $job): JsonResponse
    {
        return response()->json($job);
    }
}
