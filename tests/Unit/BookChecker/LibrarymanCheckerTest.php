<?php

use App\Enums\BookStatus;
use App\Services\BookChecker\LibrarymanChecker;

test('supports libraryman urls only', function () {
    $checker = new LibrarymanChecker;

    expect($checker->supports('https://www.libraryman.se/gerry-johansson-antarktis'))->toBeTrue()
        ->and($checker->supports('https://mackbooks.co.uk/products/some-book'))->toBeFalse();
});

test('detects available from a euro price marker', function () {
    $checker = new LibrarymanChecker;

    $html = <<<'HTML'
        <p>&euro;50</span><br /><small>First edition</small></p>
        <a class="btn btn-add" href="https://www.paypal.com/cgi-bin/webscr" role="button">Add to cart</a>
        HTML;

    expect($checker->check($html, 'https://www.libraryman.se/gerry-johansson-antarktis'))
        ->toBe(BookStatus::Available);
});

test('detects available from a non-disabled add-to-cart button', function () {
    $checker = new LibrarymanChecker;

    $html = '<a class="btn btn-add" href="https://www.paypal.com/cgi-bin/webscr" role="button">Add to cart</a>';

    expect($checker->check($html, 'https://www.libraryman.se/gerry-johansson-antarktis'))
        ->toBe(BookStatus::Available);
});

test('detects unavailable when the add-to-cart button is disabled and no price is shown', function () {
    $checker = new LibrarymanChecker;

    $html = '<a class="btn btn-add disabled" role="button">Out of print</a>';

    expect($checker->check($html, 'https://www.libraryman.se/gerry-johansson-antarktis'))
        ->toBe(BookStatus::Unavailable);
});

test('returns unavailable when neither marker is found', function () {
    $checker = new LibrarymanChecker;

    expect($checker->check('<div>nothing here</div>', 'https://www.libraryman.se/gerry-johansson-antarktis'))
        ->toBe(BookStatus::Unavailable);
});
