<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SearchController extends Controller
{
    private $loggeduser;

    public function __construct() {
        $this->middleware('auth:api');
        $this->loggeduser = auth()->user();
    }

    public function search(Request $request)
    {
        $array = ["error" => "", "users" => []];

        $data = $request->all();
        $validator = Validator::make(
            $data,
            [
                "search" => "required"
            ]
        );
        if($validator->fails()){
            $array["error"] = $validator->messages();
            return $array;
        }

        $search = User::select('id', 'name', 'avatar')
        ->where('name', 'LIKE', '%'.$data['search'].'%')
        ->get();

        foreach ($search as $key => $item) {
            $array['users'][] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'avatar' => url("/media/avatars/" . $item['avatar'])
            ];
        }

        return $array;
    }
}
