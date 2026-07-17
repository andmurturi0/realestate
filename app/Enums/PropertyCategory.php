<?php

namespace App\Enums;

enum PropertyCategory: string
{
    case House = 'house';
    case Apartment = 'apartment';
    case Office = 'office';
    case Store = 'store';
    case Land = 'land';
    case Warehouse = 'warehouse';
    case Object = 'object';
}
