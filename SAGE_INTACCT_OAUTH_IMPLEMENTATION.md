# Sage Intacct REST API OAuth Implementation - Remaining Work

## ✅ Completed (Phase 1 - Foundation)

### Database Layer
- ✅ Created `user_sage_intacct_tokens` table
  - Stores encrypted OAuth tokens per user
  - Tracks token expiration and company_id
  - One token per user (unique constraint)

- ✅ Added `redirect_uri` to `sage_intacct_settings` table
  - Supports OAuth callback URL configuration

### Models
- ✅ Created `UserSageIntacctToken` model
  - Encrypted `access_token` and `refresh_token`
  - `isExpired()`, `needsRefresh()`, `hasValidToken()` helpers
  - `updateToken()` method for token refresh
  - `isSandbox()` check for sandbox vs production
  - `getStatusSummary()` for UI display

- ✅ Updated `User` model
  - Added `sageIntacctToken()` relationship

### Services
- ✅ Partially refactored `SageIntacctApiService` to REST API
  - Changed from XML to JSON/REST
  - OAuth 2.0 token-based auth started
  - ⚠️ Still needs user token integration

### Commands
- ✅ Created `php artisan intacct:sync-gifts` command
  - Supports date ranges, retry failed, dry-run
  - Sandbox safety checks

### UI
- ✅ Updated configuration view with sandbox warnings
- ✅ Simplified credential fields (Client ID/Secret)

### Cleanup
- ✅ Removed deprecated `SageIntacctGiftSyncService`

---

## 🔄 Remaining Work (Phase 2 - OAuth Flow)

### 1. OAuth Controller (High Priority)
**File:** `app/Http/Controllers/SageIntacctOAuthController.php`

**Methods to implement:**

#### `authorize()` Method
```php
// Redirect user to Sage Intacct OAuth authorization URL
// URL: https://api.intacct.com/ia/api/v1/oauth2/authorize
// Query params:
//   - client_id: From settings
//   - response_type: 'code'
//   - redirect_uri: From settings
//   - state: CSRF token (store in session)
//   - scope: (optional, depends on Sage requirements)
```

#### `callback()` Method
```php
// Handle OAuth callback from Sage Intacct
// Steps:
//   1. Validate state parameter (CSRF)
//   2. Check for error parameter
//   3. Exchange authorization code for tokens
//   4. Store tokens in user_sage_intacct_tokens
//   5. Redirect to gifts list or configuration page
//   6. Show success message
```

#### `disconnect()` Method
```php
// Revoke user's Sage Intacct connection
// Steps:
//   1. (Optional) Call Sage revoke endpoint
//   2. Delete user's token record
//   3. Show confirmation message
```

**Token Exchange API Call:**
```
POST https://api.intacct.com/ia/api/v1/oauth2/token
Content-Type: application/x-www-form-urlencoded

grant_type=authorization_code
&code={authorization_code}
&redirect_uri={redirect_uri}
&client_id={client_id}
&client_secret={client_secret}
```

---

### 2. OAuth Routes (High Priority)
**File:** `routes/web.php`

```php
// Add after existing Sage Intacct routes
Route::middleware(['auth'])->group(function () {
    Route::get('/intacct/oauth/authorize', [SageIntacctOAuthController::class, 'authorize'])
        ->name('intacct.oauth.authorize')
        ->middleware('permission:intacct.sync');

    Route::get('/intacct/oauth/callback', [SageIntacctOAuthController::class, 'callback'])
        ->name('intacct.oauth.callback');

    Route::post('/intacct/oauth/disconnect', [SageIntacctOAuthController::class, 'disconnect'])
        ->name('intacct.oauth.disconnect')
        ->middleware('permission:intacct.sync');
});
```

---

### 3. Update API Service for User Tokens (High Priority)
**File:** `app/Services/SageIntacctApiService.php`

**Changes needed:**

#### Constructor
```php
// CHANGE FROM:
public function __construct($setting)

// CHANGE TO:
public function __construct(UserSageIntacctToken $userToken, $setting)
```

#### Remove OAuth Client Credentials Flow
```php
// REMOVE: fetchOAuthToken() method (was trying to use client credentials)
// Users will authenticate via browser OAuth flow instead
```

#### Add Token Refresh Method
```php
private function refreshAccessToken(): array
{
    // POST to https://api.intacct.com/ia/api/v1/oauth2/token
    // grant_type=refresh_token
    // refresh_token={user's refresh token}
    // client_id & client_secret from settings

    // Update user's token record with new access_token and expires_at
}
```

#### Update authenticate() Method
```php
public function authenticate(): array
{
    // Check if token is expired or needs refresh
    if ($this->userToken->isExpired()) {
        return ['success' => false, 'message' => 'Token expired - reauthorization required'];
    }

    if ($this->userToken->needsRefresh()) {
        return $this->refreshAccessToken();
    }

    // Use existing token
    $this->accessToken = $this->userToken->access_token;
    return ['success' => true];
}
```

---

### 4. Permission System (Medium Priority)
**File:** Database seeder (likely `database/seeders/PermissionsSeeder.php` or similar)

**Add permission:**
```php
Permission::create([
    'name' => 'intacct.sync',
    'description' => 'Sync data to Sage Intacct',
    'category' => 'accounting',
]);

// Assign to appropriate roles (admin, accounting staff, etc.)
$adminRole->permissions()->attach($intacctSyncPermission);
```

---

### 5. Gifts List View Integration (High Priority)
**File to find:** Likely `app/Livewire/DonorPerfectGiftsFeed.php` or similar

**Search commands:**
```bash
# Find the gifts list component
find app/Livewire -name "*Gift*" -o -name "*Donor*"
grep -r "DonorPerfect.*gift" app/Livewire/
```

**Properties to add:**
```php
public $showSyncModal = false;
public $selectedGiftIds = [];
public $syncInProgress = false;
```

**Methods to add:**
```php
public function checkIntacctConnection()
{
    $token = auth()->user()->sageIntacctToken;
    return $token && $token->hasValidToken();
}

public function initiateSageIntacctSync()
{
    if (!$this->checkIntacctConnection()) {
        return redirect()->route('intacct.oauth.authorize');
    }

    $this->showSyncModal = true;
}

public function performSync()
{
    // Get filtered/selected gift records
    // Create sync service with user's token
    // Perform sync
    // Show results
}
```

**View changes needed:**
```blade
<!-- Connection status badge -->
@can('intacct.sync')
    <div class="mb-4">
        @if(auth()->user()->sageIntacctToken?->hasValidToken())
            <span class="badge badge-success">
                ✓ Connected to Sage Intacct
                ({{ auth()->user()->sageIntacctToken->company_id }})
            </span>
        @else
            <span class="badge badge-warning">
                Not connected to Sage Intacct
            </span>
        @endif
    </div>
@endcan

<!-- Sync button -->
@can('intacct.sync')
    <button wire:click="initiateSageIntacctSync" class="btn btn-primary">
        <svg>...</svg>
        Sync to Sage Intacct
    </button>
@endcan

<!-- Sync modal (shown when $showSyncModal = true) -->
```

---

### 6. Configuration UI Updates (Medium Priority)
**File:** `app/Livewire/SageIntacctConfiguration.php`

**Changes needed:**

#### Remove user credential fields
- Remove or hide: user_id, sender_id, sender_password, web_services_user_id, web_services_password
- Keep only app-level: client_id, client_secret, company_id, redirect_uri

#### Add redirect_uri field
```php
public $redirect_uri = '';

// In loadExistingSettings():
$this->redirect_uri = $settings->redirect_uri ?? route('intacct.oauth.callback');
```

#### Add "User Connections" tab
**New tab to show:**
- List of all users with active Sage Intacct connections
- User name, company_id, token expiration
- "Revoke" button for admins

#### Update testConnection() method
```php
// Remove test connection button
// Or change it to:
// "Test App Configuration" - validates client_id/secret without user auth
```

---

### 7. Update Sync Service (Medium Priority)
**File:** `app/Services/SageIntacctSyncService.php`

**Constructor changes:**
```php
// ADD user token parameter
public function __construct(
    SageIntacctApiService $apiService,
    $setting,
    UserSageIntacctToken $userToken
)
```

**Track user in sync history:**
```php
// In createSyncHistory():
'triggered_by' => 'user:' . $userToken->user_id,
'sage_company_id' => $userToken->company_id,
```

---

## 📋 Testing Checklist

### Prerequisites
1. Register OAuth app in Sage Developer Portal
   - Get Client ID and Client Secret
   - Set redirect URI: `https://yourdomain.com/intacct/oauth/callback`

2. Configure in admin panel:
   - Client ID
   - Client Secret
   - Redirect URI
   - Company ID (CACLV-sandbox for testing)

### Test Scenarios
- [ ] User with `intacct.sync` permission can see "Connect" button
- [ ] User without permission cannot see button
- [ ] OAuth authorization redirects to Sage Intacct
- [ ] User can authorize app in Sage
- [ ] Callback stores tokens correctly
- [ ] Token expiration is tracked
- [ ] Token refresh works automatically
- [ ] User can disconnect their account
- [ ] Sync button appears after connection
- [ ] Sync works with filtered records
- [ ] Sync history tracks which user initiated
- [ ] Multiple users can have separate connections
- [ ] Expired tokens show re-auth prompt

---

## 🔧 Configuration After Implementation

### Step 1: Register OAuth App
1. Go to Sage Developer Portal
2. Create new OAuth application
3. Set redirect URI: `https://yourdomain.com/intacct/oauth/callback`
4. Save Client ID and Client Secret

### Step 2: Configure App Settings
1. Navigate to `/accounting/sage-intacct/configuration`
2. Enter Client ID and Client Secret
3. Verify redirect URI matches registration
4. Set default company to `CACLV-sandbox`
5. Save settings

### Step 3: User Authorization
1. User navigates to DonorPerfect gifts list
2. Clicks "Connect to Sage Intacct"
3. Redirected to Sage Intacct login
4. Signs in with their Sage credentials
5. Grants permission to app
6. Redirected back to application
7. Can now sync records

---

## ⚠️ Important Notes

### OAuth Scopes
- Need to determine which scopes Sage Intacct requires
- Likely something like `accounting.write` or similar
- Check Sage documentation for exact scope names

### Token Lifetime
- Access tokens typically expire in 1 hour
- Refresh tokens may expire in 30-90 days
- Need to handle refresh token expiration (require re-auth)

### Error Handling
- Authorization denied by user
- Invalid/expired refresh token
- API rate limits
- Network errors during sync
- Insufficient permissions in Sage

### Security
- Validate state parameter (CSRF protection)
- Encrypt all tokens in database
- Use HTTPS for redirect URI
- Store client_secret encrypted

---

## 📦 Deliverables Summary

1. **SageIntacctOAuthController** - 3 methods
2. **OAuth routes** - 3 routes
3. **API Service updates** - Constructor + refresh logic
4. **Permission** - intacct.sync
5. **Gifts list integration** - Button + modal + sync logic
6. **Configuration UI** - Simplified + user connections tab
7. **Sync service** - User context tracking
8. **Testing** - Full OAuth flow verification

**Estimated time remaining:** 6-8 hours
