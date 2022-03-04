<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    private $loggeduser;

    public function __construct() {
        $this->middleware('auth:api');
        $this->loggeduser = Auth::user()->user;
    }

    public function update(Request $request) {
        $array = ['error' => ''];
        
        return $array;
    }
}
