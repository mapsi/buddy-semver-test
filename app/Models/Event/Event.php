<?php

namespace App\Models\Event;

use App\Models\Brand;
use App\Models\Import;
use App\Models\Interfaces\Brandable as BrandableInterface;
use App\Models\Interfaces\HasContentSections as HasContentSectionsInterface;
use App\Models\Interfaces\Importable as ImportableInterface;
use App\Models\Interfaces\Publishable as PublishableInterface;
use App\Models\Traits\BrandableTrait as BrandableTrait;
use App\Models\Traits\HasContentSections as HasContentSectionsTrait;
use App\Models\Traits\Importable as ImportableTrait;
use App\Models\Traits\Publishable as PublishableTrait;
use DateTime;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;

class Event extends Model implements
    ImportableInterface,
    BrandableInterface,
    HasMedia,
    PublishableInterface,
    HasContentSectionsInterface
{
    use HasContentSectionsTrait;
    use PublishableTrait;
    use ImportableTrait;
    use BrandableTrait;
    use HasMediaTrait;

    protected $guarded = ['id'];

    protected $dates = [
        'date_start',
        'date_end',
    ];

    /**
     * @return array
     */
    public static function getGroups(): array
    {
        return [
            'about_highlights' => [
                'field' => 'field_key_highlights',
                'image' => true,
                'file' => false,
            ],
            'about_who' => [
                'field' => 'field_who_will_be_there',
                'image' => true,
                'file' => false,
            ],
            'pricing' => [
                'field' => 'field_pricing_content',
                'image' => true,
                'file' => false,
            ],
            'packages' => [
                'field' => 'field_packages',
                'image' => true,
                'file' => false,
            ],
            'why_speak' => [
                'field' => 'field_why_speak',
                'image' => true,
                'file' => false,
            ],
            'agenda_download' => [
                'field' => 'field_agenda_download',
                'image' => false,
                'file' => true,
            ],
            'downloadable_items' => [
                'field' => 'field_download_items',
                'image' => false,
                'file' => true,
                'thumbnail' => true,
            ],
            'accommodation' => [
                'field' => 'field_accommodation',
                'image' => true,
                'file' => false,
            ],
            'venue' => [
                'field' => 'field_venue_content',
                'image' => true,
                'file' => false,
            ],
            'travel_information' => [
                'field' => 'field_travel_information',
                'image' => true,
                'file' => false,
            ],
            'accreditation_information' => [
                'field' => 'field_accreditation_information',
                'image' => false,
                'file' => false,
            ],
            'faqs' => [
                'field' => 'field_faqs',
                'image' => false,
                'file' => false,
            ],
            'kpi' => [
                'field' => 'field_event_kpi',
                'image' => false,
                'file' => false,
            ],
            'menu_options' => [
                'field' => 'field_menu_options',
                'image' => false,
                'file' => false,
                'contentsection' => false,
            ],
        ];
    }

    /**
     * @return bool|mixed
     */
    public function getInterestedInSpeakingEmailsAttribute()
    {
        if (! $this->interested_in_speaking_email) {
            return false;
        }

        return json_decode($this->interested_in_speaking_email);
    }

    /**
     * @return bool|mixed
     */
    public function getInterestedInSponsoringEmailsAttribute()
    {
        if (! $this->interested_in_sponsoring_email) {
            return false;
        }

        return json_decode($this->interested_in_sponsoring_email);
    }

    /**
     * @param $entity
     * @param $field
     * @return null|string
     */
    protected function extractInterestedContacts($entity, $field)
    {
        $fieldPointer = "relationships.$field.data";
        $interestedContacts = Arr::get($entity, $fieldPointer, []);

        foreach ($interestedContacts as $interested) {
            $email = Arr::get($interested, 'attributes.field_email');
            $name = Arr::get($interested, 'attributes.field_name');
            $parsedInterestedContacts[$email] = $name;
        }

        $parsedInterestedContacts = array_filter($parsedInterestedContacts ?? []);
        if (empty($parsedInterestedContacts)) {
            return null;
        }

        return json_encode($parsedInterestedContacts) ?? null;
    }

    /**
     * @param array       $entity
     * @param OutputStyle $output
     * @return bool true
     * @throws \Spatie\MediaLibrary\Exceptions\FileCannotBeAdded
     */
    public function updateFromDrupal(array $entity, OutputStyle $output): bool
    {
        $start = $entity['attributes']['field_event_date']['value'] ?? null;
        $end = $entity['attributes']['field_event_date']['end_value'] ?? null;

        if ($start) {
            $start = new DateTime($start);
        }

        if ($end) {
            $end = new DateTime($end);
        }

        $fill = [
            'title' => $entity['attributes']['title'] ?? null,
            'summary' => $entity['attributes']['body']['summary'] ?? $entity['attributes']['body']['value'] ?? null,
            'body' => $entity['attributes']['body']['value'] ?? null,
            'cvent_link' => $entity['attributes']['field_cvent_link']['uri'] ?? null,
            'cvent_text' => $entity['attributes']['field_cvent_link']['title'] ?? null,
            'about_heading' => $entity['attributes']['field_about_heading'] ?? null,
            'accommodation_heading' => $entity['attributes']['field_accommodation_heading'] ?? null,
            'accreditation_heading' => $entity['attributes']['field_accreditation_heading'] ?? null,
            'analyst_commentary_heading' => $entity['attributes']['field_analyst_heading'] ?? null,
            'analyst_commentary_summary' => $entity['attributes']['field_analyst_commentary_summary']['value'] ?? null,
            'contact' => $entity['attributes']['field_contact_content']['value'] ?? null,
            'contact_heading' => $entity['attributes']['field_contact_heading'] ?? null,
            'downloadables_heading' => $entity['attributes']['field_downloadables_heading'] ?? null,
            'downloadables_summary' => $entity['attributes']['field_downloads_summary']['value'],
            'location' => $entity['attributes']['field_event_location'] ?? null,
            'management_email' => $entity['attributes']['field_event_management_email'] ?? null,
            'faq_heading' => $entity['attributes']['field_faq_heading'] ?? null,
            'speaker_heading' => $entity['attributes']['field_speakers_heading'] ?? null,
            'information_centre_heading' => $entity['attributes']['field_information_centre_heading'] ?? null,
            'news_heading' => $entity['attributes']['field_news_heading'] ?? null,
            'news_summary' => $entity['attributes']['field_news_summary']['value'] ?? null,
            'other_events_heading' => $entity['attributes']['field_other_events_heading'] ?? null,
            'other_events_summary' => $entity['attributes']['field_other_events_summary']['value'] ?? null,
            'pricing_heading' => $entity['attributes']['field_pricing_heading'] ?? null,
            'pricing_summary' => $entity['attributes']['field_pricing_summary']['value'] ?? null,
            'programme_heading' => $entity['attributes']['field_programme_heading'] ?? null,
            'resource' => $entity['attributes']['field_resource_content']['value'] ?? null,
            'resource_heading' => $entity['attributes']['field_resource_heading'] ?? null,
            'sponsor_adhoc_heading' => $entity['attributes']['field_sponsor_adhoc_heading'] ?? null,
            'sponsor_adhoc_heading_two' => $entity['attributes']['field_sponsor_adhoc_heading_two'] ?? null,
            'travel_contact' => $entity['attributes']['field_travel_contact'] ?? null,
            'travel_heading' => $entity['attributes']['field_travel_heading'] ?? null,
            'venue_lat' => $entity['attributes']['field_venue_location']['lat'] ?? null,
            'venue_long' => $entity['attributes']['field_venue_location']['lng'] ?? null,
            'venue_heading' => $entity['attributes']['field_venue_heading'] ?? null,
            'why_attend_heading' => $entity['attributes']['field_why_attend_heading'] ?? null,
            'why_attend_summary' => $entity['attributes']['field_why_attend_summary']['value'] ?? null,
            'accommodation_summary' => $entity['attributes']['field_accommodation_summary']['value'] ?? null,
            'accreditation_summary' => $entity['attributes']['field_accreditation_summary']['value'] ?? null,
            'travel_summary' => $entity['attributes']['field_travel_summary']['value'] ?? null,
            'previous_years_heading' => $entity['attributes']['field_previous_year_heading'] ?? null,
            'previous_years_summary' => $entity['attributes']['field_previous_year_summary']['value'] ?? null,
            'language_code' => $entity['attributes']['field_language_code'] ?? null,
            'language_label' => $entity['attributes']['field_language_label'] ?? null,
            'date_start' => $start ?? null,
            'date_end' => $end ?? null,
            'published_at' => $entity['attributes']['status'] ? now() : null,
            'register_now_text' => $entity['attributes']['field_register_now_text'] ?? null,
            'why_attend_video_youtube_id' => $entity['attributes']['field_why_attend_video']['video_id'] ?? null,
            'slug' => $this->generateSlug($start),
            'external' => $entity['attributes']['field_external'],
            'email_summary' => $entity['attributes']['field_email_summary']['processed'] ?? null,
            'interested_in_speaking_email' => $this->extractInterestedContacts($entity, 'field_event_speaking_contacts'),
            'interested_in_sponsoring_email' => $this->extractInterestedContacts($entity, 'field_event_sponsoring_contacts'),
        ];

        $this->fill($fill);

        $fields = [
            'field_testimonials' => [
                'class' => Testimonial::class,
                'pivot' => [],
            ],
            'field_speakers' => [
                'class' => Speaker::class,
                'pivot' => [],
            ],
            'field_programme_sessions' => [
                'class' => Session::class,
                'pivot' => [],
            ],
            'field_news' => [
                'class' => NewsItem::class,
                'pivot' => ['role' => 'normal'],
                'relationship' => 'news',
                'with_weight' => true,
            ],
            'field_analyst_commentary' => [
                'class' => NewsItem::class,
                'pivot' => ['role' => 'commentary'],
                'detach' => false,
                'relationship' => 'news',
                'with_weight' => true,
            ],
            'field_events_reference' => [
                'class' => Event::class,
                'pivot' => [],
            ],
            'field_language_reference' => [
                'class' => Event::class,
                'pivot' => ['event_language'],
            ],
            'field_previous_year' => [
                'class' => PreviousYear::class,
                'pivot' => [],
                'with_weight' => true,
            ],
            'field_banners' => [
                'class' => Banner::class,
                'pivot' => [],
            ],
        ];

        $eventTypeUUID = Arr::get($entity, 'relationships.field_event_type.data.id');

        if ($eventTypeUUID) {
            $eventType = null;

            try {
                $eventType = Import::firstByUuidOrFail($eventTypeUUID);
            } catch (ModelNotFoundException $e) {
                \Log::debug(
                    'Event: Instance of $eventTypeUUID provided but no $eventType found. Sync issue?',
                    [
                        'this' => $this,
                        'entity' => $entity,
                    ]
                );
            }

            if ($eventType) {
                $this->type_id = $eventType->importable->id;
            }
        }

        $this->save();

        foreach ($fields as $field => $settings) {
            $this->syncImportField(
                $entity['relationships'][$field]['data'] ?? [],
                $settings['class'],
                $output,
                $settings['with_weight'] ?? false,
                $settings['pivot'],
                $settings['detach'] ?? true,
                $settings['relationship'] ?? false
            );
        }

        $this->addSponsors(
            $entity['relationships']['field_sponsor_groups'],
            $entity['relationships']['field_sponsors_featured']
        );

        /** Import media - KVP of media we expect might exist for an Event */
        $media = [
            'field_event_logo' => 'logo',
            'field_other_events_image' => 'other_events_image',
            'field_previous_years_image' => 'previous_years_image',
            'field_pricing_image' => 'pricing_image',
            'field_testimonials_image' => 'testimonials_image',
            'field_why_attend_image' => 'why_attend_image',
            'field_galleries_image' => 'galleries_image',
            'field_email_image' => 'email_image',
        ];

        foreach ($media as $key => $value) {
            Import::importMediaFromDrupalAndAddToMediaCollection($entity, $key, 'field_media_image', $value, $this);
        }

        if (is_array($entity['relationships']['field_menu_options'])) {
            \App\Models\Event\MenuOption::where('event_id', $this->id)->delete();

            foreach ($entity['relationships']['field_menu_options']['data'] as $option) {
                $a = $this->MenuOptions()->updateOrCreate([
                    'itemarea' => $option['attributes']['field_machine_name'],
                    'label' => $option['attributes']['field_option_label'],
                    'enabled' => $option['attributes']['field_enabled'] == true ? true : false,
                ]);

                $a->save();
            }
        }

        return true;
    }

    /**
     * @param $startDate
     * @return string
     */
    protected function generateSlug($startDate)
    {
        if (! $startDate || ! $startDate instanceof DateTime) {
            return uniqid();
        }

        return $startDate->format('Y') . '_' . uniqid();
    }

    /**
     * @param $sponsors
     * @param $featured
     * @return bool
     */
    protected function addSponsors($sponsors, $featured): bool
    {
        $featured = $uuids = array_map(function ($item) {
            return $item['id'];
        }, $featured['data']);

        $data = [];

        foreach ($sponsors['data'] as $paragraph) {
            foreach ($paragraph['relationships']['field_para_sponsors']['data'] as $weight => $item) {
                $uuids[] = $item['id'];

                if (! isset($data[$item['id']])) {
                    $data[$item['id']] = [
                        'tier' => $paragraph['relationships']['field_para_tier']['data']['id'],
                        'weight' => $paragraph['relationships']['field_para_tier']['data']['attributes']['weight'],
                    ];
                }
            }
        }

        $tiersObjects = SponsorTier::findUuidsOrFail(
            collect($data)
                ->pluck('tier')
                ->filter(
                    function ($item) {
                        return $item != 'virtual';
                    }
                )
                ->unique()
                ->values()
                ->toArray()
        );

        $tiersObjects->load('import');

        $tiersObjectsGroup = $tiersObjects->groupBy(
            function ($tier) {
                return $tier->import->uuid;
            }
        );

        $sponsorsObjects = Sponsor::findUuidsOrFail(
            collect($uuids)
                ->filter(
                    function ($item) {
                        return $item != 'virtual';
                    }
                )
                ->unique()
                ->toArray()
        );

        $sponsorsObjects->load('import');

        $sync = [];

        foreach ($sponsorsObjects as $sponsor) {
            $sync[$sponsor->id] = [];

            if (isset($data[$sponsor->import->uuid])) {
                if (isset($data[$sponsor->import->uuid]['tier'])) {
                    $n = $data[$sponsor->import->uuid]['tier'];

                    if (isset($tiersObjectsGroup[$n])) {
                        $sync[$sponsor->id]['tier_id'] = $tiersObjectsGroup[$n][0]->id;
                    }
                }
            }

            $sync[$sponsor->id]['featured'] = array_search($sponsor->import->uuid, $featured) !== false;
        }

        $this->sponsorsAll()->sync($sync);

        return true;
    }

    /**
     * @return array
     */
    public static function getEntityFields(): array
    {
        return array_merge(
            self::getContentSectionFields(),
            [
                'field_testimonials',
                'field_sponsors_featured',
                'field_sponsor_groups',
                'field_sponsor_groups.field_para_tier',
                'field_sponsor_groups.field_para_sponsors',
                'field_speakers',
                'field_programme_sessions',
                'field_previous_year',
                'field_news',
                'field_event_type',
                'field_event_logo',
                'field_event_logo.field_media_image',
                'field_other_events_image',
                'field_other_events_image.field_media_image',
                'field_previous_years_image',
                'field_previous_years_image.field_media_image',
                'field_pricing_image',
                'field_pricing_image.field_media_image',
                'field_testimonials_image',
                'field_testimonials_image.field_media_image',
                'field_why_attend_image',
                'field_why_attend_image.field_media_image',
                'field_galleries_image',
                'field_galleries_image.field_media_image',
                'field_events_reference',
                'field_brand',
                'field_banners',
                'field_analyst_commentary',
                'field_faqs.field_para_faq_topic',
                'field_email_image',
                'field_email_image.field_media_image',
                'field_event_speaking_contacts',
                'field_event_sponsoring_contacts',
            ]
        );
    }

    /**
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return string
     */
    public static function getEntityType(): string
    {
        return 'node';
    }

    /**
     * @param mixed $value
     * @return Model|void
     */
    public function resolveRouteBinding($value)
    {
        $event_type = request('event_type');
        $language = request('language');

        $event = $this->where('slug', '=', $value)
            ->where('type_id', '=', $event_type->id);

        if ($language) {
            $event->where('language_code', $language);
        }

        return $event->first() ?? abort(404);
    }

    /**
     * @return string
     */
    public function getBasePath(): string
    {
        $params = [
            'event_type' => $this->type,
            'event' => $this,
        ];

        if ($this->language_code == 'en') {
            $routeName = 'events.index';
        } else {
            $routeName = 'language.events.index';
            $params['language'] = $this->language_code;
        }

        return route($routeName, $params);
    }

    /**
     * @return string
     */
    public static function getEntityBundle(): string
    {
        return 'event';
    }

    /**
     * @return bool
     */
    public function registerMediaCollections(): bool
    {
        $mediaCollections = [
            'logo',
            'download',
            'other_events_image',
            'previous_years_image',
            'pricing_image',
            'testimonials_image',
            'why_attend_image',
            'galleries_image',
        ];

        foreach ($mediaCollections as $mediaCollection) {
            $this->addMediaCollection($mediaCollection)
                ->singleFile();
        }

        return true;
    }

    /**
     * @return BelongsToMany
     */
    public function banners()
    {
        return $this->belongsToMany(Banner::class)->with('media');
    }

    /**
     * @return BelongsToMany
     */
    public function testimonials()
    {
        return $this->belongsToMany(Testimonial::class);
    }

    /**
     * all the sponsors not a thing you should use externaly normally
     */
    public function sponsorsAll()
    {
        return $this->belongsToMany(Sponsor::class)
            ->with('media')
            ->withPivot('featured', 'tier_id', 'weight')
            ->using(Pivots\TieredSponsor::class);
    }

    /**
     * all the sponsors not a thing you should use externaly normally
     */
    public function tieredSponsorsAll()
    {
        return $this->hasMany(Pivots\TieredSponsor::class)
            ->with('sponsor.media')
            ->with('tier');
    }

    /**
     * Get the normal sponsors (not adhoc nor featured)
     */
    public function tieredSponsors()
    {
        return $this->tieredSponsorsAll()->whereNotNull('tier_id');
    }

    /**
     * @return Collection
     */
    public function uniqueAndSortedTieredSponsors()
    {
        $sortingList = [
            'Platinum',
            'Gold',
            'Silver',
            'Bronze',
            'Knowledge partner',
            'Media partner',
            'Networking',
            'Supporting organisation',
            'ADHOC 1',
            'ADHOC 2',
        ];

        return $this->tieredSponsors
            ->unique('sponsor.title')
            ->sort(function ($a, $b) use ($sortingList) {
                $arrayPositionA = array_search($a->tier->name ?? '', $sortingList);
                $arrayPositionB = array_search($b->tier->name ?? '', $sortingList);

                return $arrayPositionA - $arrayPositionB;
            });
    }

    /**
     * Get the normal sponsors (not adhoc nor featured)
     */
    public function sponsors()
    {
        $param = $this->sponsorsAll()->getTable() . '.tier_id';

        return $this->sponsorsAll()->whereNotNull($param);
    }

    /**
     * Get the Featured sponsors
     */
    public function sponsorsFeatured()
    {
        return $this->sponsorsAll()->wherePivot('featured', '=', '1');
    }

    public function speakers()
    {
        return $this->belongsToMany(Speaker::class)->with('media');
    }

    public function sessions()
    {
        return $this->belongsToMany(Session::class);
    }

    public function menuOptions()
    {
        return $this->hasMany(MenuOption::class);
    }

    public function newsAll()
    {
        return $this->belongsToMany(NewsItem::class)->withPivot('role');
    }

    public function news()
    {
        return $this->newsAll()
            ->wherePivot(
                'role',
                '=',
                'normal'
            )
            ->withPivot('weight')
            ->orderByDesc('pivot_weight');
    }

    public function analystCommentary()
    {
        return $this->newsAll()
            ->wherePivot(
                'role',
                '=',
                'commentary'
            )
            ->withPivot('weight')
            ->orderByDesc('pivot_weight');
    }

    public function allEvents()
    {
        return $this->events()->withoutGlobalScope('brand');
    }

    public function events()
    {
        return $this->belongsToMany(
            self::class,
            'event_event',
            'parent_event_id',
            'event_id'
        );
    }

    public function languages()
    {
        return $this->belongsToMany(
            self::class,
            'event_language',
            'parent_event_id',
            'language_id'
        );
    }

    public function aboutYear()
    {
        return $this->belongsTo(PreviousYear::class, 'about_year_id');
    }

    public function previousYears()
    {
        return $this->belongsToMany(PreviousYear::class)
            ->withPivot('weight')
            ->orderBy('pivot_weight');
    }

    /**
     * @return mixed
     */
    public function previousYearsWithGalleries()
    {
        if (! isset($this->previousYears(true)->relations['galleries'])) {
            $this->previousYears->load('galleries');
        }

        return $this->previousYears;
    }

    public function type()
    {
        return $this->belongsTo(EventType::class, 'type_id');
    }

    /**
     * @param Session $session
     * @return int
     */
    public function getSessionDay(Session $session): int
    {
        return 1 + $this->sessions->sortBy('start_time')->first()->start_time->diffInDays($session->start_time);
    }

    /**
     * @return string
     */
    public function getCombinedDateAttribute(): string
    {
        if ($this->date_start) {
            if (! $this->date_end || $this->date_end->eq($this->date_start)) {
                return $this->date_start->format('F j Y');
            } elseif ($this->date_start->year == $this->date_end->year) {
                if ($this->date_start->month == $this->date_end->month) {
                    return $this->date_start->format('F j-' . $this->date_end->format('j') . ' Y');
                }

                return $this->date_start->format('F j - ') . $this->date_end->format('F j Y');
            } else {
                return $this->date_start->format('F j Y - ') . $this->date_end->format('F j Y');
            }
        }

        return Carbon::now()->format('F j Y');
    }

    public function aboutHighlights()
    {
        return $this->contentSectionsGroup('about_highlights');
    }

    public function aboutWho()
    {
        return $this->contentSectionsGroup('about_who');
    }

    public function pricing()
    {
        return $this->contentSectionsGroup('pricing');
    }

    public function whyAttend()
    {
        return $this->contentSectionsGroup('why_attend');
    }

    public function packages()
    {
        return $this->contentSectionsGroup('packages');
    }

    public function whySpeak()
    {
        return $this->contentSectionsGroup('why_speak');
    }

    public function agendaDownload()
    {
        return $this->contentSectionsGroup('agenda_download');
    }

    public function downloadableItems()
    {
        return $this->contentSectionsGroup('downloadable_items');
    }

    public function accommodation()
    {
        return $this->contentSectionsGroup('accommodation');
    }

    public function venue()
    {
        return $this->contentSectionsGroup('venue');
    }

    public function travelInformation()
    {
        return $this->contentSectionsGroup('travel_information');
    }

    public function accreditationInformation()
    {
        return $this->contentSectionsGroup('accreditation_information');
    }

    public function faqs()
    {
        return $this->contentSectionsGroup('faqs');
    }

    public function kpi()
    {
        return $this->contentSectionsGroup('kpi');
    }

    /**
     * @param $event
     * @param $event_type
     * @return array
     */
    public function getGateValues($event, $event_type): array
    {
        return [
            'key' => 'event_' . $event->id . '_gate',
            'value' => md5($event->date_start . ' ~ ' . $event_type->id),
        ];
    }

    /**
     * @param $event_type
     * @param $request
     * @return bool
     * @todo Maybe we should be checking type here also, as this is a password?
     */
    public function isGatePassed($event_type, $request): bool
    {
        $passed = $this->getGateValues($this, $event_type);

        return ($request->cookie($passed['key']) == $passed['value']);
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
     * @param Builder $query
     * @param string  $brandMachineName
     */
    public function scopeBrand(Builder $query, string $brandMachineName)
    {
        $brand = Brand::findByMachineNameOrFail($brandMachineName);

        $query->where('brand_id', $brand->id);
    }
}
