<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

use App\User;
use Image;

class UserController extends Controller
{
    private $loggeduser;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->loggeduser = auth()->user();
    }

    /**
     * Undocumented function
     *
     * @param Request $request
     * @param int $id
     * @return array
     */
    public function update(Request $request, $id): array
    {
        $array = ['error' => ''];

        $data = $request->all();

        $validator = Validator::make(
            $data,
            [
                'email' => ["email", Rule::unique('users')->ignore($id)],
                "birthdate" => ["date"],
            ]
        );

        if ($validator->fails()) {
            $array = ['error' => $validator->messages()];
            return $array;
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user = User::where('id', $id)->update($data);

        return $array;
    }

    public function updateAvatar(Request $request)
    {
        $array = ["error" => ""];
        $allowedTypes = ["image/jpg", "image/jpeg", "image/png"];

        $image = $request->file('avatar');

        if($image) {

            if(in_array($image->getClientMimeType(), $allowedTypes)) {
                $filename = md5(time().rand(0,9999)).".jpg";
                $destPath = public_path("/media/avatars");

                $img = Image::make($image->path())
                    ->fit(200, 200)
                    ->save($destPath."/".$filename);

                $user = User::find($this->loggeduser['id']);

                if($user->avatar && $user->avatar != 'default.jpg') {
                    $unlink = $destPath."/".$user->avatar;
                    unlink($unlink);
                }

                $user->avatar = $filename;
                $user->save();

                $array['url'] = url("/media/avatars/".$filename);

            } else {
                $array['error'] = "Arquivo não suportado";
                return $array;
            }
        } else {
            $array['error'] = 'Arquivo não enviado';
        }

        return $array;
    }

    public function updateCover(Request $request)
    {
        $array = ["error" => ""];
        $allowedTypes = ["image/jpg", "image/jpeg", "image/png"];

        $image = $request->file('cover');

        if($image) {

            if(in_array($image->getClientMimeType(), $allowedTypes)) {
                $filename = md5(time().rand(0,9999)).".jpg";
                $destPath = public_path("/media/covers");

                $img = Image::make($image->path())
                    ->fit(850, 310)
                    ->save($destPath."/".$filename);

                $user = User::find($this->loggeduser['id']);

                if($user->cover && $user->cover != 'cover.jpg') {
                    $unlink = $destPath."/".$user->cover;
                    unlink($unlink);
                }

                $user->cover = $filename;
                $user->save();

                $array['url'] = url("/media/covers/".$filename);

            } else {
                $array['error'] = "Arquivo não suportado";
                return $array;
            }
        } else {
            $array['error'] = 'Arquivo não enviado';
        }

        return $array;
    }
}
