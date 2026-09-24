<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Admin controllers gate every action with $this->authorize(...).
    use AuthorizesRequests;
}
