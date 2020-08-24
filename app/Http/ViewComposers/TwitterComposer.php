<?php

namespace App\Http\ViewComposers;

use Illuminate\View\View;
use App\Models\Brand;
use Twitter;
use Cache;

class TwitterComposer
{
    /**
     * The user repository implementation.
     *
     * @var UserRepository
     */
    protected $users;

    /**
     * Create a new profile composer.
     *
     * @param  UserRepository  $users
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Bind data to the view.
     *
     * @param  View  $view
     * @return void
     */
    public function compose(View $view)
    {
        // brand check
        if (resolve(Brand::class)->machine_name == 'wtr') {
            $twitter_handle = 'WTRmagazine';
        } else {
            $twitter_handle = 'IAM_magazine';
        }

        if (config('ttwitter.CONSUMER_KEY')) {
            try {
                $tweets = Cache::remember('tweets-' . $twitter_handle, 2 * 60, function () use ($twitter_handle) {
                    return $this->getTweets(4, $twitter_handle);
                });
            } catch (\Exception $ex) {
                logger($ex->getMessage() . ' twitter failed to load');
                $tweets = [
                    ['id' => 'no-tweets'
                    ],
                    [
                        'id' => '',
                        'created_at' => '-',
                        'text' => '',
                        'user' => [
                            'screen_name' => '',
                        ]
                    ]
                ];
            }
        } else {
            $tweets = [
                ['id' => 'no-tweets'
                ],
                [
                    'id' => '',
                    'created_at' => '-',
                    'text' => '',
                    'user' => [
                        'screen_name' => '',
                    ]
                ]
            ];
        }

        $view->with('tweets', $tweets);
    }

    private function getTweets($number, $twitter_handle, $depth = 0)
    {
        $original_number = $number;

        $tweets = array_slice($this->getTweetsHelper($number, $twitter_handle, $depth, $original_number), 0, $original_number);

        if (! is_array($tweets) || count($tweets) <= 0) {
            $tweets[0]['id'] = 'no-tweets';
            return $tweets;
        }

        //convert plain text links to anchors
        foreach ($tweets as $key => $tweet) {
            $text = explode(' ', $tweet['text']);
            foreach ($text as $position => $word) {
                //if word containt 'http', convert it to anchor
                if (is_numeric(strpos($word, 'http'))) {
                    $text[$position] = '<a href="' . $text[$position] . '">' . $text[$position] . '</a>';
                    $tweet['text'] = implode(' ', $text);
                }
            }
            $tweets[$key]['text'] = $tweet['text'];
        }

        return $tweets;
    }

    private function getTweetsHelper($number, $twitter_handle, $depth = 0, $original_number = 0)
    {
        if ($depth > 5) {
            return [];
        }

        $tweets = Twitter::getUserTimeline(['screen_name' => $twitter_handle, 'count' => $number, 'format' => 'array', 'exclude_replies' => true]);

        if (count($tweets) < $original_number) {
            $tweets = $this->getTweetsHelper($number + 10, $twitter_handle, $depth + 1, $original_number);
        }

        return $tweets;
    }
}
