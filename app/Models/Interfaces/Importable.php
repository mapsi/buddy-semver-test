<?php

namespace App\Models\Interfaces;

use DateTime;
use Illuminate\Console\OutputStyle;

interface Importable
{
    public function updateFromDrupal(array $entity, OutputStyle $output);
    public function import();
    public static function findUuidsOrFail(array $uuids);
    public static function importFromDrupal(OutputStyle $output, DateTime $since_date = null);
}
