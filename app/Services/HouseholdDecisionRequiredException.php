<?php

namespace App\Services;

/**
 * Thrown when deleting a participant who heads a household that still has other
 * members, and the caller has not yet chosen how to resolve it (promote a new head
 * or delete anyway). Lets the UI prompt the admin rather than deleting silently.
 */
class HouseholdDecisionRequiredException extends \RuntimeException {}
