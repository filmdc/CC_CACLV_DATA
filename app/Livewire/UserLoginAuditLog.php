<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\UserLoginAudit;
use App\Traits\WithPermissionChecks;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class UserLoginAuditLog extends Component
{
    use WithPagination;
    use WithPermissionChecks;

    public $search = '';

    public $actionFilter = '';

    public $userFilter = '';

    public $dateFrom = '';

    public $dateTo = '';

    public $perPage = 25;

    protected $queryString = [
        'search' => ['except' => ''],
        'actionFilter' => ['except' => ''],
        'userFilter' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

    public function mount()
    {
        $this->authorizeAction('audit.login_view');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedActionFilter()
    {
        $this->resetPage();
    }

    public function updatedUserFilter()
    {
        $this->resetPage();
    }

    public function updatedDateFrom()
    {
        $this->resetPage();
    }

    public function updatedDateTo()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->actionFilter = '';
        $this->userFilter = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    public function getActiveSessionsProperty()
    {
        return DB::table('sessions')
            ->join('users', 'sessions.user_id', '=', 'users.id')
            ->select(
                'sessions.id',
                'sessions.user_id',
                'sessions.ip_address',
                'sessions.user_agent',
                'sessions.last_activity',
                'users.name',
                'users.email'
            )
            ->whereNotNull('sessions.user_id')
            ->orderBy('sessions.last_activity', 'desc')
            ->get()
            ->map(function ($session) {
                $session->last_activity_at = \Carbon\Carbon::createFromTimestamp($session->last_activity);
                $session->browser = $this->parseBrowser($session->user_agent);
                $session->platform = $this->parsePlatform($session->user_agent);

                return $session;
            });
    }

    protected function parseBrowser(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'Unknown';
        }

        $browsers = [
            'Edge' => '/Edg[e\/]?/i',
            'Opera' => '/Opera|OPR/i',
            'Chrome' => '/Chrome/i',
            'Safari' => '/Safari/i',
            'Firefox' => '/Firefox/i',
            'IE' => '/MSIE|Trident/i',
        ];

        foreach ($browsers as $browser => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return $browser;
            }
        }

        return 'Other';
    }

    protected function parsePlatform(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'Unknown';
        }

        $platforms = [
            'Windows' => '/Windows/i',
            'macOS' => '/Macintosh|Mac OS/i',
            'Linux' => '/Linux/i',
            'iOS' => '/iPhone|iPad|iPod/i',
            'Android' => '/Android/i',
        ];

        foreach ($platforms as $platform => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return $platform;
            }
        }

        return 'Other';
    }

    public function render()
    {
        $query = UserLoginAudit::with('user')
            ->orderBy('created_at', 'desc');

        if ($this->search) {
            $query->search($this->search);
        }

        if ($this->actionFilter) {
            $query->where('action', $this->actionFilter);
        }

        if ($this->userFilter) {
            $query->where('user_id', $this->userFilter);
        }

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        $audits = $query->paginate($this->perPage);

        // Stats
        $todayLogins = UserLoginAudit::today()->successfulLogins()->count();
        $todayFailed = UserLoginAudit::today()->failedLogins()->count();
        $activeUsers = DB::table('sessions')->whereNotNull('user_id')->distinct('user_id')->count('user_id');
        $uniqueUsersToday = UserLoginAudit::today()->successfulLogins()->distinct('user_id')->count('user_id');

        // Users for filter dropdown
        $users = User::orderBy('name')->get(['id', 'name', 'email']);

        return view('livewire.user-login-audit-log', [
            'audits' => $audits,
            'todayLogins' => $todayLogins,
            'todayFailed' => $todayFailed,
            'activeUsers' => $activeUsers,
            'uniqueUsersToday' => $uniqueUsersToday,
            'users' => $users,
            'activeSessions' => $this->activeSessions,
        ])->layout('components.layouts.app');
    }
}
