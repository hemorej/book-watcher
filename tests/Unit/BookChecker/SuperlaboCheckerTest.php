<?php

use App\Enums\BookStatus;
use App\Services\BookChecker\SuperlaboChecker;

test('supports superlabo urls only', function () {
    $checker = new SuperlaboChecker;

    expect($checker->supports('https://superlabo.com/products/thirty-cuts'))->toBeTrue()
        ->and($checker->supports('https://mackbooks.co.uk/products/some-book'))->toBeFalse();
});

test('detects available when the add-to-cart button is present', function () {
    $checker = new SuperlaboChecker;

    $html = '<button class="product__add-to-cart button">Add to cart</button>';

    expect($checker->check($html, 'https://superlabo.com/products/thirty-cuts'))->toBe(BookStatus::Available);
});

test('detects unavailable when the button reads sold out', function () {
    $checker = new SuperlaboChecker;

    $html = '<button class="product__add-to-cart button" disabled>Sold out</button>';

    expect($checker->check($html, 'https://superlabo.com/products/thirty-cuts'))->toBe(BookStatus::Unavailable);
});

test('returns unsure when neither marker is present', function () {
    $checker = new SuperlaboChecker;

    expect($checker->check('<div>nothing here</div>', 'https://superlabo.com/products/thirty-cuts'))
        ->toBe(BookStatus::Unsure);
});
