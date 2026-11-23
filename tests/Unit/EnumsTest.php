<?php

use App\Enums\CardCondition;
use App\Enums\CardType;
use App\Enums\PhotocardStatus;

it('has the expected photocard statuses', function () {
    expect(array_column(PhotocardStatus::cases(), 'value'))
        ->toBe(['owned', 'wishlist', 'for_trade']);
});

it('has the expected card types', function () {
    expect(array_column(CardType::cases(), 'value'))
        ->toBe(['album', 'pob', 'lucky_draw', 'fansign', 'membership', 'other']);
});

it('has the expected conditions', function () {
    expect(array_column(CardCondition::cases(), 'value'))
        ->toBe(['mint', 'near_mint', 'good', 'damaged']);
});
