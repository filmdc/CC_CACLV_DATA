<?php

use App\Models\DonorPerfectSetting;
use App\Services\DonorPerfectApiService;
use Illuminate\Support\Facades\Http;

/*
 * During the Nov 2026 DonorPerfect outage the XML API first returned 502s,
 * then redirected to the HTML status page — which surfaced on DP Sync as
 * "XML parsing failed: Specification mandates value for attribute crossorigin".
 * Both must read as a plain "DonorPerfect is unavailable" message instead.
 */
function dpService(): DonorPerfectApiService
{
    return new DonorPerfectApiService(new DonorPerfectSetting([
        'api_url' => 'https://www.donorperfect.net/prod/xmlrequest.asp',
        'api_key' => 'test-key',
        'api_login' => 'login',
        'api_password' => 'secret',
    ]));
}

$statusPage = '<!DOCTYPE html><html lang="en"><head><title>DonorPerfect Status</title><link rel="preconnect" crossorigin></head><body></body></html>';

it('reports an outage when the API redirects to the HTML status page', function () use ($statusPage) {
    Http::fake([
        'www.donorperfect.net/*' => Http::response('', 302, ['Location' => 'https://status.donorperfect.com/']),
        'status.donorperfect.com/*' => Http::response($statusPage, 200, ['Content-Type' => 'text/html; charset=utf-8']),
    ]);

    $result = dpService()->getGifts('2026-11-04', '2026-11-11');

    expect($result['error'])->toBe(DonorPerfectApiService::UNAVAILABLE_MESSAGE)
        ->and($result['auth_error'])->toBeFalse();
})->group('regression');

it('reports an outage when the API returns a gateway error', function () {
    Http::fake(['*' => Http::response('error code: 502', 502)]);

    expect(dpService()->getGifts('2026-11-04', '2026-11-11')['error'])
        ->toBe(DonorPerfectApiService::UNAVAILABLE_MESSAGE);
})->group('regression');

it('reports an outage on the connection test', function () use ($statusPage) {
    Http::fake(['*' => Http::response($statusPage, 200, ['Content-Type' => 'text/html'])]);

    expect(dpService()->testConnection())
        ->toMatchArray(['success' => false, 'message' => DonorPerfectApiService::UNAVAILABLE_MESSAGE]);
})->group('regression');

it('still parses a normal XML response', function () {
    Http::fake(['*' => Http::response('<?xml version="1.0"?><result></result>', 200, ['Content-Type' => 'text/xml'])]);

    expect(dpService()->getGifts('2026-11-04', '2026-11-11'))->not->toHaveKey('error');
})->group('regression');

it('links the status page in the outage message and escapes everything else', function () {
    $html = (string) DonorPerfectApiService::linkifyStatusPage(DonorPerfectApiService::UNAVAILABLE_MESSAGE);

    expect($html)->toContain('<a href="https://status.donorperfect.com/" target="_blank" rel="noopener noreferrer"');

    $hostile = (string) DonorPerfectApiService::linkifyStatusPage('<script>alert(1)</script> see status.donorperfect.com');

    expect($hostile)->toContain('&lt;script&gt;')
        ->not->toContain('<script>')
        ->toContain('>status.donorperfect.com</a>');
})->group('regression');
