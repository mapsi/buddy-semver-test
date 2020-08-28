<?php

namespace App\Console\Commands;

use Cache;
use App\Models\Import;
use App\Models\Interfaces\Brandable;
use App\Models\Interfaces\Routable;
use DateTime;
use Facades\App\Classes\Drupal;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class TestPageload extends Command
{
    protected $signature   = 'test:pageload {--page=/} {--site=https://laravel-core-globemedia.m-w.site} {--count=100}';
    protected $description = 'Marks test';

    public function handle()
    {
        $progress_bar = $this->output->createProgressBar();
        $progress_bar->setOverwrite(true);
        $progress_bar->setRedrawFrequency(10);
        $progress_bar->setFormat('debug');
        $progress_bar->start($this->option('count', 100));
        for ($i = 0; $i < $this->option('count', 100); $i++) {
            $client = new \GuzzleHttp\Client([
                'base_uri' => $this->option('site'),
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);
            ;
            $times = [];
            $logs = [];
            try {
                $t1 = microtime(true);
                $res = $client->get($this->option('page'));
                $t2 = microtime(true);
                $times[] = $t2 - $t1;
                $res->getBody();
                $progress_bar->setMessage($this->average($times));
            } catch (Exception $ex) {
                if (! isset($logs[$ex->getMessage()])) {
                    $logs[$ex->getMessage()] = 1;
                } else {
                    $logs[$ex->getMessage()] ++;
                }
            }


            $progress_bar->advance();
        }


        $progress_bar->finish();
        dump($logs);
        dump($this->average($times));
    }
    protected function average($times)
    {
        return array_sum($times) / count($times);
    }
}
