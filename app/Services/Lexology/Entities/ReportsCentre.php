<?php

declare(strict_types=1);

namespace App\Services\Lexology\Entities;

use App\Auth\Traits\ComparesPermissions;
use App\Models\Brand;
use App\Models\Feature;
use App\Models\User;
use App\Services\ContentApi\Interfaces\RequiresPermissions;
use App\Services\Lexology\Service;
use Illuminate\Support\Facades\Auth;

final class ReportsCentre implements RequiresPermissions
{
    use ComparesPermissions;

    protected $client;
    protected $token;
    protected $reactAppId = 'reports-centre';
    protected $reactTheme;
    protected $scriptUrl = 'https://www.lexology.com/embedded/app/reports-centre?tk=';
    protected $appRootPath;

    public function __construct(Service $client, Brand $brand)
    {
        $this->client = $client;
        $this->reactTheme = 'theme-' . strtoupper($brand->machine_name);

        $appRootPath = '/tools/reports-centre';

        $this->appRootPath = $appRootPath;
    }

    public function getToken(): string
    {
        if (! $this->token) {
            $payload = $this->makePayloadStruct();
            $this->token = $this->client->getToken($this->reactAppId, $payload);
        }
        return $this->token;
    }

    public function getReactApiKey(): string
    {
        return $this->reactApiKey;
    }

    public function getReactAppId(): string
    {
        return $this->reactAppId;
    }

    public function requiresPermissions(): array
    {
        return [Feature::TYPE_REPORTS_TOOL_BASIC, Feature::TYPE_REPORTS_TOOL_STANDARD, Feature::TYPE_REPORTS_TOOL_PREMIUM];
    }

    public function scriptSrc(): string
    {
        return $this->scriptUrl . $this->getToken();
    }

    public function makeReactDiv(): string
    {
        $attributes = [
            'class' => "react-root " . e($this->reactTheme),
            'data-contentroot' => "https://www.lexology.com",
            'component' => e($this->reactAppId),
            'data-token' => e($this->getToken()),
            'data-approotpath' => e($this->appRootPath),
        ];

        return implode(' ', array_map(function ($key, $value) {
            return "{$key}='{$value}'";
        }, array_keys($attributes), $attributes));
    }

    private function makePayloadStruct(): array
    {
        $allPermissions = $this->requiresPermissions();

        if ($user = Auth::user()) {
            if ($user->isAdmin()) {
                return ['json' => ['UserFeatures' => $allPermissions]];
            }

            if (active_host('grr') || active_host('gcr')) {
                $hasAccess = [];
                $brand = Resolve(Brand::class);

                foreach ($allPermissions as $permission) {
                    if ($user->hasAccessToFeatures(array($permission), $brand)) {
                        $hasAccess[] = $permission;
                    }
                }
                return ['json' => ['UserFeatures' => $hasAccess]];
            }

            if ($user->isSubscriber()) {
                return ['json' => ['UserFeatures' => $allPermissions]];
            }
        }

        return ['json' => ['UserFeatures' => []]];
    }

    public function canView(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isSubscriber()) {
            return true;
        }

        return false;
    }
}
