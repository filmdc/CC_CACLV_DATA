#!/usr/bin/env bash
# One-command regression runner for P-33.
#
# Runs only tests tagged ->group('regression') against the test DB
# (CC_CACLV_DATA_test, routed via phpunit.xml — NOT .env). Suitable for
# running before every merge, after composer update, and after migrations.
#
# Flags (forwarded to php artisan test):
#   --filter=<pattern>   narrow to a subset (e.g. --filter=SmokeRoutes)
#   --stop-on-failure    bail out after the first failure
#
# Environment:
#   REGRESSION_UPDATE_SNAPSHOTS=1   force-rewrite every HTML snapshot after
#                                    an intentional UI change
#
# Exit status: 0 on success, non-zero on any test failure or missing
# prerequisite.

set -euo pipefail

cd "$(dirname "$0")/.."

if [[ ! -f artisan ]]; then
    echo "run-regression.sh: could not locate Laravel artisan — are you in the project root?" >&2
    exit 1
fi

echo "→ Running regression group (tests/Feature/Regression/**) …"
echo "  Test DB: CC_CACLV_DATA_test (via phpunit.xml overrides)"
echo

exec php artisan test --group=regression "$@"
