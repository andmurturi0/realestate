<?php

namespace App\Enums;

enum PropertyStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Reserved = 'reserved';
    case Sold = 'sold';
    case Rented = 'rented';
    case Archived = 'archived';
}
