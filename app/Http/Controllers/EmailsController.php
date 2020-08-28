<?php

namespace App\Http\Controllers;

use App\Models\Email;

class EmailsController extends Controller
{
    public function show(Email $email)
    {
        $user = auth()->user();

        return $email->getMailable($user);
    }
}
