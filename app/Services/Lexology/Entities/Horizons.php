<?php

declare(strict_types=1);

namespace App\Services\Lexology\Entities;

use App\Auth\Traits\ComparesPermissions;
use App\Models\Brand;
use App\Models\Feature;
use App\Models\SubscriptionLevel;
use App\Models\User;
use App\Services\ContentApi\Interfaces\RequiresPermissions;
use App\Services\Lexology\Service;

final class Horizons implements RequiresPermissions
{
    use ComparesPermissions;

    protected $client;
    protected $token;
    protected $reactAppId = 'horizons';
    protected $reactTheme;
    protected $scriptUrl = 'https://www.lexology.com/embedded/app/horizons?tk=';
    protected $appRootPath;

    public function __construct(Service $client, Brand $brand)
    {
        $this->client = $client;
        $this->reactTheme = 'theme-' . strtoupper($brand->machine_name);

        $appRootPath = '/tools/horizons';
        if (in_array($brand->machine_name, ['iam', 'wtr'])) {
            $appRootPath = '/horizons';
        }

        $this->appRootPath = $appRootPath;
    }

    public function getToken(): string
    {
        if (! $this->token) {
            $this->token = $this->client->getToken($this->reactAppId);
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
        return [Feature::TYPE_HORIZONS];
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

    public function canView(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (SubscriptionLevel::usingThis(active_host())) {
            return $this->comparePermissions($user);
        }

        if ($user->isSubscriber()) {
            return true;
        }

        return false;
    }
}
