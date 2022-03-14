<?php

namespace App\Http\Controllers;

use App\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

use App\User;
use App\UserRelation;
use Image;

class UserController extends Controller
{
    private $loggedUser;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->loggedUser = auth()->user();
    }

    public function read($id = null)
    {
        $array = ["error" => ""];
        if (!$id) {
            $id = $this->loggedUser->id;
        }
        $user = User::find($id);
        if (!$user) {
            $array["error"] = "Usuário não existe";
        }
        $array["user"] = $this->getAllDataUser($user);
        return $array;
    }

    private function getAllDataUser(User $user): object
    {
        $array = $user;
        $array["avatar"] = url("/media/avatars/" . $user['avatar']);
        $array["cover"] = url("/media/covers/" . $user['cover']);
        $array["me"] = ($user['id'] == $this->loggedUser->id) ? true : false;

        $dateFrom = new \DateTime($user['birthdate']);
        $dateTo = new \DateTime('today');
        $array["age"] = $dateFrom->diff($dateTo)->y;

        $photos = Post::where("id_user", $user->id)->where('type', 'photo')->get();
        $array["qtPhotos"] = count($photos);
        foreach($photos as $item) {
            $thisPhoto = $item;
            $thisPhoto['body'] = url("/media/uploads/" . $item['body']);
            $arrayPhotos[] = $thisPhoto;
        }
        $array["photos"] = $arrayPhotos;


        $followers = UserRelation::where("user_to", $user['id'])->get("user_from");
        $array["qtFollowers"] = count($followers);
        $arrayFollowers = [];
        foreach ($followers as $item) {
            $thisFollower = User::find($item['user_from']);

            $thisFollower["avatar"] = url("/media/avatars/" . $thisFollower['avatar']);
            $thisFollower["cover"] = url("/media/covers/" . $thisFollower['cover']);
            $thisFollower["me"] = ($thisFollower['id'] == $this->loggedUser->id) ? true : false;

            $dateFrom = new \DateTime($thisFollower['birthdate']);
            $dateTo = new \DateTime('today');
            $thisFollower["age"] = $dateFrom->diff($dateTo)->y;

            $arrayFollowers[] = $thisFollower;
            // $arrayFollowers[] = self::getAllDataUser($thisFollower);
        }
        $array["followers"] = $arrayFollowers;

        $following = UserRelation::where("user_from", $user['id'])->get("user_to");
        $array["qtFollowings"] = count($following);
        $dataFollowing = [];
        foreach ($following as $item) {
            $thisFollowing = User::find($item['user_to']);

            $thisFollowing["avatar"] = url("/media/avatars/" . $thisFollowing['avatar']);
            $thisFollowing["cover"] = url("/media/covers/" . $thisFollowing['cover']);
            $thisFollowing["me"] = ($thisFollowing['id'] == $this->loggedUser->id) ? true : false;

            $dateFrom = new \DateTime($thisFollowing['birthdate']);
            $dateTo = new \DateTime('today');
            $thisFollowing["age"] = $dateFrom->diff($dateTo)->y;

            $dataFollowing[] = $thisFollowing;
            // $dataFollowing[] = self::getAllDataUser($thisFollowing);
        }
        $array["followings"] = $dataFollowing;

        return $array;
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

        if ($image) {

            if (in_array($image->getClientMimeType(), $allowedTypes)) {
                $filename = md5(time() . rand(0, 9999)) . ".jpg";
                $destPath = public_path("/media/avatars");

                $img = Image::make($image->path())
                    ->fit(200, 200)
                    ->save($destPath . "/" . $filename);

                $user = User::find($this->loggedUser['id']);

                if ($user->avatar && $user->avatar != 'default.jpg') {
                    $unlink = $destPath . "/" . $user->avatar;
                    unlink($unlink);
                }

                $user->avatar = $filename;
                $user->save();

                $array['url'] = url("/media/avatars/" . $filename);
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

        if ($image) {

            if (in_array($image->getClientMimeType(), $allowedTypes)) {
                $filename = md5(time() . rand(0, 9999)) . ".jpg";
                $destPath = public_path("/media/covers");

                $img = Image::make($image->path())
                    ->fit(850, 310)
                    ->save($destPath . "/" . $filename);

                $user = User::find($this->loggedUser['id']);

                if ($user->cover && $user->cover != 'cover.jpg') {
                    $unlink = $destPath . "/" . $user->cover;
                    unlink($unlink);
                }

                $user->cover = $filename;
                $user->save();

                $array['url'] = url("/media/covers/" . $filename);
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
