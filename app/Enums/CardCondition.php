<?php

namespace App\Enums;

enum CardCondition: string
{
    case Mint = 'mint';
    case NearMint = 'near_mint';
    case Good = 'good';
    case Damaged = 'damaged';
}
