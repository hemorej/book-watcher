<?php

use App\Enums\BookStatus;
use App\Services\SecondarySource\CcaSecondarySource;
use Illuminate\Support\Facades\Http;

function ccaResultHtml(string $title, bool $availableInStore): string
{
    $info = json_encode(['price' => '$65.00', 'isbn' => '9780300167047', 'id' => '46074', 'title' => $title]);
    $encodedInfo = htmlspecialchars($info, ENT_QUOTES);
    $availabilityText = $availableInStore ? '(available in store)' : '';

    return <<<HTML
        <div class="item item-type-img book">
            <a data-info="{$encodedInfo}">Buy book</a>
            <div class="row-2">
                Price:<br>\$65.00
                {$availabilityText}
            </div>
        </div>
        HTML;
}

test('returns available when the matched item is in stock', function () {
    Http::fake(['www.cca.qc.ca/*' => Http::response(ccaResultHtml('Bertrand Goldberg: architecture of invention', true))]);

    $match = (new CcaSecondarySource)->search('Bertrand Goldberg: architecture of invention', 'Zoë Ryan');

    expect($match)->not->toBeNull()
        ->and($match->status)->toBe(BookStatus::Available)
        ->and($match->source)->toBe('CCA Bookstore')
        ->and($match->confidence)->toBe(100.0);
});

test('returns unavailable when the matched item lacks the in-store marker', function () {
    Http::fake(['www.cca.qc.ca/*' => Http::response(ccaResultHtml('Bertrand Goldberg: architecture of invention', false))]);

    $match = (new CcaSecondarySource)->search('Bertrand Goldberg: architecture of invention', 'Zoë Ryan');

    expect($match)->not->toBeNull()
        ->and($match->status)->toBe(BookStatus::Unavailable);
});

test('returns null when no result is a plausible title match', function () {
    Http::fake(['www.cca.qc.ca/*' => Http::response(ccaResultHtml('Completely Unrelated Book', true))]);

    $match = (new CcaSecondarySource)->search('Bertrand Goldberg: architecture of invention', 'Zoë Ryan');

    expect($match)->toBeNull();
});

test('returns null on a non-200 response', function () {
    Http::fake(['www.cca.qc.ca/*' => Http::response('', 500)]);

    expect((new CcaSecondarySource)->search('Anything', ''))->toBeNull();
});
