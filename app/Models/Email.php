<?php

namespace App\Models;

use Carbon\Carbon;
use App\Classes\EmailPresenter;
use App\Mail\LegacySubscriptionEmail;
use App\Mail\SubscriptionEmail;
use App\Newsletter\INewsletter;
use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Arr;

/**
 * @property int    id
 * @property Brand  brand
 * @property string type
 * @property string subject
 * @property Carbon scheduled_for
 * @property array  content
 */
class Email extends Model
{
    const LAST_VERSION = 2;
    const SCHEDULE_TIMEZONE = 'Europe/London';

    /**
     * @var array
     */
    protected $casts = [
        'content' => 'array',
        'campaign_data' => 'array',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'scheduled_for',
        'sent_at',
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'type',
        'subject',
        'content',
        'scheduled_for',
    ];

    /**
     * @param $scheduledFor
     * @return Carbon|null
     */
    public function getScheduledForAttribute($scheduledFor): ?Carbon
    {
        if (empty($scheduledFor)) {
            return null;
        }

        $date = Carbon::createFromFormat('Y-m-d H:i:s', $scheduledFor, 'UTC');
        $date->setTimezone(static::SCHEDULE_TIMEZONE);

        return $date;
    }

    /* Relations */
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @param $version
     * @return bool
     */
    public function isVersion($version)
    {
        return ($this->content['version'] ?? null) == $version;
    }

    public function isLastVersion()
    {
        return $this->isVersion(static::LAST_VERSION);
    }

    public function getMailable()
    {
        if ($this->isLastVersion()) {
            $data = array_merge(
                [
                    'brand' => $this->brand->machine_name,
                    'type' => $this->type,
                    'address_from' => $this->brand->email_from['address'],
                    'name_from' => $this->brand->email_from['name'],
                    'subject' => $this->subject,
                    'machine_name' => $this->brand->machine_name,
                ],
                $this->content
            );

            return new SubscriptionEmail(app(EmailPresenter::class)->build($data));
        }

        return new LegacySubscriptionEmail($this);
    }

    public function sendToReviewers()
    {
        if ($this->is_sent) {
            return false;
        }

        $recipients = $this->brand->email_reviewers;

        $mailable = $this->getMailable()->setReview();

        Mail::to($recipients)->send($mailable);

        return count($recipients);
    }

    private function localBroadcast()
    {
        $recipients = User::query()
            ->receievesEmail($this->brand, $this->type)
            ->when(
                $this->brand->machineNameIs('wtr') && $this->type === 'daily',
                function ($query) {
                    $query->subscribesToBrand($this->brand);
                }
            )
            ->count();

        SendEmails::dispatch($this);

        return $recipients;
    }

    /**
     * @param Brand $brand
     * @return $this
     */
    public function forBrand(Brand $brand)
    {
        return $this->brand()->associate($brand);
    }

    /**
     * @return string
     */
    public function getServiceFriendlyNameAttribute()
    {
        /** @var INewsletter $service */
        $service = config("newsletter.brand_provider.{$this->brand->machine_name}");

        return $service::getFriendlyName();
    }

    /**
     * @return string
     */
    public function dispatchNewsletterToService()
    {
        $newsletterService = resolve(config("newsletter.brand_provider.{$this->brand->machine_name}"));

        $campaignResponse = $newsletterService->dispatch($this, $this->getMailable());

        $this->sent_at = now();
        $this->campaign_data = $campaignResponse;
        $this->save();

        return $campaignResponse['url'];
    }

    public function scheduleNewsletter()
    {
        if (! empty($this->scheduled_for)) {
            resolve(config("newsletter.brand_provider.{$this->brand->machine_name}"))
                ->schedule(Arr::get($this->campaign_data, 'response.id'), $this->scheduled_for);
        }
    }

    public function isSent()
    {
        return ! is_null($this->sent_at);
    }

    public function pickArticles()
    {
        if ($this->status === 'sent') {
            return;
        }

        if ($this->brand->machine_name === 'wtr' && $this->type === 'daily') {
            $legal_update = $this->brand->articles()->latest('published_at')->ofType('legal update')->emailDateIsToday()->limit(3)->pluck('id')->toArray();
            $latest = $this->brand->articles()->latest('published_at')->ofType([
                'news',
                'analysis'])->ofType(['blog'])->exclude($legal_update)->limit(3)->pluck('id')->toArray();

            $this->setArticles('latest', $latest);
            $this->setArticles('legal_update', $legal_update);
        }

        if ($this->brand->machine_name === 'wtr' && $this->type === 'weekly') {
            $this->setArticles(
                'legal_update',
                $this->brand->articles()->latest('published_at')->ofType('legal update')->since('3 months ago')->limit(10)->pluck('id')->toArray()
            );
        }

        $this->save();
    }

    public function setArticles(string $section, array $ids)
    {
        $content = $this->content;

        $content[$section . '_article_ids'] = $ids;

        $this->content = $content;
    }

    public function getContributors()
    {
        return IndustryJurisdiction::whereHas('firms')->with('firms.articles')->get();
    }

    /* Mutators */

    public function getIsSentAttribute()
    {
        return $this->sent_at;
    }

    public function getSubjectAttribute()
    {
        return $this->attributes['subject'] ?? $this->brand->name . ' ' . $this->type . ' - ' . date('jS F Y');
    }

    public function getDirectoryJurisdictionAttribute()
    {
        static $directory_jurisdiction = null;

        if (! isset($this->content['directory_jurisdiction_id'])) {
            return null;
        }

        if ($directory_jurisdiction === null) {
            $directory_jurisdiction = DirectoryJurisdiction::find($this->content['directory_jurisdiction_id']);
        }

        return $directory_jurisdiction;
    }

    public function getInternationlDirectoryEntryAttribute()
    {
        static $international_directory_entry = null;

        if (! isset($this->content['international_directory_entry_id'])) {
            return null;
        }

        if ($international_directory_entry === null) {
            $international_directory_entry = InternationalDirectoryEntry::find($this->content['international_directory_entry_id']);
        }

        return $international_directory_entry;
    }

    /* Scopes */

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeSentBefore($query, DateTime $epoch)
    {
        return $query->whereNotNull('sent_at')->where('sent_at', '<', $epoch);
    }

    public function getPreviewUrl()
    {
        return route('emails.preview', ['email' => $this->id]);
    }

    public function getShowUrl()
    {
        return 'https://' . $this->brand->host . '/emails/' . $this->id;
    }
}
