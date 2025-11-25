<?php

namespace App\Services\Feedback;

use App\Models\Feedback;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubIntegration
{
    protected string $token;

    protected string $repo;

    protected string $apiUrl = 'https://api.github.com';

    public function __construct()
    {
        $this->token = config('feedback.github.token', '');
        $this->repo = config('feedback.github.repo', '');
    }

    /**
     * Check if GitHub integration is enabled and configured.
     */
    public function isEnabled(): bool
    {
        return config('feedback.github.enabled', false)
            && ! empty($this->token)
            && ! empty($this->repo);
    }

    /**
     * Create a GitHub issue from feedback.
     */
    public function createIssue(Feedback $feedback): array
    {
        if (! $this->isEnabled()) {
            return [
                'success' => false,
                'error' => 'GitHub integration is not enabled or configured.',
            ];
        }

        try {
            $labels = $this->getLabelsForCategory($feedback->category);

            // Add priority label for high/critical
            if (in_array($feedback->priority, ['high', 'critical'])) {
                $labels[] = 'priority:'.$feedback->priority;
            }

            $body = $this->buildIssueBody($feedback);

            $response = Http::withToken($this->token)
                ->withHeaders([
                    'Accept' => 'application/vnd.github.v3+json',
                    'X-GitHub-Api-Version' => '2022-11-28',
                ])
                ->post("{$this->apiUrl}/repos/{$this->repo}/issues", [
                    'title' => "[{$feedback->category_label}] {$feedback->title}",
                    'body' => $body,
                    'labels' => $labels,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                // Update feedback with GitHub issue info
                $feedback->update([
                    'github_issue_url' => $data['html_url'],
                    'github_issue_number' => $data['number'],
                    'synced_at' => now(),
                ]);

                Log::info('GitHub issue created for feedback', [
                    'feedback_id' => $feedback->id,
                    'issue_number' => $data['number'],
                    'issue_url' => $data['html_url'],
                ]);

                return [
                    'success' => true,
                    'issue_number' => $data['number'],
                    'issue_url' => $data['html_url'],
                ];
            }

            Log::error('Failed to create GitHub issue', [
                'feedback_id' => $feedback->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'GitHub API error: '.$response->status(),
            ];

        } catch (\Exception $e) {
            Log::error('GitHub integration exception', [
                'feedback_id' => $feedback->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get GitHub labels based on feedback category.
     */
    protected function getLabelsForCategory(string $category): array
    {
        $categoryConfig = config("feedback.categories.{$category}", []);
        $label = $categoryConfig['github_label'] ?? null;

        return $label ? [$label] : [];
    }

    /**
     * Build the GitHub issue body from feedback.
     */
    protected function buildIssueBody(Feedback $feedback): string
    {
        $submitter = $feedback->user?->name ?? 'Unknown';
        $submitterEmail = $feedback->user?->email ?? 'N/A';

        $body = "## Description\n\n";
        $body .= $feedback->description."\n\n";

        $body .= "## Details\n\n";
        $body .= "| Field | Value |\n";
        $body .= "|-------|-------|\n";
        $body .= "| **Category** | {$feedback->category_label} |\n";
        $body .= "| **Priority** | {$feedback->priority_label} |\n";
        $body .= "| **Submitted By** | {$submitter} ({$submitterEmail}) |\n";
        $body .= "| **Submitted At** | {$feedback->created_at->format('Y-m-d H:i:s')} |\n";

        if ($feedback->page_url) {
            $body .= "| **Page URL** | {$feedback->page_url} |\n";
        }

        if ($feedback->browser_info) {
            $body .= '| **Browser** | '.substr($feedback->browser_info, 0, 100)."... |\n";
        }

        $body .= "\n---\n";
        $body .= "_This issue was automatically created from CAP TRACK user feedback._\n";
        $body .= "_Feedback ID: {$feedback->id}_\n";

        return $body;
    }

    /**
     * Test the GitHub connection.
     */
    public function testConnection(): array
    {
        if (! $this->isEnabled()) {
            return [
                'success' => false,
                'error' => 'GitHub integration is not enabled or configured.',
            ];
        }

        try {
            $response = Http::withToken($this->token)
                ->withHeaders([
                    'Accept' => 'application/vnd.github.v3+json',
                    'X-GitHub-Api-Version' => '2022-11-28',
                ])
                ->get("{$this->apiUrl}/repos/{$this->repo}");

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'repo_name' => $data['full_name'],
                    'repo_url' => $data['html_url'],
                ];
            }

            return [
                'success' => false,
                'error' => 'Could not access repository: '.$response->status(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
