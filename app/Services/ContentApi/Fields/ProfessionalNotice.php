<?php

namespace App\Services\ContentApi\Fields;

class ProfessionalNotice
{
    private $fields;

    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    public function hasProfessionalNotice(): bool
    {
        return $this->fields['HasProfessionalNotice'] ?? false;
    }

    public function getText(): string
    {
        return $this->fields['Text'] ?? '';
    }

    public function getImageLink(): string
    {
        return $this->fields['ImageLink'] ?? '';
    }

    public function hasLogo(): bool
    {
        return ! empty($this->getLogoLink());
    }

    public function getLogoLink(): string
    {
        return $this->fields['LogoLink'] ?? '';
    }
}
