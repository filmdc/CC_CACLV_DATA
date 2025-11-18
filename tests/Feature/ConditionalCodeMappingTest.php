<?php

use App\Models\ConditionalCodeMapping;

beforeEach(function () {
    // Create test conditional mappings
    ConditionalCodeMapping::create([
        'campaign_code' => 'TEST_CAMPAIGN',
        'field_type' => 'solicit_code',
        'dp_code' => 'TEST_SOLICIT_1',
        'intacct_code' => 'F1000',
        'is_active' => true,
        'priority' => 0,
        'notes' => 'Test mapping 1',
    ]);

    ConditionalCodeMapping::create([
        'campaign_code' => 'TEST_CAMPAIGN',
        'field_type' => 'solicit_code',
        'dp_code' => 'TEST_SOLICIT_2',
        'intacct_code' => 'F2000',
        'is_active' => true,
        'priority' => 0,
        'notes' => 'Test mapping 2',
    ]);

    ConditionalCodeMapping::create([
        'campaign_code' => 'TEST_CAMPAIGN',
        'field_type' => 'sub_solicit_code',
        'dp_code' => 'TEST_SUBSOLICIT_1',
        'intacct_code' => 'S1000',
        'is_active' => true,
        'priority' => 0,
        'notes' => 'Test sub-solicit mapping',
    ]);

    // Create inactive mapping (should be ignored)
    ConditionalCodeMapping::create([
        'campaign_code' => 'TEST_CAMPAIGN',
        'field_type' => 'solicit_code',
        'dp_code' => 'INACTIVE_CODE',
        'intacct_code' => 'F9999',
        'is_active' => false,
        'priority' => 0,
        'notes' => 'Inactive mapping',
    ]);
});

test('getMapping returns correct intacct code for valid combination', function () {
    $result = ConditionalCodeMapping::getMapping('TEST_CAMPAIGN', 'solicit_code', 'TEST_SOLICIT_1');
    expect($result)->toBe('F1000');

    $result = ConditionalCodeMapping::getMapping('TEST_CAMPAIGN', 'solicit_code', 'TEST_SOLICIT_2');
    expect($result)->toBe('F2000');

    $result = ConditionalCodeMapping::getMapping('TEST_CAMPAIGN', 'sub_solicit_code', 'TEST_SUBSOLICIT_1');
    expect($result)->toBe('S1000');
});

test('getMapping returns null for non-existent combination', function () {
    $result = ConditionalCodeMapping::getMapping('TEST_CAMPAIGN', 'solicit_code', 'NON_EXISTENT');
    expect($result)->toBeNull();

    $result = ConditionalCodeMapping::getMapping('UNKNOWN_CAMPAIGN', 'solicit_code', 'TEST_SOLICIT_1');
    expect($result)->toBeNull();
});

test('getMapping ignores inactive mappings', function () {
    $result = ConditionalCodeMapping::getMapping('TEST_CAMPAIGN', 'solicit_code', 'INACTIVE_CODE');
    expect($result)->toBeNull();
});

test('hasRulesForCampaign returns true when rules exist', function () {
    $result = ConditionalCodeMapping::hasRulesForCampaign('TEST_CAMPAIGN', 'solicit_code');
    expect($result)->toBeTrue();

    $result = ConditionalCodeMapping::hasRulesForCampaign('TEST_CAMPAIGN', 'sub_solicit_code');
    expect($result)->toBeTrue();
});

test('hasRulesForCampaign returns false when no rules exist', function () {
    $result = ConditionalCodeMapping::hasRulesForCampaign('UNKNOWN_CAMPAIGN', 'solicit_code');
    expect($result)->toBeFalse();

    $result = ConditionalCodeMapping::hasRulesForCampaign('TEST_CAMPAIGN', 'gl_code');
    expect($result)->toBeFalse();
});

test('isCodeAllowed returns true for allowed codes', function () {
    $result = ConditionalCodeMapping::isCodeAllowed('TEST_CAMPAIGN', 'solicit_code', 'TEST_SOLICIT_1');
    expect($result)->toBeTrue();

    $result = ConditionalCodeMapping::isCodeAllowed('TEST_CAMPAIGN', 'solicit_code', 'TEST_SOLICIT_2');
    expect($result)->toBeTrue();
});

test('isCodeAllowed returns false for disallowed codes when rules exist', function () {
    // Rules exist for TEST_CAMPAIGN + solicit_code, but NOT_ALLOWED is not in the list
    $result = ConditionalCodeMapping::isCodeAllowed('TEST_CAMPAIGN', 'solicit_code', 'NOT_ALLOWED');
    expect($result)->toBeFalse();
});

test('isCodeAllowed returns true for any code when no rules exist', function () {
    // No rules exist for UNKNOWN_CAMPAIGN, so all codes are allowed
    $result = ConditionalCodeMapping::isCodeAllowed('UNKNOWN_CAMPAIGN', 'solicit_code', 'ANY_CODE');
    expect($result)->toBeTrue();

    $result = ConditionalCodeMapping::isCodeAllowed('UNKNOWN_CAMPAIGN', 'solicit_code', 'ANOTHER_CODE');
    expect($result)->toBeTrue();
});

test('isCodeAllowed ignores inactive mappings for validation', function () {
    // INACTIVE_CODE exists but is inactive, so it should not be allowed
    $result = ConditionalCodeMapping::isCodeAllowed('TEST_CAMPAIGN', 'solicit_code', 'INACTIVE_CODE');
    expect($result)->toBeFalse();
});

test('getAllowedCodes returns correct list of allowed codes', function () {
    $result = ConditionalCodeMapping::getAllowedCodes('TEST_CAMPAIGN', 'solicit_code');
    expect($result)->toBeArray();
    expect($result)->toHaveCount(2);
    expect($result)->toContain('TEST_SOLICIT_1');
    expect($result)->toContain('TEST_SOLICIT_2');
    expect($result)->not->toContain('INACTIVE_CODE'); // Should not include inactive
});

test('getAllowedCodes returns empty array when no rules exist', function () {
    $result = ConditionalCodeMapping::getAllowedCodes('UNKNOWN_CAMPAIGN', 'solicit_code');
    expect($result)->toBeArray();
    expect($result)->toHaveCount(0);
});

test('scopes filter correctly', function () {
    // forCampaign scope
    $result = ConditionalCodeMapping::forCampaign('TEST_CAMPAIGN')->get();
    expect($result)->toHaveCount(4); // 3 active + 1 inactive

    // forFieldType scope
    $result = ConditionalCodeMapping::forFieldType('solicit_code')->get();
    expect($result)->toHaveCount(3); // 2 active + 1 inactive solicit codes

    // active scope
    $result = ConditionalCodeMapping::active()->get();
    expect($result)->toHaveCount(3); // Only active mappings

    // Chained scopes
    $result = ConditionalCodeMapping::forCampaign('TEST_CAMPAIGN')
        ->forFieldType('solicit_code')
        ->active()
        ->get();
    expect($result)->toHaveCount(2); // 2 active solicit codes for TEST_CAMPAIGN
});

test('unique constraint prevents duplicate mappings', function () {
    // Attempt to create duplicate mapping
    expect(function () {
        ConditionalCodeMapping::create([
            'campaign_code' => 'TEST_CAMPAIGN',
            'field_type' => 'solicit_code',
            'dp_code' => 'TEST_SOLICIT_1', // Duplicate
            'intacct_code' => 'F3000',
            'is_active' => true,
            'priority' => 0,
        ]);
    })->toThrow(\Illuminate\Database\QueryException::class);
});

test('priority ordering works correctly', function () {
    // Create multiple mappings with different priorities
    ConditionalCodeMapping::create([
        'campaign_code' => 'PRIORITY_TEST',
        'field_type' => 'solicit_code',
        'dp_code' => 'TEST_CODE',
        'intacct_code' => 'LOW_PRIORITY',
        'is_active' => true,
        'priority' => 5,
    ]);

    ConditionalCodeMapping::create([
        'campaign_code' => 'PRIORITY_TEST',
        'field_type' => 'solicit_code',
        'dp_code' => 'TEST_CODE_2',
        'intacct_code' => 'HIGH_PRIORITY',
        'is_active' => true,
        'priority' => 10,
    ]);

    // getMapping should respect priority order (highest first)
    // Note: This test assumes only one mapping per campaign+field+code due to unique constraint
    // Priority is used when querying multiple mappings
    $mappings = ConditionalCodeMapping::forCampaign('PRIORITY_TEST')
        ->forFieldType('solicit_code')
        ->active()
        ->orderBy('priority', 'desc')
        ->get();

    expect($mappings->first()->intacct_code)->toBe('HIGH_PRIORITY');
    expect($mappings->last()->intacct_code)->toBe('LOW_PRIORITY');
});
