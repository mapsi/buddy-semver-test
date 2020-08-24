<?php

namespace App\Models;

use Carbon\Carbon;
use App\Classes\Search\Presenters\ArticleSearchItem;
use App\Classes\Search\Presenters\SearchableItemPresenter;
use App\Classes\Search\SearchBuilder;
use App\Drupal\Sync\ArticleDocumentsJob;
use App\Events\ContentViewed;
use App\Models\Interfaces\Brandable;
use App\Models\Interfaces\HasContentSections as HasContentSectionsInterface;
use App\Models\Interfaces\Importable as ImportableInterface;
use App\Models\Interfaces\Publishable as PublishableInterface;
use App\Models\Interfaces\Routable as RoutableInterface;
use App\Models\Traits\BrandableTrait as BrandableTrait;
use App\Models\Traits\HasContentSections as HasContentSectionsTrait;
use App\Models\Traits\Importable as ImportableTrait;
use App\Models\Traits\Publishable as PublishableTrait;
use App\Models\Traits\Routable as RoutableTrait;
use DateTime;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\Exceptions\FileCannotBeAdded;
use Spatie\MediaLibrary\File;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\MediaLibrary\Models\Media;
use Illuminate\Support\Str;

/**
 * @property int        id
 * @property int        brand_id
 * @property int        series_id
 * @property string     title
 * @property string     headline
 * @property string     stand_first
 * @property string     site_lead
 * @property string     email_precis
 * @property Carbon     email_date
 * @property bool       is_featured
 * @property bool       is_promo
 * @property bool       is_premium
 * @property bool       is_site_lead
 * @property bool       is_sticky
 * @property int        magazine_id
 * @property int        magazine_weight
 * @property int        firm_id
 * @property string     preview_featured_image (Image URL attached dynamically for preview)
 * @property Carbon     created_at
 * @property Carbon     updated_at
 * @property Carbon     published_at
 * @property string     layout
 * @property string     canonical_url
 * @property Collection articleTypes
 * @property Collection magazineSections
 * @method static Builder withoutGlobalScopes
 * @method static Builder where
 * @package App\Models
 */
class Article extends Model implements
    Brandable,
    HasContentSectionsInterface,
    HasMedia,
    ImportableInterface,
    PublishableInterface,
    RoutableInterface
{
    use BrandableTrait;
    use HasContentSectionsTrait;
    use HasMediaTrait {
        getFirstMediaUrl as originalFirstMediaUrl;
    }
    use ImportableTrait;
    use PublishableTrait;
    use RoutableTrait;

    const FEATURED_IMAGE_COLLECTION = 'featured_image';
    const DOCUMENTS_ARTICLE_COLLECTION = 'documents_articles';

    /**
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'title',
        'email_date',
        'is_from_today',
        'headline',
        'stand_first',
        'site_lead_precis',
        'email_precis',
        'is_featured',
        'is_promoted_to_front_page',
        'is_premium',
        'brand',
        'magazine',
        'magazine_weight',
        'magazine_section',
        'contentSections',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'is_featured' => 'boolean',
        'is_promoted_to_front_page' => 'boolean',
        'is_premium' => 'boolean',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'published_at',
        'updated_at',
        'email_date',
    ];

    /**
     * @var array
     */
    protected $appends = [
        'is_from_today',
    ];

    /**
     * @return array
     */
    public function toSearchableArray()
    {
        $data = [
            'id' => $this->id,
            'model_type' => self::class,
            'title' => $this->title,
            'headline' => $this->headline,
            'weight' => 0,
            'stand_first' => $this->stand_first,
            'site_lead_precis' => $this->site_lead_precis,
            'email_precis' => $this->email_precis,
            'is_featured' => $this->is_featured,
            'is_promoted_to_front_page' => $this->is_promoted_to_front_page,
            'is_premium' => $this->is_premium,
            'brand_id' => $this->brand_id,
            'published_at' => $this->null,
            'updated_at' => $this->null,
            //TODO make as array in stead now we know how nested works,
            'content_sections' => $this->contentAsString(),
            'topics' => $this->topics->format()->toArray(),
            'sectors' => $this->sectors->format()->toArray(),
            'article_types' => $this->articleTypes->format()->toArray(),
            'authors' => $this->authors->format()->toArray(),
            'regions' => $this->regions->format()->toArray(),
            'magazine_sections' => $this->magazineSections->format()->toArray(),
            'magazine' => ($this->magazine ? [
                'id' => $this->magazine->id,
                'name' => $this->magazine->name,
            ] : null),
            'directory_jurisdictions' => null,
            'directory_id' => null,
        ];
        if ($this->published_at) {
            $data['published_at'] = $this->published_at->format('Y-m-d');
        }

        return $data;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function articleTypes()
    {
        return $this->belongsToMany(ArticleType::class);
    }

    /**
     * @return MorphOne
     */
    public function contentPiece()
    {
        return $this->morphOne(ContentPiece::class, 'contentable');
    }

    /**
     * @return mixed
     */
    public function edition()
    {
        return ($first = $this->contentPiece()->first()) ? $first->edition : null;
    }

    /**
     * @return mixed
     */
    public function series()
    {
        return $this->edition()->first()->series() ?? null;
    }

    /**
     * @return BelongsToMany
     */
    public function organizations()
    {
        return $this->belongsToMany(Firm::class, 'article_author_attributions', 'article_id', 'organization_id')
            ->using(ArticleAuthorAttribution::class);
    }

    /**
     * @param $type
     * @return bool
     */
    public function hasArticleType($type)
    {
        return $this->articleTypes->where('name', $type)->count() != 0;
    }

    /**
     * @param int $brandId
     * @return mixed
     */
    public static function mostReadFor()
    {
        return cacheStuff('most_read_articles', 60, function () {
            return Article::mostPopular()->since('18 months ago')->limit(5)->get();
        });
    }

    /**
     * @param $type
     * @return bool
     */
    public function hasMagazineSection($type)
    {
        return $this->magazineSections->where('name', $type)->count() != 0;
    }

    /**
     * @return bool
     */
    public function hasCoPublishedMagazineSection()
    {
        return $this->magazineSections->where('is_copublished', true)->count() != 0;
    }

    /**
     * @return bool
     */
    public function hasLegalUpdateType()
    {
        return $this->hasArticleType('Legal Update');
    }

    /**
     * @return BelongsToMany
     */
    public function authors()
    {
        return $this->belongsToMany(Author::class, 'article_author_attributions')
            ->using(ArticleAuthorAttribution::class)
            ->withPivot(['id', 'role']);
    }

    /**
     * @return bool
     */
    public function getIsFromTodayAttribute()
    {
        return $this->attributes['email_date'] === now()->format('Y-m-d');
    }

    /**
     * @return mixed
     */
    public function getCountryAttribute()
    {
        $badregions = [
            //"Africa & Middle East" => 2117,
            //"European Union" => 2184,
            //"Europe" => 2172,
            //"Asia-Pacific" => 2149,
            //"International" => 2228,
            "Tanzania, United Republic of" => 'Tanzania',
            //"Latin America & Caribbean" => 2210,
            //"North America" => 2225,
            "United States of America" => 'United States',
            "South Korea" => 'Republic of Korea',
            "Iran, Islamic Republic of" => 'Iran',
            "CÃ´te d'Ivoire" => 'Cote D\'ivoire',
            "Montenegro" => 'Republic of Montenegro',
            // "Laos" => 'Lao People\'s Rep.',
            "Syria" => 'Syrian Arab Republic',
            "Serbia" => 'Republic of Serbia',
            "Macedonia, the former Yugoslav Republic of" => 'Macedonia',
            //"Caribbean" => 2214,
        ];
        $region = $this->regions->filter(function ($r) use ($badregions) {

            return \Lang::has('countries.reversed_list.' . (isset($badregions[$r->name]) ? $badregions[$r->name] : $r->name)) || $r->name == 'European Union' || $r->name == 'Laos' || $r->name == 'International';
        })->first();//we pick the first of them always even if there is more than one country
        return $region;
    }

    /**
     * This gets the title of the article with all possible fallbacks
     *
     * @return string
     */
    public function getTitleWithFallbackAttribute()
    {
        return isset($this->highlight->headline[0])
            ? strip_tags($this->highlight->headline[0], '<span>')
            : e($this->headline) ?: $this->title;
    }

    /**
     * @return array|bool|\Illuminate\Contracts\Translation\Translator|string|null
     */
    public function getFlagCssClassAttribute()
    {
        $badregions = [
            //"Africa & Middle East" => 2117,
            //"European Union" => 2184,
            //"Europe" => 2172,
            //"Asia-Pacific" => 2149,
            //"International" => 2228,
            "Tanzania, United Republic of" => 'Tanzania',
            //"Latin America & Caribbean" => 2210,
            //"North America" => 2225,
            "United States of America" => 'United States',
            "South Korea" => 'Republic of Korea',
            "Iran, Islamic Republic of" => 'Iran',
            "CÃ´te d'Ivoire" => 'Cote D\'ivoire',
            "Montenegro" => 'Republic of Montenegro',
            // "Laos" => 'Lao People\'s Rep.',
            "Syria" => 'Syrian Arab Republic',
            "Serbia" => 'Republic of Serbia',
            "Macedonia, the former Yugoslav Republic of" => 'Macedonia',
            //"Caribbean" => 2214,
        ];

        $region = $this->getCountryAttribute();
        if ($region) {
            if ($region->name == 'European Union') {
                return 'EUR';
            } else {
                if ($region->name == 'International') {
                    return 'INT';
                } else {
                    if ($region->name == 'Laos') {//has a . at the end of the name
                        return 'LA';
                    } else {
                        return trans('countries.reversed_list.' . (isset($badregions[$region->name]) ? $badregions[$region->name] : $region->name));
                    }
                }
            }
        } else {
            return false;
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function emails()
    {
        return $this->belongsToMany(Email::class);
    }

    /**
     * @return BelongsTo
     */
    public function firm()
    {
        return $this->belongsTo(Firm::class);
    }

    /**
     * @return BelongsTo
     */
    public function magazine()
    {
        return $this->belongsTo(Magazine::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function magazineSections()
    {
        return $this->belongsToMany(MagazineSection::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function regions()
    {
        return $this->belongsToMany(Region::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function reports()
    {
        return $this->belongsToMany(Report::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function sectors()
    {
        return $this->belongsToMany(Sector::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function topics()
    {
        return $this->belongsToMany(Topic::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function viewCounts()
    {
        return $this->morphMany(ViewCount::class, 'countable');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function views()
    {
        return $this->morphMany(View::class, 'routable');
    }

    /* Scopes */

    /**
     * @param $query
     * @return mixed
     */
    public function scopeAlphabetically($query)
    {
        return $query->orderBy('title', 'asc');
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeEmailDateIsToday($query)
    {
        return $query->where('email_date', date('Y-m-d'));
    }

    /**
     * @param       $query
     * @param mixed ...$others
     * @return mixed
     */
    public function scopeExclude($query, ...$others)
    {
        $ids = [];

        foreach ($others as $other) {
            if (is_a($other, Model::class)) { // Is it a model?
                $ids[] = $other->id;
            } elseif (is_a($other, Collection::class)) { // Is it a collection?
                $ids = array_merge($ids, $other->pluck('id')->toArray());
            } elseif (is_array($other)) { // Is it an array?
                $ids = array_merge($ids, $other);
            } else {
                $ids[] = $other;
            }
        }

        $ids = array_unique($ids);

        return $query->whereNotIn('articles.id', $ids);
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeIsFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeIsCopublished($query)
    {
        return $query
            ->has('magazine')
            ->whereHas('magazineSections', function ($query) {
                $query->where('is_copublished', true);
            });
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeIsPreview($query)
    {
        return $query->doesntHave('routes')->doesntHave('import');
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeMostPopular($query)
    {
        return $query->withCount(['viewCounts as views_count' => function ($query) {
            $query->select(\DB::raw('SUM(count)'));
            $query->where('end', '>=', \Carbon\Carbon::now()->startOfDay()->subDays(7));
        }])->orderBy('views_count', 'desc');
    }

    /**
     * @param             $query
     * @param string|null $email_type
     * @return mixed
     */
    public function scopeNotEmailed($query, string $email_type = null)
    {
        if (is_null($email_type)) {
            return $query->whereDoesntHave('emails');
        }

        return $query->whereDoesntHave('emails', function ($query) use ($email_type) {
            $query->where('type', $email_type);
        });
    }

    /**
     * @param          $query
     * @param DateTime $epoch
     * @return mixed
     */
    public function scopeNotUpdatedSince($query, DateTime $epoch)
    {
        return $query->where('updated_at', '<', $epoch);
    }

    /**
     * @param              $query
     * @param array|string $types
     * @return mixed
     */
    public function scopeOfType($query, $types)
    {
        if (is_string($types)) {
            $types = [$types];
        }

        return $query->whereHas('articleTypes', function ($query) use ($types) {
            $query->whereIn('name', $types);

            if (in_array('analysis', $types)) {
                $query->orWhere('name', 'like', 'analysis%');
            }
        });
    }

    /**
     * @param $query
     * @param $types
     * @return mixed
     */
    public function scopeNotOfType($query, $types)
    {
        if (is_string($types)) {
            $types = [$types];
        }

        return $query->whereDoesntHave('articleTypes', function ($query) use ($types) {
            $query->whereIn('name', $types);

            if (in_array('analysis', $types)) {
                $query->orWhere('name', 'like', 'analysis%');
            }
        });
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeIsAnalysis($query)
    {
        return $query->whereHas('articleTypes', function ($query) {
            $query->where('name', 'like', 'analysis%');
        });
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeIsPromotedToHomepage($query)
    {
        return $query->where('is_promoted_to_front_page', true);
    }

    /**
     * @param $query
     * @param $since
     * @return mixed
     * @throws \Exception
     */
    public function scopeSince($query, $since)
    {
        if (is_string($since)) {
            $since = new DateTime($since);
            $since->setTime($since->format('H'), $since->format('i'), 0);
        }

        return $query->where('published_at', '>', $since);
    }

    /**
     * @param $query
     * @param $topics
     * @return mixed
     */
    public function scopeTopicIs($query, $topics)
    {
        if (is_string($topics)) {
            $topics = [$topics];
        }

        return $query->whereHas('topics', function ($query) use ($topics) {
            $query->whereIn('name', $topics);
        });
    }

    /**
     * @param $query
     * @param $magazine_section
     * @return mixed
     */
    public function scopeMagazineSectionIs($query, $magazine_section)
    {
        if (is_string($magazine_section)) {
            $magazine_section = [$magazine_section];
        }

        return $query->whereHas('magazineSections', function ($query) use ($magazine_section) {
            $query->whereIn('name', $magazine_section);
        });
    }

    /**
     * @param string $magazineSectionName
     * @return Collection
     */
    public function ofMagazineSection(string $magazineSectionName)
    {
        return $this->magazineSections
            ->filter(function ($magazineSection) use ($magazineSectionName) {
                return $magazineSection->name === $magazineSectionName;
            });
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeIsSiteLead($query)
    {
        return $query->where('is_site_lead', true);
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeStickyFirst($query)
    {
        return $query->orderBy('is_sticky', 'desc');
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopePromotedHomepageFirst($query)
    {
        return $query->orderBy('is_promoted_to_front_page', 'desc');
    }

    /**
     * @param int $limit
     * @return Collection<static>
     */
    public function relatedArticles($limit = 3)
    {
        return Article::whereHas('topics', function ($topics) {
            return $topics->whereIn('topics.id', $this->topics->pluck('id') ?? []);
        })
            ->latest('published_at')
            ->limit($limit)
            ->exclude($this->id)
            ->limit($limit)
            ->get();
    }

    /**
     * @param        $query
     * @param string $days
     * @param null   $from
     * @return mixed
     */
    public function scopeWhereEmailDateIsBetween($query, string $days, $from = null)
    {
        $from = $from ?: now();

        return $query->where('email_date', '>=', $from->subDays($days));
    }

    /**
     * @param $query
     * @param $ids
     * @return mixed
     */
    public function scopeFindManyWithQueryOrder($query, array $ids)
    {
        if (empty($filteredIds = array_filter($ids))) {
            return $query;
        }

        $orderedIds = implode(',', $filteredIds);

        return $query->whereIn('id', $filteredIds)->orderByRaw(\DB::raw("FIELD(id, $orderedIds)"));
    }

    /**
     * @param                $query
     * @param string|integer $brand
     * @return mixed
     */
    public function scopeOfBrand($query, $brand)
    {
        if (is_int($brand)) {
            return $query->where('brand_id', $brand);
        }

        return $query->whereHas('brand', function ($query) use ($brand) {
            $query->where('machine_name', $brand);
        });
    }

    /**
     * @param Builder $query
     * @return mixed
     */
    public function scopeMostRecent($query)
    {
        return $query->latest('published_at');
    }

    /**
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($article) {
            $article->articleTypes()->detach();
            $article->authors()->detach();
            $article->regions()->detach();
            $article->sectors()->detach();
            $article->topics()->detach();
        });
    }

    /**
     *
     */
    public function registerMediaCollections()
    {
        $this->addMediaCollection(static::FEATURED_IMAGE_COLLECTION)
            ->singleFile()
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('lg')->fit(Manipulations::FIT_CROP, 935, 526);
                $this->addMediaConversion('md')->fit(Manipulations::FIT_CROP, 616, 347);
                $this->addMediaConversion('sm')->fit(Manipulations::FIT_CROP, 301, 168);

                // @todo IAM/WTR conversions, for GDR we had to generate different thumbnails above
                $this->addMediaConversion('banner')->fit(Manipulations::FIT_CROP, 750, 450);
                $this->addMediaConversion('thumbnail')->fit(Manipulations::FIT_CROP, 190, 190);
                $this->addMediaConversion('letterbox')->fit(Manipulations::FIT_CROP, 355, 120);
            });

        $this->addMediaCollection('pdf')
            ->singleFile()
            ->acceptsFile(function (File $file) {
                return $file->mimeType === 'application/pdf';
            });

        $this->addMediaCollection(self::DOCUMENTS_ARTICLE_COLLECTION);
    }

    /**
     * @return string
     */
    public static function getEntityType()
    {
        return 'node';
    }

    /**
     * @return string
     */
    public static function getEntityBundle()
    {
        return 'article';
    }

    /**
     * @return array
     */
    public static function getEntityFields()
    {
        return array_merge(static::getContentSectionFields(), [
            'field_brand',
            'field_authors_roles',
            'field_authors_roles.field_author_role',
            'field_authors_roles.field_role',
            'field_authors_roles.field_organization',
            'field_author',
            'field_article_featured_image',
            'field_article_featured_image.field_media_image',
            'field_article_pdfs',
            'field_article_pdfs.field_media_file',
        ]);
    }

    /**
     * @param array       $entity
     * @param OutputStyle $output
     * @throws \Exception
     */
    public function updateFromDrupal(array $entity, OutputStyle $output)
    {
        $this->fill([
            'title' => Arr::get($entity, 'attributes.title'),
            'headline' => Arr::get($entity, 'attributes.field_article_headline.value'),
            'stand_first' => Arr::get($entity, 'attributes.field_article_stand_first.value'),
            'site_lead_precis' => Arr::get($entity, 'attributes.field_article_site_lead_precis.value'),
            'email_precis' => Arr::get($entity, 'attributes.field_article_email_precis.value'),
            'is_promoted_to_front_page' => Arr::get($entity, 'attributes.promote') ? true : false,
            'is_featured' => Arr::get($entity, 'attributes.field_article_featured') ? true : false,
            'is_premium' => Arr::get($entity, 'attributes.field_is_premium') ? true : false,
            'is_site_lead' => Arr::get($entity, 'attributes.field_article_site_lead') ? true : false,
            'is_sticky' => Arr::get($entity, 'attributes.sticky') ? true : false,
            'email_date' => Arr::get($entity, 'attributes.field_email_date'),
            'layout' => Arr::get($entity, 'attributes.field_article_layout', 'default'),
        ]);
        $this->fillPublishedAt($entity['attributes']['field_article_date'], $entity['attributes']['status']);

        $this->syncBelongsToRelationships($entity, $output);

        $this->save();

        $this->syncAttachRelationships($entity, $output);
    }

    /**
     * @param array       $entity
     * @param OutputStyle $output
     */
    protected function syncBelongsToRelationships(array $entity, OutputStyle $output): void
    {
        /* Magazine */
        $magazine_uuid = $entity['relationships']['field_magazine']['data']['id']
            ?? null;
        try {
            if ($magazine_uuid) {
                $magazine = Import::firstByUuidOrFail($magazine_uuid)->importable;
                $this->magazine()->associate($magazine);
            } else {
                $this->magazine()->dissociate();
            }
        } catch (ModelNotFoundException $ex) {
            logger('MAGAZINE NOT FOUND: ' . $magazine_uuid);
        }

        /* Firm */
        $firmUuid = $entity['relationships']['field_article_firm']['data']['id'] ?? null;

        if ($firmUuid) {
            try {
                $firm = Import::firstByUuidOrFail($firmUuid)->importable;
                $this->firm()->associate($firm);
            } catch (ModelNotFoundException $e) {
                \Log::error("Firm with id '$firmUuid' not found while importing article with id '{$entity['id']}'");
            }
        } else {
            $this->firm()->dissociate();
        }
    }

    /**
     * @param array       $entity
     * @param OutputStyle $output
     */
    protected function syncAttachRelationships(array $entity, OutputStyle $output): void
    {
        /* Magazine sections */
        $this->magazineSections()->detach();

        if ($entity['relationships']['field_magazine_section']['data']) {
            foreach ($entity['relationships']['field_magazine_section']['data'] as $drupal_magazine_section) {
                $magazine_section = Import::firstByUuidOrFail($drupal_magazine_section['id'])->importable;

                $this->magazineSections()->attach($magazine_section);
            }
        }

        /* Many-to-many relations */
        $this->syncImportField($entity['relationships']['field_article_type']['data'], ArticleType::class, $output);
        $this->syncImportField($entity['relationships']['field_region_country']['data'], Region::class, $output);
        $this->syncImportField($entity['relationships']['field_sector']['data'], Sector::class, $output);
        $this->syncImportField($entity['relationships']['field_topic']['data'], Topic::class, $output);

        $this->syncAuthorAttributions($entity);

        /* Media */
        $this->syncImages($entity, $output);

        ArticleDocumentsJob::dispatch($this, Arr::get($entity, 'relationships.field_article_pdfs.data'), $output);

        if ($pdf_url = $entity['attributes']['field_article_pdf_url'] ?? null) {
            $name = Str::slug($this->title);

            try {
                $this->addMediaFromUrl($pdf_url)
                    ->usingName($name)
                    ->usingFileName($name . '.pdf')
                    ->toMediaCollection('pdf');
            } catch (FileCannotBeAdded $exception) {
                $output->error($exception->getMessage());
            }
        }
    }

    /**
     * @param array $entityData
     * @return void
     */
    private function syncAuthorAttributions(array $entityData)
    {
        $authorProfiles = Arr::get($entityData, 'relationships.field_authors_roles.data', []);
        $authorAttributions = ArticleAuthorAttribution::buildSyncAuthorAttributionsArray($this, $authorProfiles);

        // We need to detach first because the order is important
        $this->authors()->detach();
        $this->authors()->sync($authorAttributions);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function toResponse($request)
    {
        if (! $this->isPublished()) {
            abort(404);
        }

        event(new ContentViewed($this));

        //default to show blade
        $layout = 'articles.show';
        //try to see if we have a layout and if we are given a layout that it exists.
        if ($this->layout && $this->layout != 'default') {
            $layout = 'articles.layouts.' . $this->layout;
            if (! view()->exists($layout)) {
                $layout = 'articles.show';
            }
        }

        return view($layout, [
            'active_article' => $this,
            'article' => $this,
            'trending' => static::mostReadFor($this->brand_id),
        ]);
    }

    /**
     * @param $value
     */
    public function setHeadlineAttribute($value)
    {
        $this->attributes['headline'] = strip_tags($value, '<b><strong><em><i>');
    }

    /**
     * @param $value
     */
    public function setStandFirstAttribute($value)
    {
        $this->attributes['stand_first'] = strip_tags($value, '<em><i>');
    }

    /**
     * @param $value
     */
    public function setSiteLeadPrecisAttribute($value)
    {
        $this->attributes['site_lead_precis'] = strip_tags($value, '<em><i>');
    }

    /**
     * @param $value
     */
    public function setEmailPrecisAttribute($value)
    {
        $this->attributes['email_precis'] = strip_tags($value, '<em><i>');
    }

    /**
     * @return bool
     */
    public function getIsCopublishedAttribute()
    {
        return $this->magazine
            && $this->magazineSections
            && $this->magazineSections->where('is_copublished', true)->isNotEmpty();
    }

    /**
     * @return bool
     */
    public function isFree()
    {
        return ! $this->is_premium && $this->getPaywallType() == 'free';
    }

    /**
     * @return bool
     */
    public function isPaywallLoggedIn()
    {
        return $this->getPaywallType() == 'logged_in';
    }

    /**
     * @return bool
     */
    public function isMetered()
    {
        return ! $this->is_premium && $this->getPaywallType() == 'metered';
    }

    /**
     * @return int|mixed|string
     */
    public function getPaywallType()
    {
        $rules = get_host_config('access_rules', []);
        $result = get_host_config('access_rules_default', 'subscriber');

        //go though as see if anything gets white listed
        foreach ($rules as $type => $items) {
            if ($this->$type) {
                $content = $this->$type->pluck('name', 'name');
                foreach ($items as $rule => $type) {
                    if (isset($content[$rule])) {
                        //dont go back to free if we are metered
                        if ($type == 'free-forced') {
                            return 'free';
                        }
                        if ($result != 'metered') {
                            $result = $type;
                        }
                        //dont bother with the rest as we are done
                        break;
                    }
                }
            }

            if ($type == 'metered') {
                //dont bother with the rest as we are done becuase its metered
                break;
            }
        }

        return $result;
    }

    /**
     * @return bool|null
     * @throws \Exception
     */
    public function delete()
    {
        //clean up links
        $this->articleTypes()->detach();
        $this->magazineSections()->detach();

        return parent::delete();
    }

    /**
     * @return mixed
     */
    public function isFromNewsSection()
    {
        return $this->articleTypes->pluck('name')->contains('News');
    }

    /**
     * @return mixed
     */
    public function isFromFeaturedSection()
    {
        return $this->articleTypes->pluck('name')->contains('Features');
    }

    /**
     * @return string
     */
    public function getBreadcrumbSection()
    {
        if ($this->isFromNewsSection()) {
            return lang('news');
        }

        if ($this->isFromFeaturedSection()) {
            return lang('features');
        }

        return lang('news');
    }

    /**
     * @return string
     */
    public function getBreadcrumbUrl()
    {
        if ($this->isFromNewsSection()) {
            return route('articles.news');
        }

        if ($this->isFromFeaturedSection()) {
            return route('articles.features');
        }

        return route('articles.news');
    }

    /**
     * @param       $query
     * @param       $filters
     * @param array $or
     * @return SearchBuilder
     */
    public static function searchNested($query, $filters, $or = [])
    {
        $search = static::search($query);

        foreach ($or as $filter => $values) {
            $bits = explode('.', $filter);

            if (! is_array($values)) {
                $values = [$values];
            }

            $filterpart = [
                'bool' => [
                    'should' => [],
                    'minimum_should_match' => 1,
                ],
            ];

            foreach ($values as $value) {
                $filterpart['bool']['should'][] = [
                    'term' => [
                        $filter => $value,
                    ],
                ];
            }

            if (count($bits) == 2) {
                list($area, $field) = $bits;

                $search->wheres['must'][]['nested'] = [
                    'path' => $area,
                    'query' => [
                        $filterpart,
                    ],
                ];
            } else {
                $search->wheres['must'][] = $filterpart;
            }
        }

        foreach ($filters as $filter => $value) {
            $bits = explode('.', $filter);

            if (! is_array($value)) {
                $value = [$value];
            }

            if (count($bits) == 2) {
                list($area, $field) = $bits;

                foreach ($value as $v) {
                    $search->wheres['must'][]['nested'] = [
                        'path' => $area,
                        'query' => [
                            'bool' => [
                                'must' => [
                                    'term' => [
                                        $filter => $v,
                                    ],
                                ],
                            ],
                        ],
                    ];
                }
            } else {
                foreach ($value as $v) {
                    $search->wheres['must'][] = [
                        'match' => [
                            $filter => $v,
                        ],
                    ];
                }
            }
        }

        return $search;
    }

    /**
     * @return SearchableItemPresenter
     */
    public function searchPresenter(): SearchableItemPresenter
    {
        return new ArticleSearchItem($this);
    }

    /**
     * @param array       $entity
     * @param OutputStyle $output
     */
    protected function syncImages(array $entity, OutputStyle $output)
    {
        $featuredImageData = Arr::get($entity, 'relationships.field_article_featured_image.data');
        $featuredImagePath = Arr::get($featuredImageData, 'relationships.field_media_image.data.attributes.url');
        if (! $featuredImagePath) {
            return;
        }

        try {
            $this->addMediaFromUrl($featuredImagePath)
                ->usingName(Arr::get($featuredImageData, 'attributes.name'))
                ->withCustomProperties(array_filter([
                    'caption' => Arr::get($entity, 'attributes.field_lead_image_caption'),
                    'credits' => Arr::get($featuredImageData, 'attributes.field_credits'),
                ]))
                ->toMediaCollection(static::FEATURED_IMAGE_COLLECTION);
        } catch (FileCannotBeAdded $exception) {
            $output->error($exception->getMessage());
        }
    }

    public function getFirstMediaUrl(string $collectionName = 'default', string $conversionName = ''): string
    {
        if (! empty($this->preview_featured_image)) {
            return $this->preview_featured_image;
        }

        return $this->originalFirstMediaUrl($collectionName, $conversionName);
    }

    /**
     * @return Collection
     */
    public function getDocumentsMedia(): Collection
    {
        return $this->getMedia(self::DOCUMENTS_ARTICLE_COLLECTION);
    }
}
