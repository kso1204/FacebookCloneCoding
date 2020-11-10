<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\UserImage as UserImageResource;

class UserImageController extends Controller
{
    //
    public function store()
    {
        $data = request()->validate([
            'image' => '',
            'width' => '',
            'height' => '',
            'location' => '',
        ]);

        $image = Storage::url($data['image']->store('user-images','public'));
/* 
        Image::make($data['image'])
            ->fit($data['width'],$data['height'])
            ->save(storage_path('app/public/user-images/'.$data['image']->hashName())); */

        $userImage = auth()->user()->images()->create([
            'path' => $image,
            'width' => $data['width'],
            'height' => $data['height'],
            'location' => $data['location'],
        ]);

        return new UserImageResource($userImage);
    }
}
