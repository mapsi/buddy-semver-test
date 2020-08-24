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

final class WorkAreaLanding implements RequiresPermissions
{
    use ComparesPermissions;

    protected $client;
    protected $token;
    protected $reactAppId = 'workarea-landing';
    protected $reactTheme;
    protected $scriptUrl = 'https://www.lexology.com/embedded/app/workarea-landing?tk=';
    protected $toolRoot = '/tools/gtdt/tool';
    protected $appRootPath = '/tools/gtdt';

    public function __construct(Service $client, Brand $brand)
    {
        $this->client = $client;
        $this->reactTheme = 'theme-' . strtoupper($brand->machine_name);
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
        return [Feature::TYPE_GTDT];
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
            'data-toolroot' => e($this->toolRoot),
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
