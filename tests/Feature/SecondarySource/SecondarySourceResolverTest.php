<?php

use App\Enums\BookStatus;
use App\Services\SecondarySource\SecondarySource;
use App\Services\SecondarySource\SecondarySourceMatch;
use App\Services\SecondarySource\SecondarySourceResolver;

function fakeSecondarySource(string $name, ?SecondarySourceMatch $match): SecondarySource
{
    return new class($name, $match) implements SecondarySource
    {
        public function __construct(private string $name, private ?SecondarySourceMatch $match) {}

        public function name(): string
        {
            return $this->name;
        }

        public function search(string $title, string $author): ?SecondarySourceMatch
        {
            return $this->match;
        }
    };
}

test('short-circuits on the first confident, available match', function () {
    $confident = new SecondarySourceMatch('First', 'https://first.example', BookStatus::Available, 95.0);
    $neverCalled = fakeSecondarySource('Second', new SecondarySourceMatch('Second', 'https://second.example', BookStatus::Available, 100.0));

    $resolver = new SecondarySourceResolver([
        fakeSecondarySource('First', $confident),
        $neverCalled,
    ]);

    $result = $resolver->resolve('Some Title', 'Some Author');

    expect($result)->toBe($confident);
});

test('keeps the highest-scoring match when nothing is confidently available', function () {
    $weak = new SecondarySourceMatch('Weak', 'https://weak.example', BookStatus::Unavailable, 50.0);
    $weaker = new SecondarySourceMatch('Weaker', 'https://weaker.example', BookStatus::Unavailable, 45.0);

    $resolver = new SecondarySourceResolver([
        fakeSecondarySource('Weaker', $weaker),
        fakeSecondarySource('Weak', $weak),
    ]);

    $result = $resolver->resolve('Some Title', 'Some Author');

    expect($result)->toBe($weak);
});

test('returns null when every source returns null', function () {
    $resolver = new SecondarySourceResolver([
        fakeSecondarySource('A', null),
        fakeSecondarySource('B', null),
    ]);

    expect($resolver->resolve('Some Title', 'Some Author'))->toBeNull();
});
