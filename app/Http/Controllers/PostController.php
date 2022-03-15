<?php

namespace App\Http\Controllers;

use App\Post;
use App\PostComment;
use App\PostLike;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    private $loggeduser;

    public function __construct() {
        $this->middleware('auth:api');
        $this->loggeduser = auth()->user();
    }

    public function like($id)
    {
        $array = ["error" => ""];

        $postExists = Post::find($id);
        $id_user = $this->loggeduser->id;

        if(!$postExists) {
            $array['error'] = "Post não existe";
            return $array;
        }

        $isLiked = PostLike::where("id_post", $id)
        ->where("id_user", $id_user)
        ->first();

        if($isLiked) {
            $isLiked->delete();
            $liked = false;
        } else {
            $newPostLike = new PostLike();
            $newPostLike->id_post = $id;
            $newPostLike->id_user = $id_user;
            $newPostLike->created_at = date('Y-m-d H:i:s');
            $newPostLike->save();
            $liked = true;
        }

        $likeCount = PostLike::where("id_post", $id)->count();
        $array["liked"] = $liked;
        $array["likeCount"] = $likeCount;

        return $array;
    }

    public function comment(Request $request, $id)
    {
        $array = ["error" => ""];

        $data = $request->all();
        $validator = Validator::make(
            $data,
            [
                "body" => "required",
            ]
        );
        if($validator->fails()) {
            $array["error"] = $validator->messages();
            return $array;
        }

        if(!Post::find($id)){
            $array["error"] = "Post não existe";
            return $array;
        }

        $newComment = new PostComment();
        $newComment->id_post = $id;
        $newComment->id_user = $this->loggeduser->id;
        $newComment->body = $data['body'];
        $newComment->created_at = date("Y-m-d H:i:s");
        $newComment->save();

        $array["postComment"] = $newComment;

        return $array;
    }
}
