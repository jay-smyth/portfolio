<?php

namespace App\Http\Controllers;

use App\Support\Blog\Guide;
use Illuminate\Contracts\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        return view('public.blog.index', [
            'guides' => Guide::all(),
        ]);
    }

    public function guide(string $guide): View
    {
        $guide = Guide::find($guide) ?? abort(404);

        return view('public.blog.page', [
            'guide' => $guide,
            'chapter' => null,
            'page' => $guide->page(),
        ]);
    }

    public function chapter(string $guide, string $chapter): View
    {
        $guide = Guide::find($guide) ?? abort(404);

        abort_unless($guide->hasChapter($chapter), 404);

        return view('public.blog.page', [
            'guide' => $guide,
            'chapter' => $chapter,
            'page' => $guide->page($chapter),
        ]);
    }
}
