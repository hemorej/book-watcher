<?php

use App\Enums\BookStatus;
use App\Services\SecondarySource\PhotobookstoreSecondarySource;
use Illuminate\Support\Facades\Http;

function pbsSearchHtml(string $title, string $handle): string
{
    return <<<HTML
        <a href="/products/{$handle}?_pos=1&_sid=abc&_ss=r" class="grid-product__link ">
            <div class="grid-product__image-mask">stuff</div>
            <div class="grid-product__title grid-product__title--body">{$title}</div>
        </a>
        HTML;
}

function pbsProductHtml(bool $soldOut): string
{
    $label = $soldOut ? 'Sold Out' : 'Add to cart';

    return <<<HTML
        <button
          type="submit"
          name="add"
          id="AddToCart-123"
          class="btn btn--full add-to-cart">
          <span id="AddToCartText-123">{$label}</span>
        </button>
        HTML;
}

test('returns available when the matched product page has an active add-to-cart button', function () {
    Http::fake([
        'photobookstore.co.uk/search*' => Http::response(pbsSearchHtml('Fingerprint', 'fingerprint')),
        'photobookstore.co.uk/products/*' => Http::response(pbsProductHtml(soldOut: false)),
    ]);

    $match = (new PhotobookstoreSecondarySource)->search('Fingerprint', 'Jim Goldberg');

    expect($match)->not->toBeNull()
        ->and($match->status)->toBe(BookStatus::Available)
        ->and($match->source)->toBe('Photobook Store')
        ->and($match->url)->toBe('https://photobookstore.co.uk/products/fingerprint');
});

test('returns unavailable when the matched product page button reads sold out', function () {
    Http::fake([
        'photobookstore.co.uk/search*' => Http::response(pbsSearchHtml('Fingerprint', 'fingerprint')),
        'photobookstore.co.uk/products/*' => Http::response(pbsProductHtml(soldOut: true)),
    ]);

    $match = (new PhotobookstoreSecondarySource)->search('Fingerprint', 'Jim Goldberg');

    expect($match)->not->toBeNull()->and($match->status)->toBe(BookStatus::Unavailable);
});

test('returns null when no search result plausibly matches', function () {
    Http::fake([
        'photobookstore.co.uk/search*' => Http::response(pbsSearchHtml('Completely Unrelated Book', 'unrelated')),
    ]);

    $match = (new PhotobookstoreSecondarySource)->search('Fingerprint', 'Jim Goldberg');

    expect($match)->toBeNull();
});

test('returns null on a non-200 search response', function () {
    Http::fake(['photobookstore.co.uk/search*' => Http::response('', 500)]);

    expect((new PhotobookstoreSecondarySource)->search('Anything', ''))->toBeNull();
});
