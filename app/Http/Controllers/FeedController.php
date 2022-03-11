<?php

namespace App\Http\Controllers;

use App\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;

class FeedController extends Controller
{
    private $loggeduser;

    public function __construct() {
        $this->middleware('auth:api');
        $this->loggeduser = auth()->user();
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
        $post->id_user = $this->loggeduser->id;
        $post->type = $data['type'];
        $post->body = $data['body'];
        $post->save();

        $array["200"] = $post;

        return $array;
    }
}
