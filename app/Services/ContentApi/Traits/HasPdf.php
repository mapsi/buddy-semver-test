<?php

namespace App\Services\ContentApi\Traits;

trait HasPdf
{
    public function getPdf()
    {
        return $this->getInfo('PdfLink') ?? null;
    }

    public function getDigitalAssetUrl()
    {
        return $this->getDigitalAssets() ? $this->getDigitalAssets()[0]['Url'] : null;
    }

    public function getDigitalAssetFileName()
    {
        return $this->getDigitalAssets() ? $this->getDigitalAssets()[0]['Name'] : null;
    }

    public function getDigitalAssets()
    {
        return $this->getInfo('Files') ?? [];
    }
}
