<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Permission as BasePermision;

class Feature extends BasePermision
{
    const TYPE_NEWSLETTER = 'Newsletter';
    const TYPE_NEWS = 'News';
    const TYPE_FEATURES = 'Features';
    const TYPE_CONFERENCE_REPORT = 'Conference report';
    const TYPE_INTERVIEW = 'Interviews';
    const TYPE_REGIONAL_REVIEWS = 'Regional Reviews';
    const TYPE_GUIDES = 'Guides';
    const TYPE_SURVEYS = 'Surveys';
    const TYPE_MAGAZINE_CONTENT = 'Magazine content';
    const TYPE_MAGAZINE_DOWNLOAD = 'Magazine download';
    const TYPE_GXR_100_CURRENT =  'GXR 100 (Current)';
    const TYPE_GXR_100_ARCHIVE =  'GXR 100 (Archive)';
    const TYPE_GTDT = 'GTDT';
    const TYPE_HORIZONS = 'Horizons';
    const TYPE_REPORTS_TOOL_BASIC = 'Basic Reports';
    const TYPE_REPORTS_TOOL_STANDARD = 'Standard Reports';
    const TYPE_REPORTS_TOOL_PREMIUM = 'Premium Reports';
    const TYPE_MANDATES = 'Mandates';
    const TYPE_RECOGNITIONS_TOOL = 'Recognitions Tool';
    const TYPE_PRIMARY_DOCUMENTS_TOOL = 'Primary Documents Tool';
    const TYPE_ENFORCER_TRACKER = 'Enforcer Tracker';
    const TYPE_VERTICAL_RESTRAINTS = 'Vertical Restraints';
    const TYPE_GCR_USA = 'GCR USA';
    const TYPE_GCR_ASIA = 'GCR Asia';

    public static $available = [
        self::TYPE_NEWSLETTER,
        self::TYPE_NEWS,
        self::TYPE_FEATURES,
        self::TYPE_CONFERENCE_REPORT,
        self::TYPE_INTERVIEW,
        self::TYPE_REGIONAL_REVIEWS,
        self::TYPE_GUIDES,
        self::TYPE_SURVEYS,
        self::TYPE_MAGAZINE_CONTENT,
        self::TYPE_MAGAZINE_DOWNLOAD,
        self::TYPE_GXR_100_CURRENT,
        self::TYPE_GXR_100_ARCHIVE,
        self::TYPE_GTDT,
        self::TYPE_HORIZONS,
        self::TYPE_REPORTS_TOOL_BASIC,
        self::TYPE_REPORTS_TOOL_STANDARD,
        self::TYPE_REPORTS_TOOL_PREMIUM,
        self::TYPE_MANDATES,
        self::TYPE_RECOGNITIONS_TOOL,
        self::TYPE_PRIMARY_DOCUMENTS_TOOL,
        self::TYPE_ENFORCER_TRACKER,
        self::TYPE_VERTICAL_RESTRAINTS,
        self::TYPE_GCR_USA,
        self::TYPE_GCR_ASIA,
    ];

    protected $table = 'permissions';

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('guard', function (Builder $builder) {
            $builder->where('guard_name', 'web');
        });
    }
}
