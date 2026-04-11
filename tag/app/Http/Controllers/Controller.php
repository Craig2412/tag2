<?php

namespace App\Http\Controllers;

/**
 * @yields @response 401 {"message": "Unauthenticated."}
 * @yields @response 403 {"message": "Forbidden."}
 * @yields @response 500 {"message": "Server Error."}
 */
abstract class Controller
{
    //
}
