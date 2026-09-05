<?php

use App\Support\TitleMatcher;

test('scores identical titles at 100', function () {
    expect(TitleMatcher::score('Thirty Cuts', 'Thirty Cuts'))->toBe(100.0);
});

test('ignores case, punctuation, and whitespace differences', function () {
    expect(TitleMatcher::score('Jim Goldberg: Fingerprint', 'jim goldberg fingerprint'))->toBe(100.0);
});

test('scores unrelated titles low', function () {
    expect(TitleMatcher::score('Thirty Cuts', 'The Photographer\'s Playbook'))->toBeLessThan(40.0);
});
