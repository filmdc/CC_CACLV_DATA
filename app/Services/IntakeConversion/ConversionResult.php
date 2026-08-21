<?php

namespace App\Services\IntakeConversion;

use App\Models\HousingJob;

class ConversionResult
{
    public function __construct(
        public readonly HousingJob $job,
        /** Human-readable breadcrumbs ("created participant: …") for the success flash. */
        public readonly array $createdResources,
        public readonly bool $propertyAssigned,
    ) {}
}
