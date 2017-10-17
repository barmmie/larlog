<?php

namespace App;

class Post extends Model {


    public static function saveNewPost($user, $request) {
        $post = new static;
        $post->user_id = $user->id;
        $post->slug = static::slugify($request['title'], $user->id);
        $post->title = $request['title'];
        $post->description = $request['description'];

        $post->save();
    }


    public static function slugify($title) {
        $slug = $user->id.'-'.str_slug('title', '-'). '-' . time();

        return $slug;
    }
 
}