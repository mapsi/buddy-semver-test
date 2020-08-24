<?php

namespace App\Models\Interfaces;

use Illuminate\Console\OutputStyle;
use Illuminate\Database\Eloquent\Relations\HasMany;

interface HasContentSections
{
    public function contentSections();
    public static function getContentSectionFields();
    public function attachContentSections(array $entity, OutputStyle $output);
}
