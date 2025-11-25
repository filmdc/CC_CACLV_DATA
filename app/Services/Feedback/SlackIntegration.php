<?php

namespace App\Services\Feedback;

use App\Models\Feedback;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackIntegration
{
    protected string $webhookUrl;

    public function __construct()
    {
        $this->webhookUrl = config('feedback.slack.webhook_url', '');
    }

    /**
     * Check if Slack integration is enabled and configured.
     */
    public function isEnabled(): bool
    {
        return config('feedback.slack.enabled', false) && ! empty($this->webhookUrl);
    }

    /**
     * Send a feedback notification to Slack.
     */
    public function sendNotification(Feedback $feedback): array
    {
        if (! $this->isEnabled()) {
            return [
                'success' => false,
                'error' => 'Slack integration is not enabled or configured.',
            ];
        }

        try {
            $message = $this->buildMessage($feedback);

            $response = Http::post($this->webhookUrl, $message);

            if ($response->successful()) {
                Log::info('Slack notification sent for feedback', [
                    'feedback_id' => $feedback->id,
                ]);

                return [
                    'success' => true,
                ];
            }

            Log::error('Failed to send Slack notification', [
                'feedback_id' => $feedback->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'Slack API error: '.$response->status(),
            ];

        } catch (\Exception $e) {
            Log::error('Slack integration exception', [
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
     * Build the Slack message payload.
     */
    protected function buildMessage(Feedback $feedback): array
    {
        $categoryEmoji = match ($feedback->category) {
            'bug_report' => ':bug:',
            'feature_request' => ':bulb:',
            'usability_issue' => ':mag:',
            'question' => ':question:',
            default => ':speech_balloon:',
        };

        $priorityColor = match ($feedback->priority) {
            'critical' => '#dc2626',
            'high' => '#f59e0b',
            'medium' => '#3b82f6',
            'low' => '#6b7280',
            default => '#6b7280',
        };

        $submitter = $feedback->user?->name ?? 'Unknown';
        $viewUrl = route('feedback.management');

        return [
            'attachments' => [
                [
                    'color' => $priorityColor,
                    'blocks' => [
                        [
                            'type' => 'header',
                            'text' => [
                                'type' => 'plain_text',
                                'text' => "{$categoryEmoji} New {$feedback->category_label}",
                                'emoji' => true,
                            ],
                        ],
                        [
                            'type' => 'section',
                            'text' => [
                                'type' => 'mrkdwn',
                                'text' => "*{$feedback->title}*",
                            ],
                        ],
                        [
                            'type' => 'section',
                            'text' => [
                                'type' => 'mrkdwn',
                                'text' => substr($feedback->description, 0, 500).(strlen($feedback->description) > 500 ? '...' : ''),
                            ],
                        ],
                        [
                            'type' => 'context',
                            'elements' => [
                                [
                                    'type' => 'mrkdwn',
                                    'text' => "*Priority:* {$feedback->priority_label}",
                                ],
                                [
                                    'type' => 'mrkdwn',
                                    'text' => "*Submitted by:* {$submitter}",
                                ],
                                [
                                    'type' => 'mrkdwn',
                                    'text' => "*Date:* {$feedback->created_at->format('M d, Y g:i A')}",
                                ],
                            ],
                        ],
                        [
                            'type' => 'actions',
                            'elements' => [
                                [
                                    'type' => 'button',
                                    'text' => [
                                        'type' => 'plain_text',
                                        'text' => 'View in CAP TRACK',
                                        'emoji' => true,
                                    ],
                                    'url' => $viewUrl,
                                    'action_id' => 'view_feedback',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Test the Slack webhook.
     */
    public function testConnection(): array
    {
        if (! $this->isEnabled()) {
            return [
                'success' => false,
                'error' => 'Slack integration is not enabled or configured.',
            ];
        }

        try {
            $response = Http::post($this->webhookUrl, [
                'text' => ':white_check_mark: CAP TRACK feedback integration test successful!',
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Test message sent successfully.',
                ];
            }

            return [
                'success' => false,
                'error' => 'Slack webhook error: '.$response->status(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
