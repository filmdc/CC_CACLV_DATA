<?php

namespace App\Services\IntakeConversion;

use RuntimeException;

/**
 * A conversion refused for a known business reason (not an unexpected error).
 * The message is the exact user-facing text; the code tells each front-end
 * which surface to put it on (session flash vs. a specific field error).
 */
class ConversionBlocked extends RuntimeException
{
    public const ALREADY_CONVERTED = 'already_converted';

    public const NOTHING_TO_TRACK = 'nothing_to_track';

    public const MISSING_CONTACT_NAME = 'missing_contact_name';

    public const UNMET_REQUIREMENTS = 'unmet_requirements';

    public const OVERRIDE_REASON_REQUIRED = 'override_reason_required';

    public function __construct(
        public readonly string $reasonCode,
        string $message,
    ) {
        parent::__construct($message);
    }
}
