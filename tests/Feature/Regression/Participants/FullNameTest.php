<?php

use App\Models\Participant;

it('joins only the name parts that are present', function (array $parts, string $expected) {
    expect((new Participant($parts))->full_name)->toBe($expected);
})->with([
    'no middle name' => [['first_name' => 'Ada', 'last_name' => 'Lovelace'], 'Ada Lovelace'],
    'blank middle name' => [['first_name' => 'Ada', 'middle_name' => '  ', 'last_name' => 'Lovelace'], 'Ada Lovelace'],
    'middle name' => [['first_name' => 'Ada', 'middle_name' => 'King', 'last_name' => 'Lovelace'], 'Ada King Lovelace'],
    'suffix' => [['first_name' => 'Ada', 'last_name' => 'Lovelace', 'suffix' => 'Jr.'], 'Ada Lovelace Jr.'],
])->group('regression');
