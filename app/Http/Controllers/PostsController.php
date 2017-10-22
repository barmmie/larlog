<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Post;

class PostsController extends Controller
{

    public function index(Request $request)
    {
        $posts = $request->user()->posts;

        return $posts;
    }

    public function store(Request $request)
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
            'title' => 'required',
            'description' => 'required',
        ]);

        $post = Post::where('id', $id)
        ->where('user_id', $request->user()->id)
        ->firstOrFail();


        return tap($post)->update($request->only('title', 'description'));
    }

    public function destroy(Request $request, $id)
    {

        $post = Post::where('id', $id)
        ->where('user_id', $request->user()->id)
        ->firstOrFail();

        $post->delete();


        return ['deleted' => true];
    }
}
