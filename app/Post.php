<?php

namespace App;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Post extends Eloquent {

    protected $fillable = ['title', 'description'];

    public static function saveNewPost($user, $request) {
        $post = new static;
        $post->user_id = $user->id;
        $post->slug = static::slugify($request['title'], $user->id);
        $post->title = $request['title'];
        $post->description = $request['description'];

        $post->save();

        return $post;
    }


    public static function slugify($title, $id) {
        $slug = $id.'-'.str_slug($title, '-'). '-' . time();

        return $slug;
    }
 
}