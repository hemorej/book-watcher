<?php

use App\Enums\BookStatus;
use App\Services\BookChecker\SuperlaboChecker;

test('supports superlabo urls only', function () {
    $checker = new SuperlaboChecker;

    expect($checker->supports('https://superlabo.com/products/thirty-cuts'))->toBeTrue()
        ->and($checker->supports('https://mackbooks.co.uk/products/some-book'))->toBeFalse();
});

test('detects available from a dollar price marker', function () {
    $checker = new SuperlaboChecker;

    $html = '<meta property="product:price:amount" content="53.00"> $53.00 <button class="product__add-to-cart button" disabled>Sold out</button>';

    expect($checker->check($html, 'https://superlabo.com/products/thirty-cuts'))->toBe(BookStatus::Available);
});

test('detects available when the add-to-cart button is present and not disabled', function () {
    $checker = new SuperlaboChecker;

    $html = '<button type="submit" name="add" class="product__add-to-cart button">Add to cart</button>';

    expect($checker->check($html, 'https://superlabo.com/products/thirty-cuts'))->toBe(BookStatus::Available);
});

test('ignores unrelated "Sold out" text elsewhere on the page', function () {
    $checker = new SuperlaboChecker;

    $html = <<<'HTML'
        <script>window.product_words_sold_out_variant = "Sold out";</script>
        <button type="submit" name="add" class="product__add-to-cart button">Add to cart</button>
        HTML;

    expect($checker->check($html, 'https://superlabo.com/products/thirty-cuts'))->toBe(BookStatus::Available);
});

test('detects unavailable when the button is disabled and no price is shown', function () {
    $checker = new SuperlaboChecker;

    $html = '<button type="submit" name="add" class="product__add-to-cart button" disabled>Sold out</button>';

    expect($checker->check($html, 'https://superlabo.com/products/thirty-cuts'))->toBe(BookStatus::Unavailable);
});

test('returns unavailable when neither marker is found', function () {
    $checker = new SuperlaboChecker;

    expect($checker->check('<div>nothing here</div>', 'https://superlabo.com/products/thirty-cuts'))
        ->toBe(BookStatus::Unavailable);
});
