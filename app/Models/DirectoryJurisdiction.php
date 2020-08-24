<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class DirectoryJurisdiction extends Model
{
    use HasSlug;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'uuid',
        'region',
        'description',
        'barrister_experts',
        'other_experts',
        'directory_id'
    ];

    protected $visible = [
        'region',
        'name',
        'description',
        'barrister_experts',
        'other_experts',
        'rankings',
        'editorials',
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->allowDuplicateSlugs()
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    /* Relations */

    public function directory()
    {
        return $this->belongsTo(Directory::class);
    }

    public function editorials()
    {
        return $this->hasMany(DirectoryEditorial::class)
            ->with('firm.profile')
            ->select('directory_editorials.*')
            ->orderBy('name');
    }

    public function firms()
    {
        return $this->hasMany(DirectoryRanking::class)
            ->with('rankable.profile')
            ->select('directory_rankings.*')
            ->join('directory_firms', 'directory_firms.id', '=', 'directory_rankings.rankable_id')
            ->where('rankable_type', DirectoryFirm::class)
            ->orderBy('directory_rankings.group_order')
            ->orderBy('directory_rankings.group_name')
            ->orderBy('directory_rankings.rank')
            ->orderBy('name');
    }

    public function individuals()
    {
        return $this->hasMany(DirectoryRanking::class)
            ->with('rankable.profile')
            ->select('directory_rankings.*')
            ->where('rankable_type', DirectoryIndividual::class)
            ->join('directory_individuals', 'directory_individuals.id', '=', 'directory_rankings.rankable_id')
            ->orderBy('directory_rankings.group_order')
            ->orderBy('directory_rankings.group_name')
            ->orderBy('directory_rankings.rank')
            ->orderBy('directory_individuals.surname')
            ->orderBy('directory_individuals.first_names');
    }

    /* Helpers */

    public function getFlagCssClassAttribute()
    {
        //convert slug so a more readable format accepted by "country to code" translation
        $country_name = ucwords(str_replace('-', ' ', $this->slug));

        //if has a normal format which returns a country code out of the box
        if ($code = \Lang::has('countries.reversed_list.' . $country_name)) {
            return trans('countries.reversed_list.' . $country_name);
        }

        // if has a bizarre format, or isn't a country, map it manually to the "country to code" translation or give it a special code
        $special_case_lookup = [
            'china-and-sars-china-domestic' => trans('countries.reversed_list.China'),
            'china-and-sars-china' => trans('countries.reversed_list.China'),
            'china-and-sars-china-foreign' => trans('countries.reversed_list.China'),
            'china-domestic' => trans('countries.reversed_list.China'),
            'china-foreign' => trans('countries.reversed_list.China'),
            'china-and-sars-hong-kong' => trans('countries.reversed_list.Hong Kong'),
            'china-and-sars-macau' => trans('countries.reversed_list.Macau'),
            'south-korea-domestic' => trans('countries.reversed_list.Republic of Korea'),
            'south-korea-foreign' => trans('countries.reversed_list.Republic of Korea'),
            'south-korea' => trans('countries.reversed_list.Republic of Korea'),
            'benelux-agencies' => 'special-benelux-agencies',
            'benelux-belgium' => trans('countries.reversed_list.Belgium'),
            'benelux-luxembourg' => trans('countries.reversed_list.Luxembourg'),
            'benelux-netherlands' => trans('countries.reversed_list.Netherlands'),
            'japan-domestic' => trans('countries.reversed_list.Japan'),
            'japan-foreign' => trans('countries.reversed_list.Japan'),
            'russia' => trans('countries.reversed_list.Russian Federation'),
            'serbia' => trans('countries.reversed_list.Republic of Serbia'),
            'united-kingdom-england' => trans('countries.reversed_list.United Kingdom'),
            'united-kingdom-scotland' => trans('countries.reversed_list.United Kingdom'),
            'international' => 'special-international',
            'anti-counterfeiting' => 'special-anti-counterfeiting',
            'caribbean' => 'special-caribbean',
            'luxemburg' => trans('countries.reversed_list.Luxembourg'),
            'united-states-arizona' => 'special-state-arizona',
            'united-states-california' => 'special-state-california',
            'united-states-colorado' => 'special-state-colorado',
            'united-states-dc-metro-area' => 'special-state-dc-metro-area',
            'united-states-florida' => 'special-state-florida',
            'united-states-georgia' => 'special-state-georgia',
            'united-states-illinois' => 'special-state-illinois',
            'united-states-indiana' => 'special-state-indiana',
            'united-states-massachusetts' => 'special-state-massachusetts',
            'united-states-michigan' => 'special-state-michigan',
            'united-states-minnesota' => 'special-state-minnesota',
            'united-states-national' => 'special-state-national',
            'united-states-new-jersey' => 'special-state-new-jersey',
            'united-states-new-york' => 'special-state-new-york',
            'united-states-north-carolina' => 'special-state-north-carolina',
            'united-states-ohio' => 'special-state-ohio',
            'united-states-oregon' => 'special-state-oregon',
            'united-states-pennsylvania' => 'special-state-pennsylvania',
            'united-states-texas' => 'special-state-texas',
            'united-states-washington' => 'special-state-washington',
            'united-states-wisconsin' => 'special-state-wisconsin',
            'united-states-delaware' => 'special-state-delaware',
            'united-states-utah' => 'special-state-utah',
            'united-states-expert-witnesses' => 'special-state-national',
            'european-patent-office' => 'european-patent',
        ];

        return $special_case_lookup[$this->slug] ?? 'special-case-unknown';
    }
}
