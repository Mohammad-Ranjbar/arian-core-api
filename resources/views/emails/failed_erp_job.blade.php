<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>ERP Job Failed</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
        }

        .container {
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }

        .header {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .details {
            margin: 10px 0;
        }

        .error {
            color: #c0392b;
            background: #f2d7d5;
            padding: 10px;
            border-radius: 6px;
        }

        .btn {
            display: inline-block;
            background: #4a90e2;
            color: #fff !important;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">🚨 ERP Job Failed</div>

    <div class="details"><strong>Job ID:</strong> {{ $job->id }}</div>
    <div class="details"><strong>model ID:</strong> {{ $job->model_id ?? '-' }}</div>
    <div class="details"><strong>model Type:</strong> {{ $job->model_type ?? '-' }}</div>
    <div class="details"><strong>status:</strong> {{ $job->status ?? '-' }}</div>
    <div class="details"><strong>section:</strong> {{ $job->section ?? '-' }}</div>
    <div class="details"><strong>description:</strong> {{ $job->description ?? '-' }}</div>
    <div class="details"><strong>queue:</strong> {{ $job->queue ?? '-' }}</div>
    <div class="details"><strong>url:</strong> {{ $job->url ?? '-' }}</div>
    <div class="details"><strong>tries:</strong> {{ $job->tries ?? '-' }}</div>
    <div class="details"><strong>parameters:</strong> {{ $job->parameters ?? '-' }}</div>
    <div class="details"><strong>user:</strong> {{ $job->user?->name ?? '-' }}</div>
    <div class="details"><strong>Created At:</strong> {{ jdate($job->created_at) }}</div>
    <hr>
    <div class="error">
        <strong>Error Message:</strong><br>
        {{ $exception->getMessage() }}
    </div>
    <hr>
    <p>Thanks,
        <br>
        Arian Core Web Development.
    </p>
</div>
</body>
</html>
