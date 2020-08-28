<?php

namespace App\Models;

use App\Models\Interfaces\Importable as ImportableInterface;
use App\Models\Traits\Importable as ImportableTrait;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

/**
 * @property integer id
 * @property string  machine_name
 * @property Import  import
 * @property array   email_from
 */
class Brand extends Model implements ImportableInterface
{
    use ImportableTrait;

    public $timestamps = false;

    /* Overrides */

    public function __get($key)
    {
        $value = parent::__get($key);

        if (! $value) {
            // See if this key exists in the config and return it if it does. This means we don't have a tonne of mutators in this file any more.
            $brand_config = config('hosts.' . $this->machine_name);

            if ($brand_config && array_key_exists($key, $brand_config)) {
                return $brand_config[$key];
            }
        }

        return $value;
    }

    /* Relations */
    public function directories()
    {
        return $this->hasMany(Directory::class);
    }

    public function emails()
    {
        return $this->hasMany(Email::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_brands');
    }

    public static function getEntityBundle()
    {
        return 'brand';
    }

    public function updateFromDrupal(array $entity, OutputStyle $output)
    {
        return $this; // Brands aren't actually updatable but they do appear in =
    }

    public function getRouteKeyName()
    {
        return 'machine_name';
    }

    /* Statics */

    public static function findByMachineNameOrFail(string $machine_name)
    {
        return static::where('machine_name', $machine_name)->firstOrFail();
    }

    public static function findByMachineName(string $machine_name)
    {
        try {
            return static::findByMachineNameOrFail($machine_name);
        } catch (ModelNotFoundException $exception) {
            return null;
        }
    }

    /**
     * @param string $host
     * @return false|int|string
     */
    public static function concludeActiveHost(string $host)
    {
        if (Str::contains($host, 'admin')) {
            return 'admin';
        }

        $hostMapping = [
            env('BRAND_GAR_HOST', 'globalarbitrationreview.com') => 'gar',
            env('BRAND_GBRR_HOST', 'globalbankingregulationreview.com') => 'gbrr',
            env('BRAND_GCR_HOST', 'globalcompetitionreview.com') => 'gcr',
            env('BRAND_GDR_HOST', 'globaldatareview.com') => 'gdr',
            env('BRAND_GRR_HOST', 'globalrestructuringreview.com') => 'grr',
            env('BRAND_GIR_HOST', 'globalinvestigationsreview.com') => 'gir',
            env('BRAND_IAM_HOST', 'www.iam-media.com') => 'iam',
            env('BRAND_WTR_HOST', 'www.worldtrademarkreview.com') => 'wtr',
        ];

        if (isset($hostMapping[$host])) {
            return $hostMapping[$host];
        }

        foreach ($hostMapping as $hostInitials) {
            if (Str::contains($host, $hostInitials)) {
                return $hostInitials;
            }
        }

        return 'admin';
    }

    /* Helpers */

    public function machineNameIs(string $brand_machine_name)
    {
        return $brand_machine_name === $this->machine_name;
    }

    public function getLastestMagazineIssue()
    {
        static $latest_magazine = null;

        if (! $latest_magazine) {
            $latest_magazine = $this->magazines()->RegularEditions()->latest('issue')->whereHas('articles')
                /*->where('date','<=',\DB::raw('now()'))*/ //removed as need to sort out published information
                ->first();
        }

        return $latest_magazine;
    }
}
