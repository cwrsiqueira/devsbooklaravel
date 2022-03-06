<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

use App\User;

class UserController extends Controller
{
    private $loggeduser;

    public function __construct() {
        $this->middleware('auth:api');
        $this->loggeduser = Auth::user()->user;
    }

    public function update(Request $request, $id) {
        $array = ['error' => ''];

        $data = $request->all();

        $validator = Validator::make(
            $data,
            [
                'email' => ['required', "email", Rule::unique('users')->ignore($id)],
            ]
        );

        if($validator->fails()){
            $array = ['error' => $validator->messages()];
            return $array;
        }

        if(!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user = User::where('id', $id)->update($data);

        return $array;
    }
}
