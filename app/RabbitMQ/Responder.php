<?php

// app/RabbitMQ/Responder.php

namespace App\RabbitMQ;

use Illuminate\Support\Facades\Log;
use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\RabbitMQQueue;

class Responder
{
    public function handle()
    {
        $queue = config('queue.connections.rabbitmq.queue');//default queue

        // Create a RabbitMQQueue instance
        $driver = app(RabbitMQQueue::class);

        while (true) {
            // Listen for incoming messages on the specified queue
            $message = $driver->pop($queue);

            if ($message) {
                $data = json_decode($message->getRawBody(), true);

                // Process the request
                $response = $this->processRequest($data['message']);

                // Send the reply to the default exchange (empty string)
                $driver->pushRaw(json_encode($response), '', $data['reply_to']);
            }

            sleep(1); // Add a sleep to avoid high CPU usage
        }
    }

    private function processRequest($request): array
    {
        // Implement your business logic here
        Log::info('Processing request: ' . $request);

        return ['result' => 'Request processed successfully'];
    }
}
