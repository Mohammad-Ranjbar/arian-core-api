<?php

namespace App\Console\Commands;

use App\Models\Element;
use App\Models\Incident;
use Exception;
use Illuminate\Console\Command;

class AssignMakeCodeToElementIncidentCommand extends Command
{

    protected $signature = 'app:assign-make-code-to-element-incident-command';


    protected $description = 'Command description';

    public function handle(): void
    {

        try {
            $elements = Element::query()->get();
            $progress = $this->output->createProgressBar($elements->count());
            $progress->setBarCharacter('+');
            $progress->start();
            foreach ($elements as $element) {
                if ($element->makeCodes()->where('make_code', 3)) {
                    $element->makeCodes()->attach(6);
                    $progress->advance();

                }
            }

            $incidents = Incident::query()->get();
            foreach ($incidents as $incident) {
                if ($incident->makeCodes()->where('make_code', 3)) {
                    $incident->makeCodes()->attach(6);
                }
            }
        } catch (Exception $exception) {
            logger($exception);
        }
    }
}
