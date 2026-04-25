<?php

use App\Support\AddressNormalizer;

it('lowercases and trims', function () {
    $result = AddressNormalizer::normalize('  123 Main St  ', '  Allentown  ');
    expect($result['street'])->toBe('123 main st');
    expect($result['city'])->toBe('allentown');
});

it('collapses whole-word suffix variants to canonical short form', function () {
    $variants = [
        '123 Main Street' => '123 main st',
        '123 Main St.' => '123 main st',
        '123 Main St' => '123 main st',
        '5 Maple Avenue' => '5 maple ave',
        '5 Maple Ave.' => '5 maple ave',
        '8 Oak Road' => '8 oak rd',
        '7 Lincoln Boulevard' => '7 lincoln blvd',
        '12 Sunset Drive' => '12 sunset dr',
        '4 Elm Lane' => '4 elm ln',
        '1 Main Apartment 2' => '1 main apt 2',
    ];

    foreach ($variants as $input => $expected) {
        expect(AddressNormalizer::normalize($input, 'Bethlehem')['street'])
            ->toBe($expected, "Failed normalizing '{$input}'");
    }
});

it('collapses direction prefixes to single letter', function () {
    expect(AddressNormalizer::normalize('123 North Main St', 'X')['street'])
        ->toBe('123 n main st');
    expect(AddressNormalizer::normalize('456 South West Boulevard', 'X')['street'])
        ->toBe('456 s w blvd');
});

it('produces an identical fingerprint for equivalent variants in same city', function () {
    $a = AddressNormalizer::normalize('123 Main Street', 'Allentown');
    $b = AddressNormalizer::normalize('  123 main st.  ', 'allentown');
    expect($a['fingerprint'])->toBe($b['fingerprint']);
});

it('produces a different fingerprint for different cities', function () {
    $a = AddressNormalizer::normalize('123 Main St', 'Allentown');
    $b = AddressNormalizer::normalize('123 Main St', 'Bethlehem');
    expect($a['fingerprint'])->not->toBe($b['fingerprint']);
});

it('handles null inputs safely', function () {
    $result = AddressNormalizer::normalize(null, null);
    expect($result['street'])->toBe('');
    expect($result['city'])->toBe('');
    expect($result['fingerprint'])->toBe('|');
});

it('extracts street number and first non-direction word', function () {
    expect(AddressNormalizer::streetNumberAndFirstWord('123 Main St'))
        ->toBe(['123', 'main']);
    expect(AddressNormalizer::streetNumberAndFirstWord('123 N Main St'))
        ->toBe(['123', 'main']);
    expect(AddressNormalizer::streetNumberAndFirstWord('  456 South Maple Ave  '))
        ->toBe(['456', 'maple']);
});

it('returns null when street has no leading number', function () {
    expect(AddressNormalizer::streetNumberAndFirstWord('Main St'))->toBeNull();
    expect(AddressNormalizer::streetNumberAndFirstWord(''))->toBeNull();
    expect(AddressNormalizer::streetNumberAndFirstWord(null))->toBeNull();
});
