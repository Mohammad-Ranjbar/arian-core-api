<?php

namespace App\Console\Commands;

use App\Jobs\FakeInsertJob;
use Illuminate\Console\Command;

class SendFakeDataToElasticCommand extends Command
{

    protected $signature = 'insert:fake';


    protected $description = 'insert fake data to elasticsearch';


    public function handle(): void
    {
        $tags = range(1,10);
        $methods = ['get','put','post', 'delete'];
        $genders = ['m', 'f'];
        $list = [
            'body' => []
        ];
        for ($i = 0; $i <= 1000000; $i++) {
            shuffle($tags);
            shuffle($methods);
            shuffle($genders);
            $data = [
                'title' => fake()->title,
                'city' => fake()->city,
                'color' => fake()->colorName,
                'size' => rand(36,46),
                'category' => [
                    'id' => rand(1,100)
                ],
                'tags' => [
                    $tags[0],$tags[1],$tags[2]
                ],
                'method' => $methods[1],
                'gender' => $genders[1],
            ];
            $list['body'][] = [
                'index' => [
                    '_index' => 'aaa',
                    '_id'    => $i
                ]
            ];
            $list['body'][] = $data;
            if(count($list['body']) == 20000) {
                FakeInsertJob::dispatch($list);
                $list = [
                    'body' => []
                ];
            }
        }

    }
}
