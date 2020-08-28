<?php

namespace App\Jobs;

use App\Models\Event\NewsItem;
use App\Models\Import;
use App\Models\Traits\Importable;
use Illuminate\Bus\Queueable;
use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Illuminate\Support\Str;

class UpdateContent implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    const UUID_SESSION_KEY = 'import.current_id';

    /**
     * @var array
     */
    protected $message;

    /**
     * Sorts Class names based on the bundle
     * Bundle is converted to CamelCase before checking (e.g. author_profiles -> AuthorProfiles)
     * The base entity needs to override the getEntityBundle method
     *
     * @var array
     */
    protected $entityMapping = [
        'EventNews' => NewsItem::class,
    ];

    /**
     * Finds out Classes (from bundle) when not defined in entityMapping
     * (e.g. firm -> Firm)
     *
     * @var array
     */
    protected $entityMappingFallbackNamespaces = [
        'App\Models\\',
        'App\Models\Event\\',
    ];

    /**
     * @param array $message
     */
    public function __construct(array $message)
    {
        $this->message = $message;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        try {
            $this->parseEntity();
        } catch (\Exception $exception) {
            $this->logEntityUuid('Failed to process (exception below):');
            logger($exception);

            throw $exception;
        }
    }

    /**
     * @return void
     */
    public function parseEntity()
    {
        if (! $this->isValidEntityType() || ! $this->isValidEntityBundle()) {
            return;
        }

        $this->storeEntityUuid();

        $action = $this->message['action'];
        if (Str::contains($action, ['update', 'insert'])) {
            $this->createOrUpdateEntity();

            return;
        }

        if ($action === 'delete') {
            $this->deleteEntity();

            return;
        }

        logger('Unknown message action ' . $this->message['action'] . ' ' . json_encode($this->message));
    }

    /**
     * @return void
     */
    private function storeEntityUuid()
    {
        session()->put(self::UUID_SESSION_KEY, $this->message['uuid']);
    }

    /**
     * @param string $message
     * @return void
     */
    public static function logEntityUuid($message = 'Failed to process:')
    {
        $uuid = session()->get(self::UUID_SESSION_KEY, 'NOT DEFINED');
        Log::warning("$message {$uuid}");
    }

    /**
     * @return void
     */
    private function createOrUpdateEntity()
    {
        $class = $this->buildFullyQualifiedClassName();
        if (! $class) {
            return;
        }

        /* @todo: This output being passed in the call chain is not being used and MUST be deleted */
        list($output, $progressBar) = $this->startProgressBar($class);

        DB::transaction(function () use ($class, $output, $progressBar) {
            $class::importEntityFromDrupal($output, $progressBar, [
                $this->message['uuid'] => $this->message['identifier']['id'],
            ]);
        });
    }

    /**
     * @return void
     */
    private function deleteEntity()
    {
        DB::transaction(function () {
            Import::whereUuidIs($this->message['uuid'])
                ->with(['importable' => function ($query) {
                    $query->withoutGlobalScope('is_published');
                }])
                ->get()
                ->each(function ($import) {
                    $import->importable ? $import->importable->delete() : $import->delete();
                });
        });
    }

    /**
     * @param $base
     * @return bool
     */
    protected function checkModelClass($base): bool
    {
        if (class_exists($base) && method_exists($base, 'getEntityBundle')) {
            if ($base::getEntityBundle() == $this->message['identifier']['bundle']) {
                return $base;
            }
        }

        return false;
    }

    /**
     * @return mixed|string
     */
    private function buildClassName()
    {
        $class = ucfirst(camel_case($this->message['identifier']['bundle'] ?? '-'));

        return $this->entityMapping[$class] ?? $class;
    }

    /**
     * @return bool
     */
    private function isValidEntityBundle()
    {
        return $this->message['identifier']['bundle'] !== 'user';
    }

    /**
     * @return bool
     */
    private function isValidEntityType()
    {
        return $this->message['type'] === 'entity';
    }

    /**
     * @return bool|string
     */
    private function buildFullyQualifiedClassName()
    {
        $fullClassName = $class = $this->buildClassName();

        if ($this->checkModelClass($fullClassName)) {
            return $fullClassName;
        }

        foreach ($this->entityMappingFallbackNamespaces as $namespace) {
            $fullClassName = $namespace . $class;
            if ($this->checkModelClass($fullClassName)) {
                return $fullClassName;
            }
        }

        logger(
            sprintf(
                'Unknown message entity %s does not exist %s',
                $class,
                json_encode($this->message)
            )
        );

        return false;
    }

    /**
     * @param Importable $class
     * @return array
     */
    private function startProgressBar($class)
    {
        $output = new OutputStyle(new ArrayInput([]), new BufferedOutput());
        $progressBar = $class::getProgressBar($output);
        $progressBar->start();

        return [$output, $progressBar];
    }
}
