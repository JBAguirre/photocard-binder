<?php

namespace App\Enums;

enum CardType: string
{
    case Album = 'album';
    case Pob = 'pob';
    case LuckyDraw = 'lucky_draw';
    case Fansign = 'fansign';
    case Membership = 'membership';
    case Other = 'other';
}