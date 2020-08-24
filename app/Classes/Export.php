<?php

namespace App\Classes;

use Illuminate\Console\Command;

/**
 * Attempt at a clean export command that we can use to deal with issues can go in its own package and improved
 */
class Export extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'global:export {mode} {filename?} {--model=} {--headerless} {--fields=*} {--filters=[]} {--output=csv}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export data';
    protected $file;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (is_string($this->option('filters'))) {
            $this->filters = json_decode($this->option('filters'));
        } else {
            $this->filters = $this->option('filters');
        }
        $mode = $this->argument('mode');
        if (method_exists($this, 'mode' . ucfirst($mode))) {
            $this->{'mode' . ucfirst($mode)}();
        }
    }
    /**
     * Creates a CSV based on the given model
     */
    protected function modeModel()
    {
        if (class_exists($this->option('model'))) {
            $query = $this->option('model')::select($this->option('fields') ?: '*');
            if (isset($this->filters['start'])) {
                $query->where('created_at', '>=', $this->filters['start']);
            }
            if (isset($this->filters['end'])) {
                $query->where('created_at', '<=', $this->filters['end']);
            }
            $this->makeCsv($query);
        } else {
            $this->error('Unknown model');
        }
    }
    /**
     * function that does the work
     * @param mixed $query object that has the chunk function taking a number per chunk and a function that gets given the chunk
     * @param  $parser
     */
    protected function makeCsv($query, $parser = false)
    {
        $this->file = fopen($this->argument('filename') ?: 'php://stdout', 'w');
        $first = true;
        if ($this->argument('filename')) {
            $progress_bar = $this->output->createProgressBar();
            $progress_bar->setOverwrite(true);
            $progress_bar->setRedrawFrequency(10);
            $progress_bar->setFormat('debug');
            $progress_bar->start($query->count());
        }
        $query->chunk(100, function ($results) use (&$first, $parser, $progress_bar) {
            foreach ($results as $result) {
                if (! $parser) {
                    $a = $result->setVisible([])->toArray();
                } else {
                    $a = $parser($result);
                }
                if ($first && ! $this->option('headerless')) {
                    fputcsv($this->file, array_keys($a));
                }
                $first = false;
                fputcsv($this->file, $a);
                if ($this->argument('filename')) {
                    $progress_bar->advance();
                }
            }
        });
        if ($this->argument('filename')) {
            $progress_bar->finish();
            $this->line('');
        }
        fclose($this->file);
    }
}
