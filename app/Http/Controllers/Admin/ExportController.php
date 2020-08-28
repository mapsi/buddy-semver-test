<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Export;
use Storage;

class ExportController extends Controller
{
    function getList()
    {
        return view('exports.index')->with('exports', Export::paginate())->with('modes', $this->getModes());
    }
    function getfile(Export $export)
    {
        if (env('AWS_BUCKET')) {
            return redirect(Storage::drive('s3')->temporaryUrl($export->file, now()->addMinutes(5)));
        } else {
            return Response()->download(storage_path($export->file), null, ['Content-Type' => 'text/csv']);
        }
    }
    function postSetupNew(Request $request)
    {
        $filters = $this->exportGetParameters($request->input('mode'));

        //any special filters can be added here


        return view('exports.create')
            ->with('mode', $request->input('mode'))
            ->with('modes', $this->getModes())
            ->with('models', $this->getModels(app_path() . '/Models'))
            ->with('filters', $filters);
    }

    function postRequestNew(Request $request)
    {
        $export = new Export();
        $export->mode = $request->input('mode');
        $export->output = 'csv';
        $export->parameters = [
            '--filters' => $request->only(array_keys($this->exportGetParameters($request->input('mode')))),
            '--model' => $request->input('model')
        ];
        $export->user_id = auth()->user()->id;
        $export->save();
        return redirect()->route('exports.list');
    }

    function exportGetParameters($export)
    {
        $filters = [
            'start' => 'date',
            'end' => 'date',

        ];

        return $filters;
    }
    function getModes()
    {
        $out = [
            'usage' => 'Usage',
            'model' => 'System data',
            'users' => 'Users'

        ];
        //$this->getModels(app_path().'/Models')
        return $out;
    }
    function getModels($path)
    {
        $out = [];
        ;
        $results = scandir($path);
        foreach ($results as $result) {
            if ($result === '.' || $result === '..') {
                continue;
            }
            $filename = $path . '/' . $result;
            if (is_dir($filename)) {
                //$out = array_merge($out, $this->getModels($filename));
            } else {
                $a = 'App\\Models\\' . substr($result, 0, -4);
                if (new $a() instanceof \App\Models\Interfaces\Exportable) {
                    $out['App\\Models\\' . substr($result, 0, -4)] = substr($result, 0, -4);
                }
            }
        }
        return $out;
    }
}
