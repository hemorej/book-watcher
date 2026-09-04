<?php

use App\Enums\BookStatus;
use App\Services\BookChecker\LibrarymanChecker;

test('supports libraryman urls only', function () {
    $checker = new LibrarymanChecker;

    expect($checker->supports('https://www.libraryman.se/gerry-johansson-antarktis'))->toBeTrue()
        ->and($checker->supports('https://mackbooks.co.uk/products/some-book'))->toBeFalse();
});

test('detects available when any edition lacks the out class', function () {
    $checker = new LibrarymanChecker;

    $html = <<<'HTML'
        <span class="btn btn-add out">Out of print</span>
        <span class="btn btn-add">Add to cart</span>
        HTML;

    expect($checker->check($html, 'https://www.libraryman.se/gerry-johansson-antarktis'))
        ->toBe(BookStatus::Available);
});

test('detects unavailable when every edition is out of print', function () {
    $checker = new LibrarymanChecker;

    $html = <<<'HTML'
        <span class="btn btn-add out">Out of print</span>
        <span class="btn btn-add out">Out of print</span>
        HTML;

    expect($checker->check($html, 'https://www.libraryman.se/gerry-johansson-antarktis'))
        ->toBe(BookStatus::Unavailable);
});

test('returns unsure when no edition buttons are found', function () {
    $checker = new LibrarymanChecker;

    expect($checker->check('<div>nothing here</div>', 'https://www.libraryman.se/gerry-johansson-antarktis'))
        ->toBe(BookStatus::Unsure);
});
