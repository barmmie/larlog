<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PublicPostsController extends Controller
{

    public function index(Request $request)
    {
        $posts = Post::all();

        return $posts;
    }

    
    public function show(Request $request, $slug)
    {
        $post = Post::where('slug', $slug)
                    ->firstOrFail();

        return $post;
    }


}
