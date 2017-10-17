<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PostsController extends Controller
{

    public function index(Request $request)
    {
        $posts = $request->user()->posts;

        return $posts;
    }

    public function create(Request $request)
    {

        $this->validate($request, [
            'title' => 'required',
            'description' => 'required',
        ]);

        $post = Post::saveNewPost($request->user(), $request->only(['title', 'description']));
        return $post;
    }

    public function show(Request $request, $id)
    {
        $post = Post::where('id', $id)
                    ->where('user_id', $request->user()->id)
                    ->firstOrFail();

        return $post;
    }


    public function update(Request $request, $id)
    {
        $user = $request->user();

        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,'.$user->id,
        ]);

        $post = Post::where('id', $id)
        ->where('user_id', $request->user()->id)
        ->firstOrFail();


        return tap($post)->update($request->only('title', 'description'));
    }

    public function delete(Request $request)
    {

        $post = Post::where('id', $id)
        ->where('user_id', $request->user()->id)
        ->firstOrFail();

        $post->delete();


        $posts = $request->user()->posts;

        return $posts;
    }
}
