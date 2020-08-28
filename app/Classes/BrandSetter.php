<?php

namespace App\Classes;

use Dotenv\Dotenv;
use File;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;

class BrandSetter
{
    /**
     * @var string
     */
    private $brand;

    /**
     * @return void
     */
    public function loadConfiguration()
    {
        $this->mergeHostConfiguration();
    }

    /**
     * @param string $activeHost
     * @return $this
     */
    public function setHostBrand(string $activeHost)
    {
        $this->brand = $activeHost;

        return $this;
    }

    /**
     * @return void
     */
    private function mergeHostConfiguration()
    {
        $brand = $this->brand;

        $files = [];

        if ($brand === 'admin') {
            $files[] = config_path("environments/.env.admin");
        } else {
            $files = array_merge($files, $this->getBrandFiles($brand));
        }

        $localEnvFile = base_path('.env');
        if (File::exists($localEnvFile)) {
            $files[] = $localEnvFile;
        }

        foreach ($files as $file) {
            $this->loadConfig($file);
        }

        (new LoadConfiguration())->bootstrap(app());
    }

    private function getBrandFiles($brand)
    {
        $environment = app()->environment();
        $files = [];

        $globalFilePath = base_path("brands/{$brand}/environments/global");
        if (File::exists($globalFilePath)) {
            $files[] = $globalFilePath;
        }

        $envFilePath = base_path("brands/{$brand}/environments/{$environment}");
        if (File::exists($envFilePath)) {
            $files[] = $envFilePath;
        }

        return $files;
    }

    private function loadConfig($envFilePath)
    {
        (Dotenv::create(dirname($envFilePath), basename($envFilePath)))->overload();
    }
}
