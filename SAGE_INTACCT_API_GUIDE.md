# Sage Intacct API Integration Guide
## Complete Reference for Building Sage Intacct Integrations

**Purpose:** Standalone guide for connecting to Sage Intacct API
**Audience:** Developers building Sage Intacct integrations
**Last Updated:** November 7, 2025

---

## 📋 Table of Contents

1. [API Overview](#api-overview)
2. [Authentication Methods](#authentication-methods)
3. [XML Web Services (Recommended for Server-to-Server)](#xml-web-services)
4. [REST API with OAuth 2.0](#rest-api-with-oauth-20)
5. [XML Requirements & Structure](#xml-requirements--structure)
6. [Common Operations](#common-operations)
7. [Error Handling](#error-handling)
8. [Best Practices](#best-practices)
9. [Troubleshooting](#troubleshooting)

---

## API Overview

### Two API Types Available

Sage Intacct offers two API methods:

| Feature | XML Web Services | REST API (OAuth 2.0) |
|---------|-----------------|---------------------|
| **Protocol** | SOAP/XML over HTTP | REST/JSON over HTTPS |
| **Auth Method** | Session-based (Sender ID) | Token-based (OAuth) |
| **Use Case** | Server-to-server, automated | User-authorized apps |
| **Firewall Friendly** | ✅ Yes | ❌ No (requires public callback) |
| **Future Development** | Maintenance mode | Active development |
| **Deprecation** | No plans | N/A |
| **Token Lifetime** | Session-based (long) | 5 minutes |
| **Best For** | Internal apps, automation | Public apps, user-specific |

### Recommendation

**Use XML Web Services if:**
- ✅ Your app is internal/behind firewall
- ✅ You need automated background syncs
- ✅ No user interaction required
- ✅ You have Sender ID credentials

**Use REST API/OAuth if:**
- ✅ Your app is publicly accessible
- ✅ Users need to authorize with their own Sage accounts
- ✅ Per-user data access required
- ✅ You can handle 5-minute token expiration

---

## Authentication Methods

### Method 1: XML Web Services (Sender ID)

**Required Credentials:**
1. **Sender ID** - Web Services License ID (from Sage)
2. **Sender Password** - Web Services License Password (from Sage)
3. **Company ID** - Your Sage Intacct company identifier
4. **Web Services User ID** - User created in Sage Intacct for API access
5. **Web Services Password** - Password for that user

**Where to Get:**
- Sender ID/Password: Provided by Sage Intacct with Web Services license
- Company ID: Sage Intacct → Applications → Overview → Company Information
- Web Services User: Create in Sage Intacct → Company → Web Services users

**Security Notes:**
- Sender ID and password are **case-sensitive**
- Store passwords encrypted
- Create dedicated Web Services user (don't use admin account)
- Grant minimal required permissions to Web Services user

### Method 2: REST API (OAuth 2.0)

**Required Credentials:**
1. **Client ID** - From Sage Developer Portal
2. **Client Secret** - From Sage Developer Portal
3. **Redirect URI** - Your app's callback URL (must be publicly accessible)

**OAuth Flow:**
- Authorization Code Flow (requires user interaction)
- User authorizes via browser
- Tokens expire in 5 minutes
- Refresh tokens available

**Where to Register:**
- Sage Developer Portal: https://developer.sage.com
- Register OAuth application
- Configure redirect URIs

---

## XML Web Services

### Base URL
```
https://api.intacct.com/ia/xml/xmlgw.phtml
```

### Authentication Flow

**Step 1: Get Session ID**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<request>
    <control>
        <senderid>YOUR_SENDER_ID</senderid>
        <password>YOUR_SENDER_PASSWORD</password>
        <controlid>UNIQUE_CONTROL_ID</controlid>
        <uniqueid>false</uniqueid>
        <dtdversion>3.0</dtdversion>
    </control>
    <operation>
        <authentication>
            <login>
                <userid>WEB_SERVICES_USER_ID</userid>
                <companyid>YOUR_COMPANY_ID</companyid>
                <password>WEB_SERVICES_PASSWORD</password>
            </login>
        </authentication>
        <content>
            <function controlid="UNIQUE_FUNCTION_ID">
                <getAPISession/>
            </function>
        </content>
    </operation>
</request>
```

**Response (Session ID Location):**
```xml
<response>
    <operation>
        <result>
            <status>success</status>
            <data>
                <api>
                    <sessionid>YOUR_SESSION_ID</sessionid>
                    <endpoint>https://api.intacct.com/ia/xml/xmlgw.phtml</endpoint>
                </api>
            </data>
        </result>
    </operation>
</response>
```

**Extract session ID:**
```php
$xml = simplexml_load_string($response);
$sessionId = $xml->xpath('//result/data/api/sessionid');
$sessionId = (string) $sessionId[0];
```

**Step 2: Use Session for Subsequent Requests**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<request>
    <control>
        <senderid>YOUR_SENDER_ID</senderid>
        <password>YOUR_SENDER_PASSWORD</password>
        <controlid>UNIQUE_CONTROL_ID</controlid>
        <uniqueid>false</uniqueid>
        <dtdversion>3.0</dtdversion>
    </control>
    <operation>
        <authentication>
            <sessionid>YOUR_SESSION_ID</sessionid>
        </authentication>
        <content>
            <!-- Your API functions here -->
        </content>
    </operation>
</request>
```

---

## XML Requirements & Structure

### Critical XML Rules

**⚠️ IMPORTANT:** Sage Intacct XML is very strict. Follow these rules exactly:

#### 1. **Element Names Cannot Contain Spaces**

❌ **WRONG:**
```xml
<Donor Perfect Gifts>
    <amount>100</amount>
</Donor Perfect Gifts>
```

✅ **CORRECT:**
```xml
<gifts>
    <amount>100</amount>
</gifts>
```

**Rule:** Use the **integration name** from custom object definition, NOT the display name.

#### 2. **Special Characters Must Be Escaped**

```php
// ALWAYS use htmlspecialchars() for values
$xml = '<amount>' . htmlspecialchars($value) . '</amount>';
```

**Characters to escape:**
- `&` → `&amp;`
- `<` → `&lt;`
- `>` → `&gt;`
- `"` → `&quot;`
- `'` → `&apos;`

#### 3. **Field Names Are Case-Sensitive**

```xml
<donor_id>12345</donor_id>  <!-- Correct -->
<DONOR_ID>12345</DONOR_ID>  <!-- Wrong -->
<Donor_Id>12345</Donor_Id>  <!-- Wrong -->
```

Match the exact case from your Sage Intacct field definitions.

#### 4. **Required Fields Must Be Present**

If a custom object field is marked "Required" in Sage Intacct, it MUST be in the XML. Omitting it causes:
```
Error: "[Field name] must be specified"
```

**Solution:** Make field optional in Sage OR always send a value (even if empty string).

#### 5. **Control IDs Must Be Unique**

```php
// Generate unique IDs
$controlId = 'APP_' . time() . '_' . uniqid();
```

Don't reuse control IDs across requests.

#### 6. **DTD Version**

Always use `3.0`:
```xml
<dtdversion>3.0</dtdversion>
```

---

## Common Operations

### Create a Custom Object Record

**Single Record:**

```xml
<function controlid="UNIQUE_ID">
    <create>
        <OBJECT_NAME>
            <field1>value1</field1>
            <field2>value2</field2>
        </OBJECT_NAME>
    </create>
</function>
```

**Example (creating a gift):**

```xml
<function controlid="create_gift_12345">
    <create>
        <gifts>
            <donor_perfect_gift_id>12345</donor_perfect_gift_id>
            <gift_date>2025-11-07</gift_date>
            <amount>150.00</amount>
            <gift_gl_code>40100</gift_gl_code>
            <donor_id>98765</donor_id>
            <firstName>John</firstName>
            <lastName>Doe</lastName>
            <email>john@example.com</email>
        </gifts>
    </create>
</function>
```

### Batch Create (Multiple Records)

**Up to 100 records per request:**

```xml
<content>
    <function controlid="func_1">
        <create>
            <gifts>
                <donor_perfect_gift_id>12345</donor_perfect_gift_id>
                <amount>100.00</amount>
                <!-- ... -->
            </gifts>
        </create>
    </function>
    <function controlid="func_2">
        <create>
            <gifts>
                <donor_perfect_gift_id>12346</donor_perfect_gift_id>
                <amount>200.00</amount>
                <!-- ... -->
            </gifts>
        </create>
    </function>
    <!-- Up to 100 functions -->
</content>
```

**Batch Response Structure:**

```xml
<operation>
    <result>
        <status>success</status>
        <!-- First record result -->
    </result>
    <result>
        <status>failure</status>
        <errormessage>
            <error>
                <description2>Error details here</description2>
            </error>
        </errormessage>
    </result>
    <!-- More results -->
</operation>
```

**Parse batch results:**
```php
$results = $xml->xpath('//operation/result');
foreach ($results as $index => $result) {
    $status = (string) $result->status;
    if ($status === 'success') {
        $key = (string) $result->key;
        // Record created successfully
    } else {
        $errors = $result->xpath('errormessage/error/description2');
        $errorText = (string) $errors[0];
        // Record failed
    }
}
```

### Upload Attachments

```xml
<function controlid="attach_1">
    <create>
        <SUPDOCATTACHMENT>
            <attachmentname>check_image.jpg</attachmentname>
            <attachmenttype>File</attachmenttype>
            <attachmentdata>BASE64_ENCODED_FILE_CONTENT</attachmentdata>
        </SUPDOCATTACHMENT>
    </create>
</function>
```

**File size limit:** 100MB (Base64 encoded)

---

## Error Handling

### Common Error Patterns

#### 1. Authentication Errors

**Error:** `"XL03000006: Web Services are not authorized"`
- **Cause:** Sender ID not authorized for this company
- **Fix:** Add Sender ID to Web Services Authorizations in Sage Intacct
  - Company → Company Info → Security → Web Services Authorizations

**Error:** `"XL03000002: Sign-in information is invalid"`
- **Cause:** Incorrect credentials (user ID or password)
- **Fix:** Verify Web Services user credentials
- **Note:** Credentials are case-sensitive!

**Error:** `"No session ID returned from authentication"`
- **Cause:** Wrong XPath or authentication failed silently
- **Fix:** Check for `//errormessage` in response first
- **XPath:** Use `//result/data/api/sessionid` for getAPISession

#### 2. XML Parse Errors

**Error:** `"Specification mandates value for attribute [word]"`
- **Cause:** Element name contains spaces
- **Example:** `<Donor Perfect Gifts>` treats "Perfect" as attribute
- **Fix:** Use integration name without spaces: `<gifts>`

**Error:** `"Couldn't find end of Start Tag"`
- **Cause:** Unescaped special characters in values
- **Fix:** Always use `htmlspecialchars()`:
  ```php
  $xml = '<name>' . htmlspecialchars($value) . '</name>';
  ```

**Error:** `"Opening and ending tag mismatch"`
- **Cause:** Inconsistent tag names or nesting
- **Fix:** Verify XML structure with validator

#### 3. Field Validation Errors

**Error:** `"[Field] must be specified"`
- **Cause:** Required field missing from XML
- **Fix:** Include field OR make it optional in Sage Intacct

**Error:** `"[Value] does not match the required format"`
- **Cause:** Field validation failed (e.g., numeric field got text)
- **Fix:** Verify field data types in custom object definition
- **Example:** GL Code expecting `#####` (5 digits) but got `P1400`

#### 4. Permission Errors

**Error:** `"The user does not have permission to [action]"`
- **Cause:** Web Services user lacks permissions
- **Fix:** In Sage Intacct, grant permissions to the Web Services user
  - User → Roles → Assign appropriate role
  - Or grant specific object permissions

---

## Best Practices

### 1. Error Logging

Always log:
- Authentication attempts (success/failure)
- API requests (preview first 500 chars of XML)
- API responses (preview first 500 chars)
- Specific error messages from Sage

```php
Log::info('Sage Intacct: Authenticating', [
    'sender_id' => $senderId,
    'company_id' => $companyId,
]);

Log::debug('XML Request Preview', [
    'preview' => substr($xmlRequest, 0, 500),
]);

Log::error('Sage Intacct API Error', [
    'error' => $errorMessage,
    'support_id' => $supportId, // Extract from error response
]);
```

### 2. Control ID Generation

Use unique, identifiable control IDs:

```php
// Good pattern
$controlId = 'APPNAME_' . time() . '_' . uniqid();
// Example: CAPTRACK_1762549644_690e5f8c

// Include context for debugging
$functionId = 'create_gift_' . $giftId . '_' . time();
```

### 3. Batch Processing

**Rules:**
- Maximum 100 functions per request
- Use batch for better performance
- Track which record each function represents

```php
$batchSize = 100;
$batches = array_chunk($records, $batchSize);

foreach ($batches as $batchIndex => $batch) {
    $xml = buildBatchXml($batch);
    $response = sendRequest($xml);
    parseBatchResults($response, $batchIndex);
}
```

### 4. Session Management

**XML Web Services:**
- Get session ID once
- Reuse for multiple requests
- Session timeout: Configured in Sage (usually hours)
- Re-authenticate if session expires

```php
class SageIntacctClient {
    private $sessionId = null;

    public function authenticate() {
        if ($this->sessionId) {
            return true; // Already authenticated
        }

        $response = $this->getAPISession();
        $this->sessionId = extractSessionId($response);
    }

    public function createRecord($data) {
        $this->authenticate(); // Ensures session exists
        return $this->sendRequest($this->buildXml($data));
    }
}
```

### 5. Response Parsing

**Always check multiple levels:**

```php
// 1. HTTP status
if (!$response->successful()) {
    // Handle HTTP error
}

// 2. Control status
$xml = simplexml_load_string($response->body());
$controlStatus = $xml->xpath('//control/status');
if ((string)$controlStatus[0] === 'failure') {
    // Handle control-level error
}

// 3. Operation status (if present)
$opStatus = $xml->xpath('//operation/result/status');
if ((string)$opStatus[0] === 'failure') {
    // Handle operation error
}

// 4. Function status (individual operations)
$functionStatus = $xml->xpath('//function/result/status');
if ((string)$functionStatus[0] === 'failure') {
    // Handle function error
}

// 5. Check for errormessage anywhere
$errors = $xml->xpath('//errormessage/error/description2');
if (!empty($errors)) {
    $errorText = (string) $errors[0];
    // Handle specific error
}
```

### 6. Custom Object Naming

**CRITICAL RULE:** When working with custom objects, use the **integration name**, not the display name.

**In Sage Intacct:**
- Display Name: "Donor Perfect Gifts" (shown in UI)
- Integration Name: `gifts` (used in API)

**In Your Code:**
```php
// ❌ WRONG - Will cause XML parse errors
$objectName = 'Donor Perfect Gifts';
$xml = '<' . $objectName . '>...</' . $objectName . '>';

// ✅ CORRECT - Use integration name
$objectName = 'gifts';
$xml = '<' . $objectName . '>...</' . $objectName . '>';
```

**How to Find Integration Name:**
- Platform Services → Custom Objects → [Your Object]
- Look for "Integration name" field
- Usually lowercase, no spaces, singular or plural

---

## REST API with OAuth 2.0

### Base URL
```
https://api.intacct.com/ia/api/v1/
```

### OAuth Endpoints

**Authorization:**
```
GET https://api.intacct.com/ia/api/v1/oauth2/authorize
  ?client_id=YOUR_CLIENT_ID
  &response_type=code
  &redirect_uri=YOUR_CALLBACK_URL
  &state=RANDOM_CSRF_TOKEN
```

**Token Exchange:**
```
POST https://api.intacct.com/ia/api/v1/oauth2/token
Content-Type: application/x-www-form-urlencoded

grant_type=authorization_code
&code=AUTHORIZATION_CODE
&redirect_uri=YOUR_CALLBACK_URL
&client_id=YOUR_CLIENT_ID
&client_secret=YOUR_CLIENT_SECRET
```

**Token Refresh:**
```
POST https://api.intacct.com/ia/api/v1/oauth2/token
Content-Type: application/x-www-form-urlencoded

grant_type=refresh_token
&refresh_token=USER_REFRESH_TOKEN
&client_id=YOUR_CLIENT_ID
&client_secret=YOUR_CLIENT_SECRET
```

### Token Lifetimes

**Access Token:** 5 minutes (300 seconds)
**Refresh Token:** Variable (check response)

**Auto-Refresh Strategy:**
```php
public function ensureValidToken() {
    if ($this->tokenExpiresAt->subMinutes(2)->isPast()) {
        $this->refreshToken(); // Refresh 2 min before expiry
    }
}
```

### REST API Requests

**Create Record:**
```
POST https://api.intacct.com/ia/api/v1/companies/{companyId}/objects/{objectName}
Authorization: Bearer ACCESS_TOKEN
Content-Type: application/json

{
    "donor_perfect_gift_id": 12345,
    "gift_date": "2025-11-07",
    "amount": 150.00,
    "firstName": "John",
    "lastName": "Doe"
}
```

---

## PHP Implementation Examples

### XML Web Services Example

```php
<?php

class SageIntacctXmlClient
{
    private $senderId;
    private $senderPassword;
    private $companyId;
    private $userId;
    private $userPassword;
    private $sessionId = null;

    public function __construct($credentials)
    {
        $this->senderId = $credentials['sender_id'];
        $this->senderPassword = $credentials['sender_password'];
        $this->companyId = $credentials['company_id'];
        $this->userId = $credentials['user_id'];
        $this->userPassword = $credentials['user_password'];
    }

    public function authenticate()
    {
        $xml = $this->buildAuthXml();
        $response = $this->sendRequest($xml);

        $xmlObj = simplexml_load_string($response);

        // Check for errors
        $errors = $xmlObj->xpath('//errormessage/error/description2');
        if (!empty($errors)) {
            throw new Exception('Auth failed: ' . (string)$errors[0]);
        }

        // Extract session ID
        $sessionId = $xmlObj->xpath('//result/data/api/sessionid');
        if (empty($sessionId)) {
            throw new Exception('No session ID in response');
        }

        $this->sessionId = (string)$sessionId[0];
        return $this->sessionId;
    }

    private function buildAuthXml()
    {
        $controlId = $this->generateControlId();

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<request>
    <control>
        <senderid>{$this->senderId}</senderid>
        <password>{$this->senderPassword}</password>
        <controlid>{$controlId}</controlid>
        <uniqueid>false</uniqueid>
        <dtdversion>3.0</dtdversion>
    </control>
    <operation>
        <authentication>
            <login>
                <userid>{$this->userId}</userid>
                <companyid>{$this->companyId}</companyid>
                <password>{$this->userPassword}</password>
            </login>
        </authentication>
        <content>
            <function controlid="{$controlId}_func">
                <getAPISession/>
            </function>
        </content>
    </operation>
</request>
XML;
    }

    public function createRecord($objectName, $fields)
    {
        // Ensure authenticated
        if (!$this->sessionId) {
            $this->authenticate();
        }

        $controlId = $this->generateControlId();

        // Build fields XML
        $fieldsXml = '';
        foreach ($fields as $fieldName => $value) {
            $escapedName = htmlspecialchars($fieldName);
            $escapedValue = htmlspecialchars($value);
            $fieldsXml .= "<{$escapedName}>{$escapedValue}</{$escapedName}>\n";
        }

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<request>
    <control>
        <senderid>{$this->senderId}</senderid>
        <password>{$this->senderPassword}</password>
        <controlid>{$controlId}</controlid>
        <uniqueid>false</uniqueid>
        <dtdversion>3.0</dtdversion>
    </control>
    <operation>
        <authentication>
            <sessionid>{$this->sessionId}</sessionid>
        </authentication>
        <content>
            <function controlid="{$controlId}_func">
                <create>
                    <{$objectName}>
                        {$fieldsXml}
                    </{$objectName}>
                </create>
            </function>
        </content>
    </operation>
</request>
XML;

        return $this->sendRequest($xml);
    }

    private function sendRequest($xml)
    {
        $ch = curl_init('https://api.intacct.com/ia/xml/xmlgw.phtml');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/xml',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("HTTP {$httpCode}");
        }

        return $response;
    }

    private function generateControlId()
    {
        return 'APP_' . time() . '_' . uniqid();
    }
}

// Usage:
$client = new SageIntacctXmlClient([
    'sender_id' => 'YOUR_SENDER_ID',
    'sender_password' => 'YOUR_SENDER_PASSWORD',
    'company_id' => 'YOUR_COMPANY_ID',
    'user_id' => 'WEB_SERVICES_USER',
    'user_password' => 'USER_PASSWORD',
]);

$client->authenticate();

$result = $client->createRecord('gifts', [
    'donor_perfect_gift_id' => 12345,
    'gift_date' => '2025-11-07',
    'amount' => '150.00',
    'firstName' => 'John',
    'lastName' => 'Doe',
]);
```

---

## Laravel-Specific Implementation

### Using Laravel HTTP Client

```php
use Illuminate\Support\Facades\Http;

$response = Http::timeout(60)
    ->withHeaders([
        'Content-Type' => 'application/xml',
    ])
    ->withBody($xmlPayload, 'application/xml')
    ->post('https://api.intacct.com/ia/xml/xmlgw.phtml');

if (!$response->successful()) {
    Log::error('Sage Intacct request failed', [
        'status' => $response->status(),
        'body' => $response->body(),
    ]);
}

$xmlResponse = $response->body();
```

### Encrypted Credentials (Laravel)

```php
// In your model
protected $casts = [
    'sender_password' => 'encrypted',
    'client_secret' => 'encrypted',
];

// Laravel automatically encrypts/decrypts
$password = $setting->sender_password; // Auto-decrypted
```

### Cache Session ID

```php
use Illuminate\Support\Facades\Cache;

// Cache session for 4 hours
$sessionId = Cache::remember(
    'sage_intacct_session_' . $companyId,
    now()->addHours(4),
    function () {
        return $this->authenticate();
    }
);
```

---

## Troubleshooting

### Enable Debug Logging

**Temporarily log full XML:**

```php
Log::debug('Full XML Request', ['xml' => $xmlRequest]);
Log::debug('Full XML Response', ['xml' => $xmlResponse]);
```

**Review in Sage Intacct:**
- Platform Services → Web Services Logs
- View recent API calls
- See request/response details
- Check for authorization issues

### Common Pitfalls We Discovered

#### 1. Database Schema Mismatches

When integrating Sage with existing databases:
- **Check exact column names** (case-sensitive)
- **Verify enum values** match exactly
- **Test with actual database structure**, not documentation

**Example Issues We Hit:**
- Table: `sync_history` vs `sync_histories`
- Column: `successful_count` vs `synced_records`
- Status: `success` vs `completed` vs `synced`
- Column: `intacct_record_id` vs `intacct_record_key`

**Solution:** Always query actual table structure:
```sql
DESCRIBE your_table_name;
```

#### 2. XPath Variations

Session ID and results can be in different locations:

```php
// Try multiple XPaths
$sessionId = $xml->xpath('//result/data/api/sessionid');
if (empty($sessionId)) {
    $sessionId = $xml->xpath('//data/api/sessionid');
}
if (empty($sessionId)) {
    $sessionId = $xml->xpath('//sessionid');
}

// Batch results
$results = $xml->xpath('//operation/result');
if (empty($results)) {
    $results = $xml->xpath('//function/result');
}
```

#### 3. Custom Object Element Names

**Always use integration name:**
```php
// From Sage UI: Get the "Integration name" field
$integrationName = 'gifts'; // NOT "Donor Perfect Gifts"

// Build XML
$xml = "<{$integrationName}></{$integrationName}>";
```

#### 4. Required vs Optional Fields

**Before syncing:**
- Review custom object in Sage
- Note which fields are "Required"
- Either provide values OR make fields optional

**Field types that often cause issues:**
- File upload fields (require file or make optional)
- Picklist fields (value must match picklist)
- Date fields (format: YYYY-MM-DD or MM/DD/YYYY)
- Numeric fields (no letters allowed)

---

## Testing Strategy

### 1. Start with Sandbox

Always use `-sandbox` company for testing:
```php
$companyId = 'YOURCOMPANY-sandbox';
```

### 2. Test Authentication First

```php
$result = $client->authenticate();
if ($result['success']) {
    echo "✅ Authentication working\n";
} else {
    echo "❌ Auth failed: {$result['message']}\n";
}
```

### 3. Test Single Record Before Batch

```php
// Test with one record first
$result = $client->createRecord('gifts', [
    'donor_perfect_gift_id' => 99999,
    'gift_date' => '2025-11-07',
    'amount' => '1.00',
]);

// Check result
if ($result['success']) {
    echo "✅ Single record created\n";
    // Now try batch
} else {
    echo "❌ Failed: {$result['error']}\n";
    // Fix issues before batch
}
```

### 4. Validate Field Mappings

Before full sync, verify:
- All required fields are mapped
- Field names match Sage exactly (case-sensitive)
- Data types compatible (text→text, numeric→numeric)
- Picklist values are valid

---

## Sample Laravel Service Class Structure

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SageIntacctService
{
    private $setting;
    private $sessionId = null;

    public function __construct($setting)
    {
        $this->setting = $setting;
    }

    // Authentication
    public function authenticate(): array { }
    private function buildAuthenticationXml(): string { }

    // Record Operations
    public function createRecord(string $objectName, array $data): array { }
    public function batchCreateRecords(string $objectName, array $records): array { }
    public function readRecord(string $objectName, string $recordId): array { }
    public function updateRecord(string $objectName, string $recordId, array $data): array { }
    public function deleteRecord(string $objectName, string $recordId): array { }

    // XML Building
    private function buildXmlRequest(string $function, array $params): string { }
    private function buildBatchXml(string $objectName, array $records): string { }

    // HTTP Communication
    private function sendRequest(string $xml): array { }

    // Response Parsing
    private function parseXmlResponse(string $xml): array { }
    private function parseBatchResponse(string $xml): array { }

    // Utilities
    private function generateControlId(): string { }
    private function escapeXmlValue($value): string { }
}
```

---

## Configuration Checklist

### Before Starting Development

- [ ] Obtain Sender ID and Password from Sage
- [ ] Verify Sender ID is authorized for target company
  - Sage → Company → Company Info → Security → Web Services Authorizations
- [ ] Create Web Services user in Sage Intacct
  - Company → Web Services users → Add
- [ ] Grant appropriate permissions to Web Services user
- [ ] Get Company ID (Company → Overview → Company Information)
- [ ] Identify custom object integration names (not display names)
- [ ] Review custom object field requirements
- [ ] Test with sandbox company first

### For OAuth Applications

- [ ] Register app in Sage Developer Portal
- [ ] Configure redirect URIs (must be publicly accessible)
- [ ] Get Client ID and Client Secret
- [ ] Verify redirect URI matches exactly (HTTPS required)
- [ ] Understand 5-minute token expiration
- [ ] Implement token refresh logic

---

## Quick Reference: HTTP Codes

| Code | Meaning | Action |
|------|---------|--------|
| 200 | Success | Parse XML for operation status |
| 400 | Bad Request | Check XML structure |
| 401 | Unauthorized | Check sender ID/password |
| 403 | Forbidden | Check company authorization |
| 500 | Server Error | Retry or contact Sage support |

**Note:** HTTP 200 doesn't mean success! Always check:
```xml
<control><status>success</status></control>
<operation><result><status>success</status></result></operation>
```

---

## Sage Intacct Support

**Developer Documentation:**
- XML API: https://developer.intacct.com/api/
- Web Services: https://developer.intacct.com/web-services/
- REST API: https://developer.sage.com/intacct

**Support IDs:**
- Extract from error messages: `[Support ID: ABC123...]`
- Reference when contacting Sage support
- Helps them locate exact request in logs

**Web Services Logs:**
- Sage Intacct → Platform Services → Web Services Logs
- View recent API activity
- See request/response details
- Debug authorization issues

---

## Security Best Practices

### Credential Storage

```php
// ✅ DO: Encrypt in database
protected $casts = [
    'sender_password' => 'encrypted',
];

// ✅ DO: Use environment variables
$senderId = env('SAGE_SENDER_ID');

// ❌ DON'T: Hardcode in code
$password = 'actual_password_here'; // NEVER DO THIS

// ❌ DON'T: Store in plain text
$password = 'plain_text'; // NEVER
```

### API Access

```php
// ✅ DO: Use dedicated Web Services user
$userId = 'APIUser';

// ✅ DO: Grant minimal permissions
// Only permissions needed for integration

// ❌ DON'T: Use admin account
// ❌ DON'T: Grant full permissions if not needed
```

### Logging

```php
// ✅ DO: Log sanitized data
Log::info('Auth attempt', [
    'sender_id' => $senderId,
    'company_id' => $companyId,
]);

// ❌ DON'T: Log passwords
Log::info('Auth', [
    'password' => $password, // NEVER LOG PASSWORDS
]);
```

---

## Performance Optimization

### 1. Batch When Possible

```php
// ❌ Slow: One request per record
foreach ($records as $record) {
    $client->createRecord('gifts', $record);
}

// ✅ Fast: Batch up to 100 records
$batches = array_chunk($records, 100);
foreach ($batches as $batch) {
    $client->batchCreateRecords('gifts', $batch);
}
```

### 2. Reuse Sessions

```php
// ❌ Slow: Authenticate every request
foreach ($batches as $batch) {
    $client->authenticate();
    $client->createBatch($batch);
}

// ✅ Fast: Authenticate once
$client->authenticate();
foreach ($batches as $batch) {
    $client->createBatch($batch);
}
```

### 3. Cache Sessions (If Appropriate)

```php
// Cache for shorter than session timeout
Cache::remember('sage_session', now()->addHours(2), function () {
    return $client->authenticate();
});
```

---

## Summary

### Key Takeaways

1. **XML element names cannot have spaces** - Use integration names
2. **Always escape special characters** - Use htmlspecialchars()
3. **Check multiple response levels** - Control, operation, function, errormessage
4. **Session ID location varies** - Try multiple XPaths
5. **Batch up to 100 records** - For performance
6. **Required fields must be provided** - Or make optional in Sage
7. **Test in sandbox first** - Use `-sandbox` company
8. **Store credentials encrypted** - Security best practice
9. **Case-sensitive everywhere** - Field names, credentials, values
10. **HTTP 200 ≠ Success** - Always parse XML response status

### XML vs REST Decision Tree

```
Is your app behind a firewall?
├─ YES → Use XML Web Services
└─ NO (publicly accessible)
   ├─ Need user-level authorization?
   │  ├─ YES → Use REST API OAuth
   │  └─ NO → Use XML Web Services
   └─ Can handle 5-min token expiration?
      ├─ YES → REST API okay
      └─ NO → Use XML Web Services
```

**For most internal/automated integrations: Use XML Web Services** ✅

---

**This guide is based on real-world implementation experience with Sage Intacct API, including all the edge cases and pitfalls discovered during development.**

Good luck with your Sage Intacct integrations! 🚀
