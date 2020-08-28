<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;

class Export extends Model
{
    protected $casts = [
        'parameters' => 'array'
    ];
    protected $fillable = [
        'mode',
        'parameters',
        'status',
        'file',
        'output',
        'user_id'
    ];
    function buildExport()
    {

        $this->status = time();
        $this->save();
        $parameters = $this->parameters;
        if (isset($parameters['filters'])) {
            $a = $parameters['filters'];
            unset($parameters['filters']);
            $parameters['--filters'] = $a;
        }
        Artisan::call('global:export', [
            'mode' => $this->mode, 'filename' => storage_path($this->getFileName()),
        ] + $parameters);

        if (env('AWS_BUCKET')) {
            $fp = fopen(storage_path($this->getFileName()), 'r');
            Storage::drive('s3')->put($this->getFileName(), $fp, 'private');
            fclose($fp);
            $this->file = $this->getFileName();
            unlink(storage_path($this->getFileName()));
        } else {
            $this->file = $this->getFileName();
        }
        $this->status = 'finished';

        $this->save();
    }
    protected function getFileName()
    {
        $name = $this->id .
            '-' . $this->mode .
            '-' . $this->created_at->format('d-m-y-h-i-s') .
            '.' . $this->getOutputExtension();
        return $name;
    }

    protected function getOutputExtension()
    {
        return 'csv';
    }
    public function process()
    {
        set_time_limit(0);
        ini_set('memory_limit', '500M');
        try {
            $this->buildExport();
        } catch (\Exception $ex) {
            $this->status = 'failed';
            $this->save();
            throw $ex;
        }
    }
    function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
