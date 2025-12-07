<?php

namespace App\Services;

class VersionService
{
    protected int $major;
    protected int $minor;
    protected int $patch;
    protected string $commit;
    protected string $updatedAt;

    public function __construct()
    {
        $this->major = config('version.major', 1);
        $this->minor = config('version.minor', 0);
        $this->patch = config('version.patch', 0);
        $this->commit = config('version.commit', 'unknown');
        $this->updatedAt = config('version.updated_at', now()->toDateString());
    }

    public function getVersion(): string
    {
        return sprintf('%d.%d.%d', $this->major, $this->minor, $this->patch);
    }

    public function getShortVersion(): string
    {
        return 'v' . $this->getVersion();
    }

    public function getFullVersion(): string
    {
        return sprintf('v%s (%s)', $this->getVersion(), $this->getShortCommit());
    }

    public function getCommit(): string
    {
        return $this->commit;
    }

    public function getShortCommit(): string
    {
        if ($this->commit === 'initial' || $this->commit === 'unknown') {
            return $this->getGitCommit() ?: $this->commit;
        }

        return substr($this->commit, 0, 7);
    }

    public function getGitCommit(): ?string
    {
        $gitDir = base_path('.git');
        if (! is_dir($gitDir)) {
            return null;
        }

        $head = @file_get_contents($gitDir . '/HEAD');
        if (! $head) {
            return null;
        }

        $head = trim($head);

        if (str_starts_with($head, 'ref: ')) {
            $ref = substr($head, 5);
            $refPath = $gitDir . '/' . $ref;
            if (file_exists($refPath)) {
                $hash = @file_get_contents($refPath);
                return $hash ? substr(trim($hash), 0, 7) : null;
            }
        }

        return substr($head, 0, 7);
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function getMajor(): int
    {
        return $this->major;
    }

    public function getMinor(): int
    {
        return $this->minor;
    }

    public function getPatch(): int
    {
        return $this->patch;
    }

    public function toArray(): array
    {
        return [
            'version' => $this->getVersion(),
            'short' => $this->getShortVersion(),
            'full' => $this->getFullVersion(),
            'major' => $this->major,
            'minor' => $this->minor,
            'patch' => $this->patch,
            'commit' => $this->getShortCommit(),
            'updated_at' => $this->updatedAt,
        ];
    }
}
