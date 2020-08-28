<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DirectoryFirm;
use App\Models\DirectoryIndividual;
use App\Models\DirectoryJurisdiction;

class DirectoryRoutes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'directories:routes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add in any redirects';

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
        $this->info(PHP_EOL . 'starting Individuals');
        $progress_bar = $this->output->createProgressBar();
        $progress_bar->setOverwrite(true);
        $progress_bar->setRedrawFrequency(10);
        $progress_bar->setFormat('debug');
        $progress_bar->start(DirectoryIndividual::count());
        DirectoryIndividual::with('directory.brand')->chunk(100, function ($models) use ($progress_bar) {
            foreach ($models as $model) {
                $redirect = new \App\Models\Redirect([
                    'old' => '/' . $model->directory->slug . '/directory/Detail.aspx?g=' . $model->uuid,
                    'new' => '/' . $model->directory->brand->machine_name . '/directories/' . $model->directory->slug . '/individuals/' . $model->slug
                ]);
                $redirect->save();
                $progress_bar->advance();
            }
        });
        $progress_bar->finish();
        $this->info(PHP_EOL . 'starting Rankings');
        $progress_bar = $this->output->createProgressBar();
        $progress_bar->setOverwrite(true);
        $progress_bar->setRedrawFrequency(10);
        $progress_bar->setFormat('debug');
        $progress_bar->start(DirectoryJurisdiction::count());
        DirectoryJurisdiction::with('directory.brand')->chunk(100, function ($models) use ($progress_bar) {
            foreach ($models as $model) {
                $redirect = new \App\Models\Redirect([
                    'old' => '/' . $model->directory->slug . '/Rankings/Detail.aspx?g=' . $model->uuid,
                    'new' => '/' . $model->directory->brand->machine_name . '/directories/' . $model->directory->slug . '/rankings/' . $model->slug
                ]);
                $redirect->save();
                $progress_bar->advance();
            }
        });
        $progress_bar->finish();

        $this->info(PHP_EOL . 'starting Firms');
        $progress_bar = $this->output->createProgressBar();
        $progress_bar->setOverwrite(true);
        $progress_bar->setRedrawFrequency(10);
        $progress_bar->setFormat('debug');
        $progress_bar->start(DirectoryFirm::count());
        DirectoryFirm::with('directory.brand')->chunk(100, function ($models) use ($progress_bar) {
            foreach ($models as $model) {
                $redirect = new \App\Models\Redirect([
                    'old' => '/' . $model->directory->slug . '/directory/FirmDetail.aspx?g=' . $model->uuid,
                'new' => '/' . $model->directory->brand->machine_name . '/directories/' . $model->directory->slug . '/firms/' . $model->slug
                ]);
                $redirect->save();
                $progress_bar->advance();
            }
        });
        $progress_bar->finish();
    }
}
