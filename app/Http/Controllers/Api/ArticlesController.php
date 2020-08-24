<?php

namespace App\Http\Controllers\Api;

use App\Models\Article;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ArticlesController
{
    /**
     * @param Article $article
     * @return array
     */
    public function show(Article $article)
    {
        return Arr::only($article->getAttributes(), 'title');
    }
}
