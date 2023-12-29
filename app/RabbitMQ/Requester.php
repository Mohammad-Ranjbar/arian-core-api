<?php

namespace App\RabbitMQ;

use VladimirYuldashev\LaravelQueueRabbitMQ\Queue\RabbitMQQueue;

class Requester
{
    public function sendRequest($message)
    {
        $queue = config('queue.failed.queue');

        // Create a RabbitMQQueue instance
        $driver = app(RabbitMQQueue::class);

        // Publish the request message to the default exchange (empty string)
        $driver->pushRaw(json_encode(['message' => $message, 'reply_to' => $queue]), '', $queue);

        // Wait for the reply
        $response = $driver->pop($queue);

        return json_decode($response->getRawBody(), true);
    }


    // Example usage
//    use App\RabbitMQ\Requester;
//
//$requester = new Requester();
//$response = $requester->sendRequest('Hello, RabbitMQ!');
//dd($response);

}
