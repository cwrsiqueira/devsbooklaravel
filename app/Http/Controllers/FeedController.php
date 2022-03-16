<?php

namespace App\Http\Controllers;

use App\Post;
use App\PostComment;
use App\PostLike;
use App\User;
use App\UserRelation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;

class FeedController extends Controller
{
    private $loggedUser;

    public function __construct() {
        $this->middleware('auth:api');
        $this->loggedUser = auth()->user();
    }

    public function create(Request $request)
    {
        $array = ["error" => ""];
        $allowedTypes = ["image/jpg", "image/jpeg", "image/png"];

        $data = $request->all();

        $validator = Validator::make(
            $data,
            [
                "type" => "required|string",
            ]
        );

        if($validator->fails()) {
            $array["error"] = $validator->messages();
            return $array;
        }

        switch ($data['type']) {
            case 'text':
                if(!$data['body']) {
                    $array["error"] = "Texto não enviado!";
                    return $array;
                }
                break;
            case 'photo':
                if($data['photo']) {
                    $image = $data['photo'];
                    if(in_array($image->getClientMimeType(), $allowedTypes)) {
                        $filename = md5(time().rand(0,9999)).".jpg";
                        $destPath = public_path("/media/uploads");

                        $img = Image::make($image->path())
                            ->resize(800, null, function($constraint){
                                $constraint->aspectRatio();
                            })
                            ->save($destPath."/".$filename);
                            $data['body'] = $filename;
                    } else {
                        $array["error"] = "Arquivo não suportado!";
                        return $array;
                    }
                } else {
                    $array["error"] = "Imagem não enviada!";
                    return $array;
                }
                break;

            default:
                $array["error"] = "Tipo de postagem inexistente!";
                break;
        }

        $post = new Post();
        $post->id_user = $this->loggedUser->id;
        $post->type = $data['type'];
        $post->body = $data['body'];
        $post->save();

        $array["200"] = $post;

        return $array;
    }

    public function read(Request $request)
    {
        $array = ["error" => ""];

        $page = intval($request->input('page'));
        $perPage = 2;

        // Pegar lista de usuários que eu sigo, inclusive eu
        $users = [];
        $userList = UserRelation::where('user_from', $this->loggedUser->id)->get();
        foreach ($userList as $userItem) {
            $users[] = $userItem->user_to;
        }
        $users[] = $this->loggedUser->id;

        $total = Post::whereIn('id_user', $users)->count();
        $pageCount = ceil($total / $perPage);

        // Pegar posts dos usuários que eu sigo, inclusive os meus, ordenados por data
        $postList = Post::whereIn('id_user', $users)
        ->orderBy('created_at', 'DESC')
        ->offset($page * $perPage)
        ->limit($perPage)
        ->get();

        // Preencher informações adicionais - likes, comments etc.

        $posts = $this->_postListToObject($postList, $this->loggedUser->id);

        $array['posts'] = $posts;
        $array['pageCount'] = $pageCount;
        $array['currentPage'] = $page;

        return $array;
    }

    public function userFeed(Request $request, $id = false)
    {
        $array = ["error" => ""];

        if(!$id) {
            $id = $this->loggedUser->id;
        }

        $page = intval($request->input('page'));
        $perPage = 2;

        $total = Post::where('id_user', $id)->count();
        $pageCount = ceil($total / $perPage);

        // Pegar posts dos usuários que eu sigo, inclusive os meus, ordenados por data
        $postList = Post::where('id_user', $id)
        ->orderBy('created_at', 'DESC')
        ->offset($page * $perPage)
        ->limit($perPage)
        ->get();

        // Preencher informações adicionais - likes, comments etc.

        $posts = $this->_postListToObject($postList, $this->loggedUser->id);

        $array['posts'] = $posts;
        $array['pageCount'] = $pageCount;
        $array['currentPage'] = $page;
        $array['totalPosts'] = $total;

        return $array;
    }

    public function userPhotos(Request $request, $id = false)
    {
        $array = ["error" => ""];

        $page = intval($request->input('page'));
        $perPage = 2;

        // Pegar fotos dos usuários que eu sigo, inclusive os meus, ordenados por data
        $postList = Post::where('id_user', $id)
        ->where('type', 'photo')
        ->orderBy('created_at', 'DESC')
        ->offset($page * $perPage)
        ->limit($perPage)
        ->get();

        $total = Post::where('id_user', $id)->where('type', 'photo')->count();
        $pageCount = ceil($total / $perPage);

        if(!$id) {
            $postList = Post::where('type', 'photo')
            ->orderBy('created_at', 'DESC')
            ->offset($page * $perPage)
            ->limit($perPage)
            ->get();

            $total = Post::where('type', 'photo')->count();
            $pageCount = ceil($total / $perPage);
        }

        // Preencher informações adicionais - likes, comments etc.

        $posts = $this->_postListToObject($postList, $this->loggedUser->id);

        foreach($posts as $item) {
            $item['body'] = url("/media/uploads" , $item['body']);
        }

        $array['posts'] = $posts;
        $array['pageCount'] = $pageCount;
        $array['currentPage'] = $page;
        $array['totalPosts'] = $total;

        return $array;
    }

    private function _postListToObject($postList, $loggedId)
    {
        foreach ($postList as $postKey => $postItem) {

            // Verificar se o post é meu
            if($postItem['id_user'] == $loggedId) {
                $postList[$postKey]['mine'] = true;
            } else {
                $postList[$postKey]['mine'] = false;
            }

            // Preencher informações do usuário
            $userInfo = User::find($postItem['id_user']);
            $userInfo['avatar'] = url('media/avatars/'.$userInfo['avatar']);
            $userInfo['cover'] = url('media/covers/'.$userInfo['cover']);
            $postList[$postKey]['user'] = $userInfo;

            // Preencher informações de LIKE
            $likes = PostLike::where('id_post', $postItem['id'])->count();
            $postList[$postKey]['likeCount'] = $likes;

            $isLiked = PostLike::where('id_post', $postItem['id'])->where('id_user', $loggedId)->count();
            $postList[$postKey]['liked'] = ($isLiked > 0) ? true : false;

            // Preencher informações de COMMENT
            $comments = PostComment::where('id_post', $postItem['id'])->get();
            foreach ($comments as $commentKey => $comment) {
                $user = User::find($comment['id_user']);
                $user['avatar'] = url('media/avatars/'.$user['avatar']);
                $user['cover'] = url('media/covers/'.$user['cover']);
                $comments[$commentKey]['user'] = $user;
            }
            $postList[$postKey]['comments'] = $comments;


        }
        return $postList;
    }
}
