<?php

use App\Enums\BookStatus;
use App\Services\SecondarySource\PolygonSecondarySource;
use Illuminate\Support\Facades\Http;

function polygonSearchHtml(string $title, string $handle): string
{
    return <<<HTML
        <a class="full-width-link" href="/products/{$handle}?_pos=1&_sid=abc&_ss=r">
            <span class="visually-hidden">{$title}</span>
        </a>
        HTML;
}

function polygonProductHtml(bool $soldOut): string
{
    $label = $soldOut ? 'Sold out' : 'Add to cart';

    return <<<HTML
        <button type="submit" name="add" aria-label="{$label}">
            <span data-add-to-cart-text>{$label}</span>
        </button>
        HTML;
}

test('returns available when the matched product page has an active add-to-cart button', function () {
    Http::fake([
        'store.thepolygon.ca/search*' => Http::response(polygonSearchHtml('Jim Goldberg - Fingerprint', 'jim-goldberg-fingerprint')),
        'store.thepolygon.ca/products/*' => Http::response(polygonProductHtml(soldOut: false)),
    ]);

    $match = (new PolygonSecondarySource)->search('Jim Goldberg - Fingerprint', 'Jim Goldberg');

    expect($match)->not->toBeNull()
        ->and($match->status)->toBe(BookStatus::Available)
        ->and($match->source)->toBe('The Polygon')
        ->and($match->url)->toBe('https://store.thepolygon.ca/products/jim-goldberg-fingerprint');
});

test('returns unavailable when the matched product page button reads sold out', function () {
    Http::fake([
        'store.thepolygon.ca/search*' => Http::response(polygonSearchHtml('Jim Goldberg - Fingerprint', 'jim-goldberg-fingerprint')),
        'store.thepolygon.ca/products/*' => Http::response(polygonProductHtml(soldOut: true)),
    ]);

    $match = (new PolygonSecondarySource)->search('Jim Goldberg - Fingerprint', 'Jim Goldberg');

    expect($match)->not->toBeNull()->and($match->status)->toBe(BookStatus::Unavailable);
});

test('returns null when no search result plausibly matches', function () {
    Http::fake([
        'store.thepolygon.ca/search*' => Http::response(polygonSearchHtml('Completely Unrelated Book', 'unrelated')),
    ]);

    $match = (new PolygonSecondarySource)->search('Jim Goldberg - Fingerprint', 'Jim Goldberg');

    expect($match)->toBeNull();
});

test('returns null on a non-200 search response', function () {
    Http::fake(['store.thepolygon.ca/search*' => Http::response('', 500)]);

    expect((new PolygonSecondarySource)->search('Anything', ''))->toBeNull();
});
