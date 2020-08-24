<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AppBaseController;
use App\Services\UploadService;
use Illuminate\Http\Request;

class UploadController extends AppBaseController
{
    protected $allowedTypes = ['banner','event_image'];

    protected $allowedAssetTypes = ['email'];

    public function store($type, $assetType, Request $request)
    {
        // Check to make sure the upload type is allowed
        if (! in_array($type, $this->allowedTypes)) {
            logger("Upload {type} for {$assetType} failed: Type not allowed");
            abort(403);
        }

        // Check to make sure the asset type is allowed
        if (! in_array($assetType, $this->allowedAssetTypes)) {
            logger("Upload {type} for {$assetType} failed: Asset type not allowed");
            abort(403);
        }

        $path = $this->uploadImage($request, $type);

        return [
            'type' => config('emails.config.disk_name'),
            'filename' => $path,
        ];
    }

    private function uploadImage($request, $type)
    {
        if ($type == 'banner') {
            $this->validate($request, [
                'image' => ['required', 'image', 'dimensions:width=570'],
            ], [
                'image.dimensions' => 'Dimensions must be: width 570px',
            ]);
        }

        if ($type == 'event_image') {
            $this->validate($request, [
                'image' => ['required', 'dimensions:width=74'],
            ], [
                'image.dimensions' => 'Dimensions must be: width 74px',
            ]);
        }

        return $request->file('image')->store('emails', [
            'disk' => config('emails.config.disk_name'),
        ]);
    }
}
