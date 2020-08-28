<?php

namespace App\Services\ContentApi\TagGroups;

use App\Services\ContentApi\Entities\Article;

class MagazineSection extends TagGroup
{
    const TAG_GROUP_ID = 1003;

    public const TAG_ID_ARTICLES = 2570;
    public const TAG_ID_CHAPTERS = 2606;
    public const TAG_ID_COLUMNS = 2557;
    public const TAG_ID_COVER_STORY = 2558;
    public const TAG_ID_COUNTRY_CHAPTERS = 2604;
    public const TAG_ID_TRADEMARK_INTELLIGENCE = 2587;
    public const TAG_ID_FEATURES = 2491;
    public const TAG_ID_NEWS = 2569;
    public const TAG_ID_ROUNDTABLE = 2603;
    public const TAG_ID_COUNTRY_CORRESPONDENT = 2592;
    public const TAG_ID_EUROPE = 2835;
    public const TAG_ID_MANAGEMENT_REPORT = 2836;
    public const TAG_ID_FOCUS = 2837;
    public const TAG_ID_INDUSTRY_INSIGHT = 2607;
    public const TAG_ID_SPECIAL_FOCUS = 2608;
    public const TAG_ID_INTRODUCTION = 2609;
    public const TAG_ID_REGIONAL_FOCUS = 2610;
    public const TAG_ID_DIRECTORY = 2611;
    public const TAG_ID_COUNTRY_Q_AS = 2712;
    public const TAG_ID_EUROPE_FOCUS = 2723;
    public const TAG_ID_INSIGHTS = 2831;
    public const TAG_ID_DATA_CENTRE = 2832;
    public const TAG_ID_MONETISATION_AND_STRATEGY = 2833;
    public const TAG_ID_COUNTRY_BY_COUNTRY = 2834;
    public const TAG_ID_INTERNATIONAL = 2841;
    public const TAG_ID_COPUBLISHED = 2842;

    public const DEFAULT_WEIGHTS = [
        'Feature' => -3,
        'News' => -2,
        'Column' => -1,
    ];

    public static $title = [
        self::TAG_ID_ARTICLES => 'Articles',
        self::TAG_ID_CHAPTERS => 'Chapters',
        self::TAG_ID_COLUMNS => 'Columns',
        self::TAG_ID_COVER_STORY => 'Cover story',
        self::TAG_ID_COUNTRY_CHAPTERS => 'Country chapters',
        self::TAG_ID_TRADEMARK_INTELLIGENCE => 'Trademark intelligence',
        self::TAG_ID_FEATURES => 'Features',
        self::TAG_ID_NEWS => 'News',
        self::TAG_ID_ROUNDTABLE => 'Roundtable',
        self::TAG_ID_COUNTRY_CORRESPONDENT => 'Country correspondent',
        self::TAG_ID_EUROPE => 'Europe',
        self::TAG_ID_MANAGEMENT_REPORT => 'Management report',
        self::TAG_ID_FOCUS => 'Focus',
        self::TAG_ID_INDUSTRY_INSIGHT => 'Industry insight',
        self::TAG_ID_SPECIAL_FOCUS => 'Special focus',
        self::TAG_ID_INTRODUCTION => 'Introduction',
        self::TAG_ID_REGIONAL_FOCUS => 'Regional focus',
        self::TAG_ID_DIRECTORY => 'Directory',
        self::TAG_ID_COUNTRY_Q_AS => 'Country Q&As',
        self::TAG_ID_EUROPE_FOCUS => 'Europe focus',
        self::TAG_ID_INSIGHTS => 'Insights',
        self::TAG_ID_DATA_CENTRE => 'Data centre',
        self::TAG_ID_MONETISATION_AND_STRATEGY => 'Monetisation and strategy',
        self::TAG_ID_COUNTRY_BY_COUNTRY => 'Country by country',
        self::TAG_ID_INTERNATIONAL => 'International',
        self::TAG_ID_COPUBLISHED => 'Co-published',
    ];
}
