<?php

namespace App\Models\Interfaces;

use Illuminate\Mail\Mailable;

interface Emailable
{
    public function getMailable(): Mailable;
}
