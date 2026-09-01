<?php

namespace App\Livewire\Homes\Concerns;

use App\Models\CommunicationLog;
use App\Models\HomesPin;
use App\Models\HousingJob;
use Livewire\Attributes\Computed;

/**
 * Pin/unpin support for Housing-portal screens.
 *
 * A pin is personal and cross-screen: Today shows every pin, and each list
 * screen lifts its own pins above the list. Both list screens and both
 * detail screens toggle through the same entry point, so the button behaves
 * identically wherever it appears.
 *
 * The consuming component must also use WithPermissionChecks (every Homes
 * component already does) — pinning is gated on the same permission that
 * gates viewing the record.
 */
trait HasPins
{
    /** Pinned ids for the current user, keyed by pin type. */
    #[Computed]
    public function pinnedIds(): array
    {
        $userId = auth()->id();

        return [
            HomesPin::TYPE_CALL => $userId ? HomesPin::idsFor($userId, HomesPin::TYPE_CALL) : [],
            HomesPin::TYPE_PROJECT => $userId ? HomesPin::idsFor($userId, HomesPin::TYPE_PROJECT) : [],
        ];
    }

    public function isPinned(string $type, int $id): bool
    {
        // Property access hits Livewire's per-request computed cache.
        return in_array($id, $this->pinnedIds[$type] ?? [], true);
    }

    /**
     * Flip a pin. Silently ignores unknown types and records the user may
     * not see — a stale button in a cached page should never 500 or leave a
     * dangling pin behind.
     */
    public function togglePin(string $type, int $id): void
    {
        $permission = HomesPin::permissionFor($type);
        if ($permission === null) {
            return;
        }

        $this->authorizeAction($permission);

        if (! $this->pinTargetExists($type, $id)) {
            return;
        }

        $pinned = HomesPin::toggle(auth()->id(), $type, $id);

        unset($this->pinnedIds);

        session()->flash('homes_message', $pinned
            ? ($type === HomesPin::TYPE_CALL ? 'Pinned this call to your dashboard.' : 'Pinned this project to your dashboard.')
            : 'Removed from your pins.');
    }

    protected function pinTargetExists(string $type, int $id): bool
    {
        return match ($type) {
            HomesPin::TYPE_CALL => CommunicationLog::whereKey($id)->exists(),
            HomesPin::TYPE_PROJECT => HousingJob::whereKey($id)->exists(),
            default => false,
        };
    }
}
