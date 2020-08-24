<?php

namespace App\Http\ViewComposers;

use App\Models\Brand;
use Illuminate\View\View;
use Illuminate\Support\Str;

class GoogleBotComposer
{
    public function compose(View $view)
    {
        $article = $view->article;
        $url = env('APP_URL'); //site url

        $publisher_logo = $url . '/images/logos/' . app(Brand::class)->machine_name . '-logo.png'; //set to brand logo


        $article_image = $article->getMediaUrl() ?? $publisher_logo; //set to article fearuted image or brand logo if article doesn't have an image

        $headline = Str::limit(strip_tags($article->getHeadline()), 107, '...');
        //trim headline to 110 chars adn add elipsis

        $description = strip_tags($article->getPrecis());
        $date_modified = $article->getLastUpdateDate('Y-m-d H:i:s');
        $date_published = $article->getPublicationDate('Y-m-d H:i:s');

        $publisher = app(Brand::class)->title;

        $authors = [];

        if ($article->getAuthors()->count()) {
            foreach ($article->getAuthors() as $author) {
                $authors[] = [
                    "@type" => "Person",
                    "name" => $author->getName()
                ];
            }
        } else {
            $authors = [
                "@type" => "Organization",
                "name" => $publisher
            ];
        }

        $structured_data = [
            "@context" => "http://schema.org",
            "@type" => "NewsArticle",
            "mainEntityOfPage" => [
                "@type" => "WebPage",
                "@id" => $url
            ],
            "headline" => $headline,
            "image" => [
                "@type" => "ImageObject",
                "url" => $article_image,
            ],
            "datePublished" => $date_published,
            "dateModified" => $date_modified,
            'author' => $authors,
            "publisher" => [
                "@type" => "Organization",
                "name" => $publisher,
                "logo" => [
                    "@type" => "ImageObject",
                    "url" => $publisher_logo,
                ],
            ],
            "description" => $description,
            "isAccessibleForFree" => 'False',
            "hasPart" => [
                "@type" => "WebPageElement",
                "isAccessibleForFree" => "False",
                "cssSelector" => ".paywall"
            ],
        ];

        $view->with('structured_data', json_encode($structured_data));
    }
}
