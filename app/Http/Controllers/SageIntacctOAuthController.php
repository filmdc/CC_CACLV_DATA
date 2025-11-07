<?php

namespace App\Http\Controllers;

use App\Models\SageIntacctSetting;
use App\Models\UserSageIntacctToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SageIntacctOAuthController extends Controller
{
    /**
     * Redirect user to Sage Intacct OAuth authorization page
     */
    public function authorize(Request $request)
    {
        // Get active Sage Intacct settings
        $settings = SageIntacctSetting::getActive();

        if (! $settings || ! $settings->isConfigured()) {
            return redirect()
                ->route('sage-intacct.configuration')
                ->with('error', 'Sage Intacct is not configured. Please configure the integration first.');
        }

        // Generate state token for CSRF protection
        $state = Str::random(40);
        session(['sage_intacct_oauth_state' => $state]);

        // Build authorization URL
        $params = http_build_query([
            'client_id' => $settings->client_id,
            'response_type' => 'code',
            'redirect_uri' => $settings->redirect_uri ?? route('intacct.oauth.callback'),
            'state' => $state,
            // Add scope if required by Sage Intacct
            // 'scope' => 'accounting.write',
        ]);

        $authorizationUrl = 'https://api.intacct.com/ia/api/v1/oauth2/authorize?'.$params;

        Log::info('Sage Intacct: Redirecting user to OAuth authorization', [
            'user_id' => auth()->id(),
            'company_id' => $settings->company_id,
        ]);

        return redirect($authorizationUrl);
    }

    /**
     * Handle OAuth callback from Sage Intacct
     */
    public function callback(Request $request)
    {
        // Validate state parameter (CSRF protection)
        $expectedState = session('sage_intacct_oauth_state');
        $actualState = $request->get('state');

        if (! $expectedState || $expectedState !== $actualState) {
            Log::error('Sage Intacct OAuth: Invalid state parameter', [
                'user_id' => auth()->id(),
                'expected' => $expectedState,
                'actual' => $actualState,
            ]);

            return redirect()
                ->route('sage-intacct.configuration')
                ->with('error', 'OAuth authentication failed: Invalid state parameter');
        }

        // Clear state from session
        session()->forget('sage_intacct_oauth_state');

        // Check for error from Sage Intacct
        if ($request->has('error')) {
            $error = $request->get('error');
            $errorDescription = $request->get('error_description', 'Unknown error');

            Log::error('Sage Intacct OAuth: Authorization error', [
                'user_id' => auth()->id(),
                'error' => $error,
                'description' => $errorDescription,
            ]);

            return redirect()
                ->route('sage-intacct.configuration')
                ->with('error', 'OAuth authorization failed: '.$errorDescription);
        }

        // Get authorization code
        $authorizationCode = $request->get('code');

        if (! $authorizationCode) {
            return redirect()
                ->route('sage-intacct.configuration')
                ->with('error', 'OAuth authentication failed: No authorization code received');
        }

        // Exchange authorization code for access token
        $tokenResult = $this->exchangeCodeForToken($authorizationCode);

        if (! $tokenResult['success']) {
            return redirect()
                ->route('sage-intacct.configuration')
                ->with('error', 'Failed to obtain access token: '.$tokenResult['message']);
        }

        // Store tokens for the user
        $this->storeUserTokens($tokenResult);

        Log::info('Sage Intacct OAuth: User successfully connected', [
            'user_id' => auth()->id(),
            'company_id' => $tokenResult['company_id'] ?? null,
        ]);

        return redirect()
            ->route('sage-intacct.configuration')
            ->with('success', 'Successfully connected to Sage Intacct!');
    }

    /**
     * Disconnect user's Sage Intacct account
     */
    public function disconnect(Request $request)
    {
        $user = auth()->user();
        $token = $user->sageIntacctToken;

        if (! $token) {
            return back()->with('error', 'You are not connected to Sage Intacct.');
        }

        // Optional: Call Sage Intacct revoke endpoint
        // This would invalidate the token on Sage's side
        // $this->revokeToken($token->access_token);

        // Delete the token record
        $token->delete();

        Log::info('Sage Intacct OAuth: User disconnected', [
            'user_id' => $user->id,
        ]);

        return back()->with('success', 'Disconnected from Sage Intacct.');
    }

    /**
     * Exchange authorization code for access token
     */
    private function exchangeCodeForToken(string $authorizationCode): array
    {
        $settings = SageIntacctSetting::getActive();

        if (! $settings) {
            return [
                'success' => false,
                'message' => 'Sage Intacct settings not found',
            ];
        }

        $tokenUrl = 'https://api.intacct.com/ia/api/v1/oauth2/token';

        try {
            Log::debug('Sage Intacct OAuth: Exchanging code for token', [
                'url' => $tokenUrl,
            ]);

            $response = Http::asForm()
                ->timeout(30)
                ->post($tokenUrl, [
                    'grant_type' => 'authorization_code',
                    'code' => $authorizationCode,
                    'redirect_uri' => $settings->redirect_uri ?? route('intacct.oauth.callback'),
                    'client_id' => $settings->client_id,
                    'client_secret' => $settings->client_secret,
                ]);

            if (! $response->successful()) {
                $error = $response->json();

                Log::error('Sage Intacct OAuth: Token exchange failed', [
                    'status' => $response->status(),
                    'error' => $error,
                ]);

                return [
                    'success' => false,
                    'message' => $error['error_description'] ?? $error['error'] ?? 'Token exchange failed',
                ];
            }

            $data = $response->json();

            return [
                'success' => true,
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'expires_in' => $data['expires_in'] ?? 300, // Default 5 minutes per Sage documentation
                'token_type' => $data['token_type'] ?? 'Bearer',
                'scope' => $data['scope'] ?? null,
                'company_id' => $data['company_id'] ?? $settings->company_id,
            ];
        } catch (\Exception $e) {
            Log::error('Sage Intacct OAuth: Token exchange exception', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Exception: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Store OAuth tokens for the authenticated user
     */
    private function storeUserTokens(array $tokenData): void
    {
        $user = auth()->user();

        UserSageIntacctToken::updateOrCreate(
            ['user_id' => $user->id],
            [
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'],
                'expires_at' => now()->addSeconds($tokenData['expires_in']),
                'company_id' => $tokenData['company_id'],
                'token_type' => $tokenData['token_type'],
                'scopes' => $tokenData['scope'] ? explode(' ', $tokenData['scope']) : null,
            ]
        );
    }

    /**
     * Revoke access token on Sage Intacct side (optional)
     */
    private function revokeToken(string $accessToken): void
    {
        try {
            // Note: Check Sage Intacct docs for actual revoke endpoint
            // This is a placeholder
            $revokeUrl = 'https://api.intacct.com/ia/api/v1/oauth2/revoke';

            Http::asForm()
                ->timeout(10)
                ->post($revokeUrl, [
                    'token' => $accessToken,
                ]);

            Log::info('Sage Intacct OAuth: Token revoked');
        } catch (\Exception $e) {
            Log::warning('Sage Intacct OAuth: Token revocation failed', [
                'error' => $e->getMessage(),
            ]);
            // Don't fail the disconnect operation if revoke fails
        }
    }
}
