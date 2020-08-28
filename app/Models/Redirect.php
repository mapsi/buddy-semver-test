<?php

namespace App\Models;

use Cache;
use App\Exceptions\ImportAbortedException;
use App\Models\Traits\Importable as ImportableTrait;
use App\Models\Interfaces\Importable as ImportableInterface;
use DateTime;
use Facades\App\Classes\Drupal;
use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PDOException;

class Redirect extends Model implements ImportableInterface, Responsable
{
    use ImportableTrait;

    public $fillable = [
        'old',
        'new',
        'code',
    ];
    public static function getEntityType()
    {
        return 'redirect';
    }
    /* Overrides */

    public static function getEntityBundle()
    {
        return 'redirect'; // We don't actually use this because we're not using the JSON API endpoint in importFromDrupal()
    }

    public static function importFromDrupal(OutputStyle $output, DateTime $since_date = null)
    {
        $progress_bar = static::getProgressBar($output);
        $progress_bar->start();

        if (! $since_date) {
            $since_date = Import::getLastUpdatedAt(static::class);
        }

        $entities = Drupal::getRedirects($since_date);

        foreach ($entities as $entity) {
            if (Cache::pull('stop_content_import')) { // We can signal the importer to abort. See App\Console\Commands\ContentUpdate
                logger('Stopped import.');

                throw new ImportAbortedException();
            }

            try {
                $import = Import::whereUuidIs($entity['uuid'])->with('importable')->firstOrFail();

                $redirect = $import->importable;
            } catch (ModelNotFoundException $exception) {
                $redirect = new static();
            }

            try {
                $redirect->updateFromDrupal($entity, $output);
            } catch (PDOException $exception) {
                logger('Could not add ' . json_encode($entity));
                continue; // Invalid redirect. Usually the path is too long.
            }

            // This is to maintain compatibility with the JSON API content.
            $json_api_entity = [
                'type' => 'redirect--redirect',
                'id' => $entity['uuid'],
                'attributes' => ['rid' => $entity['id']],
            ];

            Import::register($redirect, $json_api_entity, new DateTime($entity['created']));

            $progress_bar->advance();
        }

        $progress_bar->finish();
    }

    public function updateFromDrupal(array $entity, OutputStyle $output)
    {
        $this->fill([
            'old' => $entity['from'],
            'new' => $entity['to'],
            'code' => $entity['code'],
        ]);

        return $this->save();
    }

    public function toResponse($request)
    {
        if (! $brand = resolve(Brand::class)) {
            return null;
        }

        // We need to check this redirects to our active brand.

        $exploded = explode('/', $this->new);

        if (isset($exploded[1]) && strpos($exploded[1], $brand->machine_name) !== false) { // Sometimes, we get '/wtr-iam/...' for shared content
            $destination = '/' . implode('/', array_slice($exploded, 2));

            return redirect($destination, $this->code);
        }

        abort(404);
    }

    /* Helpers */

    public static function findByPath($path)
    {
        if (! $brand = resolve(Brand::class)) {
            return null;
        }

        if (strpos($path, '/') !== 0) {
            $path = '/' . $path;
        }

        $branded_path = '/' . $brand->machine_name . $path;


        $r = static::where('old', $branded_path)
            ->orWhere('old', '/wtr-iam' . $path) // A dirty hack before launch.
            ->orWhere('old', $path)
            ->first();

        if ($r) {
            return $r;
        } else {
            $url = parse_url($path);
            if (! empty($url['query'])) {
                $query = explode('&', $url['query']);
                $out_query = [];
                foreach ($query as $item) {
                    if (substr($item, 0, 3) != 'utm' && substr($item, 0, 2) != 'c=') {
                        $out_query[] = $item;
                    }
                }
                $url['query'] = implode('&', $out_query);

                $path = ($url['path'] ?? '') . '?' . $url['query'];
                $branded_path = '/' . $brand->machine_name . $path;
                return static::where('old', $branded_path)
                ->orWhere('old', '/wtr-iam' . $path) // A dirty hack before launch.
                ->orWhere('old', $path)
                ->first();
            }
        }

        abort(404);
    }
}
