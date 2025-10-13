# CAP TRACK API Developer Manual

## Overview

The CAP TRACK (Community Action Partnership Data Management System) API provides programmatic access to participant/household data, program services, and CSBG reporting functionality. This manual guides developers in integrating with the CAP TRACK system.

**Current Status**: This API documentation represents the recommended implementation structure for CAP TRACK. The API endpoints described here need to be implemented in the Laravel application.

## Table of Contents

1. [Authentication & Security](#authentication--security)
2. [Base Configuration](#base-configuration)
3. [Core Data Models](#core-data-models)
4. [API Endpoints](#api-endpoints)
5. [Response Formats](#response-formats)
6. [Error Handling](#error-handling)
7. [Rate Limiting](#rate-limiting)
8. [Code Examples](#code-examples)
9. [Testing](#testing)
10. [Implementation Guidelines](#implementation-guidelines)

---

## Authentication & Security

### Authentication Methods

CAP TRACK API uses Laravel Sanctum for API authentication with token-based access.

#### 1. Personal Access Tokens (Recommended for External Apps)

```bash
POST /api/auth/tokens
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password",
  "device_name": "Mobile App"
}
```

**Response:**
```json
{
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com"
  }
}
```

#### 2. Session-Based Authentication (For Web Apps)

For web applications using the same domain, leverage Laravel's built-in session authentication.

### Authorization System

CAP TRACK implements a comprehensive Role-Based Access Control (RBAC) system:

- **Roles**: admin, case_manager, data_entry, viewer
- **Permissions**: Resource-based permissions (e.g., `participants.view`, `programs.create`)

### Security Headers

All API requests must include:

```http
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

### Permission Requirements

| Resource | View | Create | Update | Delete |
|----------|------|--------|--------|--------|
| Participants | `participants.view` | `participants.create` | `participants.update` | `participants.delete` |
| Households | `households.view` | `households.create` | `households.update` | `households.delete` |
| Programs | `programs.view` | `programs.create` | `programs.update` | `programs.delete` |
| Services | `services.view` | `services.create` | `services.update` | `services.delete` |
| Contracts | `contracts.view` | `contracts.create` | `contracts.update` | `contracts.delete` |

---

## Base Configuration

### API Base URL

```
Production: https://your-domain.com/api/v1
Development: http://localhost/api/v1
```

### Standard Headers

```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer {token}
X-Requested-With: XMLHttpRequest
```

### Pagination

All list endpoints support pagination:

```
GET /api/v1/participants?page=1&per_page=25
```

**Default**: 25 items per page  
**Maximum**: 100 items per page

---

## Core Data Models

### Participant Model

```json
{
  "id": 1,
  "household_id": 123,
  "reference_data_set_id": 1,
  "is_head_of_household": true,
  "relationship_to_head": "self",
  "first_name": "John",
  "middle_name": "Michael",
  "last_name": "Doe",
  "suffix": null,
  "date_of_birth": "1985-03-15",
  "gender": "male",
  "telephone": "(555) 123-4567",
  "email": "john.doe@email.com",
  "education_level": "high_school_graduate",
  "ethnicity": "not_hispanic_latino",
  "race": ["white"],
  "intake_date": "2025-01-15",
  "last_service_date": "2025-01-20",
  "created_at": "2025-01-15T10:30:00Z",
  "updated_at": "2025-01-20T15:45:00Z",
  "household": {
    "id": 123,
    "household_code": "HH2025001",
    "household_size": 4,
    "annual_income": 45000.00
  }
}
```

### Household Model

```json
{
  "id": 123,
  "household_code": "HH2025001",
  "household_type": "family",
  "household_size": 4,
  "housing_status": "renter",
  "monthly_income": 3750.00,
  "annual_income": 45000.00,
  "hhs_poverty_percentage": 185.50,
  "notes": "Family of four seeking housing assistance",
  "created_at": "2025-01-15T10:30:00Z",
  "updated_at": "2025-01-20T15:45:00Z",
  "participants": [
    {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "is_head_of_household": true,
      "relationship_to_head": "self"
    }
  ],
  "address": {
    "street": "123 Main St",
    "city": "Las Vegas",
    "state": "NV",
    "zip_code": "89101"
  }
}
```

### Program Model

```json
{
  "id": 1,
  "name": "Emergency Food Assistance",
  "description": "Provides emergency food assistance to low-income households",
  "program_type": "csbg",
  "is_active": true,
  "start_date": "2025-01-01",
  "end_date": "2025-12-31",
  "funding_source": "federal",
  "contract_id": 5,
  "created_at": "2025-01-01T00:00:00Z",
  "services": [
    {
      "id": 10,
      "name": "Food Bank Distribution",
      "service_type": "emergency_assistance"
    }
  ]
}
```

### Service Enrollment Model

```json
{
  "id": 1,
  "participant_id": 1,
  "service_id": 10,
  "enrollment_date": "2025-01-15",
  "completion_date": null,
  "status": "active",
  "notes": "Monthly food assistance enrollment",
  "service_data": {
    "frequency": "monthly",
    "benefit_amount": 200.00
  },
  "created_at": "2025-01-15T10:30:00Z"
}
```

---

## API Endpoints

### Authentication Endpoints

#### POST /api/auth/tokens
Create a new personal access token.

**Request:**
```json
{
  "email": "user@example.com",
  "password": "password",
  "device_name": "Mobile App"
}
```

#### DELETE /api/auth/tokens/current
Revoke current token.

#### GET /api/auth/user
Get authenticated user information.

### Participant Endpoints

#### GET /api/v1/participants
List all participants with optional filtering.

**Query Parameters:**
- `page` (integer): Page number
- `per_page` (integer): Items per page (max 100)
- `search` (string): Search by name, email, or phone
- `household_id` (integer): Filter by household
- `program_id` (integer): Filter by program enrollment
- `intake_date_from` (date): Filter by intake date range
- `intake_date_to` (date): Filter by intake date range

**Example:**
```
GET /api/v1/participants?search=john&per_page=10&page=1
```

#### POST /api/v1/participants
Create a new participant.

**Required Fields:**
```json
{
  "household_id": 123,
  "first_name": "John",
  "last_name": "Doe",
  "date_of_birth": "1985-03-15",
  "is_head_of_household": true,
  "intake_date": "2025-01-15"
}
```

#### GET /api/v1/participants/{id}
Get a specific participant with full details.

**Response includes:**
- Participant details
- Household information  
- Service enrollments
- Program enrollments
- Work plan goals

#### PUT /api/v1/participants/{id}
Update participant information.

#### DELETE /api/v1/participants/{id}
Soft delete a participant.

### Household Endpoints

#### GET /api/v1/households
List households with filtering options.

#### POST /api/v1/households
Create a new household.

#### GET /api/v1/households/{id}
Get household details with all members.

#### PUT /api/v1/households/{id}
Update household information.

#### DELETE /api/v1/households/{id}
Delete a household and all associated participants.

### Program Endpoints

#### GET /api/v1/programs
List all programs.

#### POST /api/v1/programs
Create a new program.

#### GET /api/v1/programs/{id}
Get program details including services and enrollments.

#### GET /api/v1/programs/{id}/participants
Get all participants enrolled in a specific program.

### Service Endpoints

#### GET /api/v1/services
List services with program filtering.

#### POST /api/v1/services
Create a new service.

#### GET /api/v1/services/{id}
Get service details.

### Enrollment Endpoints

#### POST /api/v1/enrollments/program
Enroll participant in a program.

```json
{
  "participant_id": 1,
  "program_id": 5,
  "enrollment_date": "2025-01-15",
  "notes": "Initial enrollment"
}
```

#### POST /api/v1/enrollments/service
Enroll participant in a service.

```json
{
  "participant_id": 1,
  "service_id": 10,
  "enrollment_date": "2025-01-15",
  "service_data": {
    "frequency": "monthly",
    "benefit_amount": 200.00
  }
}
```

#### GET /api/v1/enrollments/participant/{id}
Get all enrollments for a participant.

### Reporting Endpoints

#### GET /api/v1/reports/csbg
Generate CSBG compliance reports.

**Query Parameters:**
- `program_id` (integer): Specific program
- `date_from` (date): Report period start
- `date_to` (date): Report period end
- `format` (string): json|csv|excel

#### GET /api/v1/reports/demographics
Get demographic summary data.

---

## Response Formats

### Success Response Structure

```json
{
  "success": true,
  "data": {
    // Resource data or collection
  },
  "meta": {
    "current_page": 1,
    "per_page": 25,
    "total": 150,
    "last_page": 6
  }
}
```

### Single Resource Response

```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Resource Name",
    // ... resource fields
  }
}
```

### Collection Response

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      // ... resource fields
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 25,
    "total": 150,
    "last_page": 6,
    "from": 1,
    "to": 25
  }
}
```

---

## Error Handling

### Error Response Format

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": [
      "Validation error message"
    ]
  },
  "error_code": "VALIDATION_ERROR"
}
```

### HTTP Status Codes

| Code | Description | When Used |
|------|-------------|-----------|
| 200 | OK | Successful GET, PUT requests |
| 201 | Created | Successful POST requests |
| 204 | No Content | Successful DELETE requests |
| 400 | Bad Request | Invalid request data |
| 401 | Unauthorized | Missing or invalid token |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource doesn't exist |
| 422 | Unprocessable Entity | Validation errors |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server-side errors |

### Common Error Scenarios

#### Authentication Error
```json
{
  "success": false,
  "message": "Unauthenticated.",
  "error_code": "UNAUTHENTICATED"
}
```

#### Authorization Error
```json
{
  "success": false,
  "message": "This action is unauthorized.",
  "error_code": "UNAUTHORIZED"
}
```

#### Validation Error
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "first_name": ["The first name field is required."],
    "email": ["The email field must be a valid email address."]
  },
  "error_code": "VALIDATION_ERROR"
}
```

---

## Rate Limiting

### Default Limits

- **Authenticated requests**: 1000 requests per hour
- **Unauthenticated requests**: 100 requests per hour
- **Token creation**: 10 attempts per hour per IP

### Rate Limit Headers

```http
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1641024000
```

---

## Code Examples

### JavaScript/Node.js

#### Authentication
```javascript
const axios = require('axios');

// Create API client
const apiClient = axios.create({
  baseURL: 'https://your-domain.com/api/v1',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// Get access token
async function authenticate(email, password) {
  const response = await apiClient.post('/auth/tokens', {
    email,
    password,
    device_name: 'My Application'
  });
  
  // Store token for future requests
  apiClient.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;
  return response.data;
}

// Example usage
authenticate('user@example.com', 'password')
  .then(auth => console.log('Authenticated:', auth.user))
  .catch(error => console.error('Auth failed:', error.response.data));
```

#### Create Participant
```javascript
async function createParticipant(participantData) {
  try {
    const response = await apiClient.post('/participants', participantData);
    return response.data.data;
  } catch (error) {
    throw new Error(error.response.data.message);
  }
}

// Usage
const newParticipant = {
  household_id: 123,
  first_name: 'Jane',
  last_name: 'Smith',
  date_of_birth: '1990-05-20',
  is_head_of_household: false,
  relationship_to_head: 'spouse',
  intake_date: '2025-01-15'
};

createParticipant(newParticipant)
  .then(participant => console.log('Created:', participant))
  .catch(error => console.error('Error:', error.message));
```

#### List Participants with Pagination
```javascript
async function getParticipants(page = 1, perPage = 25, search = '') {
  const params = new URLSearchParams({
    page: page.toString(),
    per_page: perPage.toString()
  });
  
  if (search) {
    params.append('search', search);
  }
  
  const response = await apiClient.get(`/participants?${params}`);
  return response.data;
}

// Usage with pagination
async function loadAllParticipants() {
  let allParticipants = [];
  let currentPage = 1;
  let hasMorePages = true;
  
  while (hasMorePages) {
    const result = await getParticipants(currentPage);
    allParticipants = [...allParticipants, ...result.data];
    
    hasMorePages = currentPage < result.meta.last_page;
    currentPage++;
  }
  
  return allParticipants;
}
```

### Python

#### Authentication & Basic Operations
```python
import requests
from datetime import datetime

class CAPTrackAPI:
    def __init__(self, base_url):
        self.base_url = base_url
        self.session = requests.Session()
        self.session.headers.update({
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        })
    
    def authenticate(self, email, password, device_name='Python Client'):
        response = self.session.post(f'{self.base_url}/auth/tokens', json={
            'email': email,
            'password': password,
            'device_name': device_name
        })
        
        if response.status_code == 200:
            token = response.json()['token']
            self.session.headers['Authorization'] = f'Bearer {token}'
            return response.json()
        else:
            raise Exception(f'Authentication failed: {response.text}')
    
    def get_participants(self, **params):
        response = self.session.get(f'{self.base_url}/participants', params=params)
        response.raise_for_status()
        return response.json()
    
    def create_participant(self, data):
        response = self.session.post(f'{self.base_url}/participants', json=data)
        response.raise_for_status()
        return response.json()['data']
    
    def enroll_in_service(self, participant_id, service_id, enrollment_data=None):
        data = {
            'participant_id': participant_id,
            'service_id': service_id,
            'enrollment_date': datetime.now().strftime('%Y-%m-%d')
        }
        
        if enrollment_data:
            data['service_data'] = enrollment_data
            
        response = self.session.post(f'{self.base_url}/enrollments/service', json=data)
        response.raise_for_status()
        return response.json()

# Usage example
api = CAPTrackAPI('https://your-domain.com/api/v1')
api.authenticate('user@example.com', 'password')

# Get participants
participants = api.get_participants(search='john', per_page=10)
print(f"Found {participants['meta']['total']} participants")

# Create new participant
new_participant = api.create_participant({
    'household_id': 123,
    'first_name': 'Alice',
    'last_name': 'Johnson',
    'date_of_birth': '1985-08-15',
    'is_head_of_household': False,
    'intake_date': '2025-01-15'
})
print(f"Created participant ID: {new_participant['id']}")
```

### PHP (Laravel/Guzzle)

```php
<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class CAPTrackAPIClient 
{
    private $client;
    private $token;
    
    public function __construct(string $baseUrl) 
    {
        $this->client = new Client([
            'base_uri' => $baseUrl,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ]);
    }
    
    public function authenticate(string $email, string $password, string $deviceName = 'PHP Client'): array
    {
        try {
            $response = $this->client->post('/auth/tokens', [
                'json' => [
                    'email' => $email,
                    'password' => $password,
                    'device_name' => $deviceName
                ]
            ]);
            
            $data = json_decode($response->getBody(), true);
            $this->token = $data['token'];
            
            return $data;
        } catch (RequestException $e) {
            throw new Exception('Authentication failed: ' . $e->getMessage());
        }
    }
    
    public function getParticipants(array $params = []): array
    {
        return $this->makeRequest('GET', '/participants', ['query' => $params]);
    }
    
    public function createParticipant(array $data): array
    {
        return $this->makeRequest('POST', '/participants', ['json' => $data]);
    }
    
    public function getPrograms(): array
    {
        return $this->makeRequest('GET', '/programs');
    }
    
    private function makeRequest(string $method, string $endpoint, array $options = []): array
    {
        if (!$this->token) {
            throw new Exception('Not authenticated. Call authenticate() first.');
        }
        
        $options['headers']['Authorization'] = 'Bearer ' . $this->token;
        
        try {
            $response = $this->client->request($method, $endpoint, $options);
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            throw new Exception('API request failed: ' . $e->getMessage());
        }
    }
}

// Usage
$api = new CAPTrackAPIClient('https://your-domain.com/api/v1');
$auth = $api->authenticate('user@example.com', 'password');

$participants = $api->getParticipants(['search' => 'smith', 'per_page' => 20]);
echo "Found {$participants['meta']['total']} participants\n";
```

---

## Testing

### Test Environment Setup

1. **Base URL**: Use a separate test environment
2. **Test Data**: Create test users with limited permissions
3. **Rate Limiting**: Test environments should have relaxed limits

### Test User Credentials

```
Email: test@captrack.com
Password: TestPassword123!
Role: case_manager
Permissions: participants.*, services.view, programs.view
```

### Postman Collection

A Postman collection is available for testing all endpoints:

```json
{
  "info": {
    "name": "CAP TRACK API",
    "description": "Complete API test collection"
  },
  "auth": {
    "type": "bearer",
    "bearer": [
      {
        "key": "token",
        "value": "{{auth_token}}",
        "type": "string"
      }
    ]
  },
  "variables": [
    {
      "key": "base_url",
      "value": "https://your-domain.com/api/v1"
    }
  ]
}
```

### Unit Testing with PHP (Pest)

```php
<?php

use App\Models\User;
use App\Models\Participant;
use Laravel\Sanctum\Sanctum;

test('can authenticate and get token', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password')
    ]);
    
    $response = $this->postJson('/api/auth/tokens', [
        'email' => 'test@example.com',
        'password' => 'password',
        'device_name' => 'Test Device'
    ]);
    
    $response->assertOk()
             ->assertJsonStructure(['token', 'user']);
});

test('can create participant with valid data', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);
    
    $participantData = [
        'household_id' => 1,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'date_of_birth' => '1985-03-15',
        'is_head_of_household' => true,
        'intake_date' => '2025-01-15'
    ];
    
    $response = $this->postJson('/api/v1/participants', $participantData);
    
    $response->assertCreated()
             ->assertJsonFragment(['first_name' => 'John']);
             
    $this->assertDatabaseHas('participants', ['first_name' => 'John']);
});

test('requires authentication for participant access', function () {
    $response = $this->getJson('/api/v1/participants');
    
    $response->assertUnauthorized();
});
```

---

## Implementation Guidelines

### For Laravel Developers

#### 1. Create API Routes

Add to `routes/api.php`:

```php
<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ParticipantController;
use App\Http\Controllers\Api\HouseholdController;
use App\Http\Controllers\Api\ProgramController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\EnrollmentController;

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('tokens', [AuthController::class, 'createToken']);
    Route::delete('tokens/current', [AuthController::class, 'revokeCurrentToken'])->middleware('auth:sanctum');
    Route::get('user', [AuthController::class, 'user'])->middleware('auth:sanctum');
});

// Protected API routes
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Participants
    Route::apiResource('participants', ParticipantController::class);
    
    // Households  
    Route::apiResource('households', HouseholdController::class);
    
    // Programs
    Route::apiResource('programs', ProgramController::class);
    Route::get('programs/{program}/participants', [ProgramController::class, 'participants']);
    
    // Services
    Route::apiResource('services', ServiceController::class);
    
    // Enrollments
    Route::prefix('enrollments')->group(function () {
        Route::post('program', [EnrollmentController::class, 'enrollInProgram']);
        Route::post('service', [EnrollmentController::class, 'enrollInService']);
        Route::get('participant/{participant}', [EnrollmentController::class, 'participantEnrollments']);
    });
});
```

#### 2. Create API Controllers

Example ParticipantController:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use Illuminate\Http\Request;
use App\Http\Resources\ParticipantResource;
use App\Http\Requests\StoreParticipantRequest;
use App\Http\Requests\UpdateParticipantRequest;

class ParticipantController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Participant::class);
        
        $query = Participant::with(['household']);
        
        // Apply filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        if ($request->has('household_id')) {
            $query->where('household_id', $request->household_id);
        }
        
        $participants = $query->paginate($request->get('per_page', 25));
        
        return ParticipantResource::collection($participants);
    }
    
    public function store(StoreParticipantRequest $request)
    {
        $this->authorize('create', Participant::class);
        
        $participant = Participant::create($request->validated());
        
        return new ParticipantResource($participant->load('household'));
    }
    
    public function show(Participant $participant)
    {
        $this->authorize('view', $participant);
        
        return new ParticipantResource(
            $participant->load(['household', 'serviceEnrollments.service', 'programEnrollments.program'])
        );
    }
    
    public function update(UpdateParticipantRequest $request, Participant $participant)
    {
        $this->authorize('update', $participant);
        
        $participant->update($request->validated());
        
        return new ParticipantResource($participant->load('household'));
    }
    
    public function destroy(Participant $participant)
    {
        $this->authorize('delete', $participant);
        
        $participant->delete();
        
        return response()->noContent();
    }
}
```

#### 3. Create API Resources

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParticipantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'household_id' => $this->household_id,
            'is_head_of_household' => $this->is_head_of_household,
            'relationship_to_head' => $this->relationship_to_head,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'full_name' => $this->first_name . ' ' . $this->last_name,
            'date_of_birth' => $this->date_of_birth,
            'age' => $this->date_of_birth ? now()->diffInYears($this->date_of_birth) : null,
            'gender' => $this->gender,
            'telephone' => $this->telephone,
            'email' => $this->email,
            'intake_date' => $this->intake_date,
            'last_service_date' => $this->last_service_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'household' => new HouseholdResource($this->whenLoaded('household')),
            'service_enrollments' => ServiceEnrollmentResource::collection($this->whenLoaded('serviceEnrollments')),
            'program_enrollments' => ProgramEnrollmentResource::collection($this->whenLoaded('programEnrollments')),
        ];
    }
}
```

#### 4. Setup Sanctum Authentication

In `config/sanctum.php`:

```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
    '%s%s',
    'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
    env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
))),

'guard' => ['web'],

'expiration' => null, // Tokens don't expire by default

'middleware' => [
    'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
    'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
],
```

#### 5. Create Authentication Controller

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function createToken(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return response()->json([
            'token' => $user->createToken($request->device_name)->plainTextToken,
            'user' => $user
        ]);
    }
    
    public function revokeCurrentToken(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        
        return response()->json(['message' => 'Token revoked']);
    }
    
    public function user(Request $request)
    {
        return $request->user();
    }
}
```

---

## Best Practices

### Security
1. **Always validate permissions** using Laravel policies
2. **Sanitize input data** using Form Requests
3. **Rate limit API endpoints** to prevent abuse
4. **Use HTTPS** in production environments
5. **Implement proper CORS** settings for web clients

### Performance
1. **Use eager loading** to prevent N+1 queries
2. **Implement caching** for frequently accessed data
3. **Add database indexes** for search and filter fields
4. **Use pagination** for large datasets

### Error Handling
1. **Return consistent error formats** across all endpoints
2. **Log API errors** for debugging and monitoring
3. **Use appropriate HTTP status codes**
4. **Provide helpful error messages** for developers

### Documentation
1. **Keep this documentation updated** as the API evolves
2. **Provide example requests/responses** for each endpoint
3. **Document breaking changes** and version updates
4. **Include rate limiting information**

---

## Support & Contributing

### Getting Help
- Review this documentation thoroughly
- Check the Laravel and Sanctum documentation for framework-specific questions
- Test endpoints using the provided Postman collection

### Reporting Issues
- Provide specific error messages and HTTP status codes
- Include request/response examples
- Specify which endpoint and method you're using

### API Versioning
The API uses URL-based versioning (`/api/v1/`). Breaking changes will increment the version number.

---

*This API manual is designed to guide the implementation of a REST API for the CAP TRACK system. The endpoints and functionality described here represent the recommended API structure based on the existing data models and business requirements.*