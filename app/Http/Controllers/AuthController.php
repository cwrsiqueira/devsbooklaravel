<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\User;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', [
            'except' => [
                'login',
                'create',
                'unauthorized',
            ]
        ]);
    }

    public function unauthorized()
    {
        $array = json_encode(['error' => 'Acesso não autorizado'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return response($array, 401);
    }

    public function login(Request $request)
    {
        $array = ['error' => ''];

        $credentials = $request->only([
            'email',
            'password',
        ]);

        $validator = Validator::make(
            $credentials,
            [
                'email' => ['required', 'string', 'email', 'max:100'],
                'password' => ['required', 'string', 'min:6'],
            ]
        );

        if ($validator->fails()) {
            $array['error'] = $validator->errors();
        } else {
            $token = Auth::attempt($credentials);

            if ($token) {
                $array['token'] = $token;
            } else {
                $array = json_encode(['error' => 'E-mail e/ou senha inválidos!'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                return response($array, 401);
            }
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function logout()
    {
        auth()->logout();
        return ['error' => ''];
    }

    public function refresh()
    {
        $token = auth()->refresh();
        return [
            'error' => '',
            'token' => $token,
        ];
    }

    public function create(Request $request)
    {
        $array = ['error' => ''];

        $data = $request->only([
            'name',
            'email',
            'password',
            'birthdate',
        ]);

        $validator = Validator::make(
            $data,
            [
                'name' => ['required', 'string', 'max:100'],
                'email' => ['required', 'string', 'email', 'max:100', 'unique:users'],
                'password' => ['required', 'string', 'min:6'],
                'birthdate' => ['required', 'date'],
            ]
        );

        if ($validator->fails()) {
            $array['error'] = $validator->errors();
        } else {
            $newUser = new User();
            $newUser->name = $data['name'];
            $newUser->email = $data['email'];
            $newUser->password = Hash::make($data['password']);
            $newUser->birthdate = $data['birthdate'];
            $newUser->save();

            $credentials = $request->only('email', 'password');
            $token = Auth::attempt($credentials);

            if ($token) {
                $array['token'] = $token;
            } else {
                $array['error'] = 'Ocorreu um erro! Contacte o administrador do sistema.';
            }
        }

        return json_encode($array, JSON_UNESCAPED_UNICODE);
    }
}
