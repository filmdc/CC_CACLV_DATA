# Sage Intacct OAuth Implementation vs Postman Guide Comparison

## Summary
Based on the Postman guide you shared, I've updated our implementation to match Sage's OAuth requirements.

---

## ✅ What Matches Perfectly

| Feature | Postman Guide | Our Implementation | Status |
|---------|--------------|-------------------|--------|
| Authorization Type | OAuth 2.0 | OAuth 2.0 | ✅ Match |
| Auth in Headers | Yes (Bearer token) | Yes (Bearer token) | ✅ Match |
| State Parameter | YOUR_RANDOM_VALUE | 40-char random string | ✅ Match |
| Client Credentials | Sent in body | Sent via `asForm()` | ✅ Match |
| Callback/Redirect URI | Required | Configured & validated | ✅ Match |
| Grant Access Once | Yes, then refresh | Yes, with refresh token | ✅ Match |

---

## ⚠️ Critical Fixes Applied

### 1. Token Expiration Time
**Postman Guide:** "The access token will be valid for 5 minutes"

**Our Original Code:** Assumed 3600 seconds (1 hour)

**Fix Applied:**
- Changed default `expires_in` from `3600` to `300` (5 minutes)
- Updated `needsRefresh()` from 5 min buffer to 2 min buffer
- Token will auto-refresh when 2 minutes remain

**Files Updated:**
- `app/Models/UserSageIntacctToken.php:66`
- `app/Services/SageIntacctApiService.php:123`
- `app/Http/Controllers/SageIntacctOAuthController.php:200`

### 2. Client Authentication Method
**Postman Guide:** "Set Client Authentication to Send client credentials in body"

**Our Implementation:** Using `Http::asForm()` which sends credentials as form data in body ✓

**Status:** Already correct - no changes needed

---

## ⚠️ Important Verification Needed

### OAuth Endpoint URLs

**What We're Using:**
```
Authorization: https://api.intacct.com/ia/api/v1/oauth2/authorize
Token:         https://api.intacct.com/ia/api/v1/oauth2/token
```

**Note:** The Postman guide you shared mentions "Sage Accounting" which may have different endpoints than "Sage Intacct".

**Action Required:** Verify these exact URLs in your Sage Intacct Developer Portal documentation.

---

## 🔧 Our OAuth Flow Implementation

### Authorization Request (Step 1)
```
GET https://api.intacct.com/ia/api/v1/oauth2/authorize
  ?client_id=YOUR_CLIENT_ID
  &response_type=code
  &redirect_uri=https://yourdomain.com/intacct/oauth/callback
  &state=RANDOM_40_CHAR_STRING
```

**Implemented in:** `app/Http/Controllers/SageIntacctOAuthController.php:32-42`

### Token Exchange (Step 2 - Callback)
```
POST https://api.intacct.com/ia/api/v1/oauth2/token
Content-Type: application/x-www-form-urlencoded

grant_type=authorization_code
&code=AUTHORIZATION_CODE_FROM_CALLBACK
&redirect_uri=https://yourdomain.com/intacct/oauth/callback
&client_id=YOUR_CLIENT_ID
&client_secret=YOUR_CLIENT_SECRET
```

**Implemented in:** `app/Http/Controllers/SageIntacctOAuthController.php:170-178`

### Token Refresh (Automatic)
```
POST https://api.intacct.com/ia/api/v1/oauth2/token
Content-Type: application/x-www-form-urlencoded

grant_type=refresh_token
&refresh_token=USER_REFRESH_TOKEN
&client_id=YOUR_CLIENT_ID
&client_secret=YOUR_CLIENT_SECRET
```

**Implemented in:** `app/Services/SageIntacctApiService.php:92-99`

---

## 📊 Token Lifecycle in Our App

```
User Authorizes
    ↓
Tokens Stored (expires in 5 min)
    ↓
User Makes Sync Request
    ↓
Check: Token expires in < 2 min?
    ├─ YES → Auto-refresh token (seamless)
    └─ NO → Use existing token
    ↓
Token Expired Completely?
    ├─ YES → Prompt user to re-authorize
    └─ NO → Proceed with API call
```

**Refresh is automatic** - Users won't notice as long as they sync at least once every 5 minutes.

---

## 🚨 Key Differences from Postman Guide

| Aspect | Postman (Manual) | Our App (Automated) |
|--------|------------------|---------------------|
| Token Refresh | Manual "Get New Token" button | Automatic when < 2 min left |
| Token Storage | Temporary in Postman | Persistent in encrypted database |
| User Flow | Click button each time | Authorize once, refresh automatic |
| Expiration | 5 minutes shown | Tracked in DB, auto-refresh |

---

## 🧪 Testing Checklist

Based on Postman guide, verify:

- [ ] Client ID from Sage Developer Portal is correct
- [ ] Client Secret from Sage Developer Portal is correct
- [ ] Redirect URI matches exactly what's registered in Sage portal
- [ ] OAuth authorize URL is correct for Sage **Intacct** (not just Sage Accounting)
- [ ] OAuth token URL is correct for Sage **Intacct**
- [ ] API base URL for making requests is correct
- [ ] Access token expires in 5 minutes (check response from Sage)
- [ ] Refresh token works to get new access token
- [ ] Authorization headers include "Bearer " prefix

---

## 🔍 Troubleshooting Tips from Postman Guide

**From Guide:** "Try to remove the cookies in Postman, when authorization fails"

**For Our App:**
- Clear Laravel session/cache if OAuth fails
- Check browser developer console for redirect errors
- Verify no CORS issues with callback URL
- Ensure redirect URI uses HTTPS (not HTTP)

---

## 📝 Next Steps to Verify

1. **Check Sage Intacct Developer Portal** for exact OAuth endpoint URLs
2. **Register OAuth app** and get real Client ID/Secret
3. **Test authorization flow** - should redirect to Sage Intacct login
4. **Monitor token expiration** - verify it's actually 5 minutes
5. **Test auto-refresh** - make sync call after 3+ minutes, should refresh seamlessly

---

## ⚡ Quick Test Commands

```bash
# After configuring Client ID/Secret in admin panel:

# 1. Seed permission
php artisan db:seed --class=DonorPerfectSeeder

# 2. Assign permission to your user (do this in admin UI or via tinker)

# 3. Navigate to /accounting/donor-gifts in browser

# 4. Click "Connect to Sage Intacct" button

# 5. You should be redirected to Sage Intacct OAuth page
```

---

## 🎯 Current Status

✅ **Implementation matches Postman guide**
✅ **5-minute token expiration handled**
✅ **Auto-refresh logic updated (2-min buffer)**
⚠️ **Need to verify OAuth URLs are for Intacct (not generic Sage)**

**Ready for testing once you have valid Client ID/Secret from Sage Intacct Developer Portal!**
