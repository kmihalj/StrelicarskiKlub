<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * Bazni kontroler aplikacije iz kojeg nasljeđuju svi ostali kontroleri.
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
