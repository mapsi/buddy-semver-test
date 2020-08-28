<?php

namespace App\Models;

use Spatie\MediaLibrary\Models\Media as BaseMedia;
use Spatie\MediaLibrary\UrlGenerator\S3UrlGenerator;
use Spatie\MediaLibrary\UrlGenerator\UrlGeneratorFactory;

/**
 * @property string|null caption
 * @property string|null credits
 * @property string|null formatted_credits
 */
class Media extends BaseMedia
{
    // The previews need absolute URLs. S3 gives us those but for local development, we need to wrap it in the URL function.
    public function getUrl(string $conversionName = ''): string
    {
        $urlGenerator = UrlGeneratorFactory::createForMedia($this, $conversionName);

        if ($urlGenerator instanceof S3UrlGenerator) {
            return $urlGenerator->getUrl();
        }

        return url($urlGenerator->getUrl());
    }

    public function getSignatureAttribute()
    {
        return md5($this->id . '~' . config('APP_KEY') . '~' . $this->model_id);
    }

    /**
     * @return string|null
     */
    public function getCreditsAttribute()
    {
        return $this->custom_properties['credits'] ?? null;
    }

    /**
     * @return string|null
     */
    public function getFormattedCreditsAttribute()
    {
        if (empty($this->custom_properties['credits'])) {
            return null;
        }

        $credit = lang('credit');

        return "($credit: {$this->custom_properties['credits']})";
    }

    /**
     * @return string|null
     */
    public function getCaptionAttribute()
    {
        return $this->custom_properties['caption'] ?? null;
    }
}
