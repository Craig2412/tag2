<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @yields @response 401 {"message": "Unauthenticated."}
 * @yields @response 403 {"message": "Forbidden."}
 * @yields @response 500 {"message": "Server Error."}
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests;
}
