<?php

namespace App\Services\IntakeConversion;

/**
 * Explicit inputs for converting an intake CommunicationLog into a HousingJob.
 *
 * Both front-ends (main-app CommunicationsLogManagement and the housing
 * portal's call detail) build one of these from their own UI state, so the
 * conversion itself has a single, headless implementation.
 */
class ConversionInput
{
    public function __construct(
        public readonly string $jobTitle,
        public readonly ?string $jobDescription,
        /** Program kept only for project access control; auto-derived by callers. */
        public readonly ?int $programId,
        /** 'create_new' or an existing participant id (numeric string/int). */
        public readonly string|int $participantAction,
        /** 'create_new', 'skip', or an existing property id (numeric string/int). */
        public readonly string|int $propertyAction,
        public readonly bool $requirementOverride = false,
        public readonly ?string $overrideReason = null,
    ) {}
}
