<?php

use App\Support\Isbn;

test('normalises a hyphenated ISBN-13', function () {
    expect(Isbn::normalize('978-0-306-40615-7'))->toBe('9780306406157');
});

test('converts a valid ISBN-10 to ISBN-13', function () {
    expect(Isbn::normalize('0306406152'))->toBe('9780306406157');
});

test('handles the trailing X check digit on ISBN-10', function () {
    // 0-8044-2957-X is a well-known valid ISBN-10.
    expect(Isbn::normalize('080442957X'))->toBe('9780804429573');
});

test('rejects a wrong ISBN-13 check digit', function () {
    expect(Isbn::normalize('9780306406150'))->toBeNull();
});

test('rejects a wrong ISBN-10 check digit', function () {
    expect(Isbn::normalize('0306406153'))->toBeNull();
});

test('rejects strings that are not 10 or 13 digits', function () {
    expect(Isbn::normalize('12345'))->toBeNull()
        ->and(Isbn::normalize('not-an-isbn'))->toBeNull()
        ->and(Isbn::normalize(''))->toBeNull();
});
