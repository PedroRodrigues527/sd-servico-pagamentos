<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
* @OA\Info(
*     version="0.0.1",
*     title="SD Payment API"
* )
* @OA\SecurityScheme(
*     type="http",
*     scheme="bearer",
*     securityScheme="bearerAuth",
* )
*/
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}

