<?php

namespace App\Enums;

enum PhotocardStatus: string
{
    case Owned = 'owned';
    case Wishlist = 'wishlist';
    case ForTrade = 'for_trade';
}