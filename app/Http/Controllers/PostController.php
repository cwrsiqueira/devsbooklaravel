<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;

class PostController extends Controller
{
    private $loggeduser;

    public function __construct() {
        $this->middleware('auth:api');
        $this->loggeduser = auth()->user;
    }
}
