<?php

namespace App\Enums;

enum OfferStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case InProgress = 'in_progress';
    case Converted = 'converted';
    case Rejected = 'rejected';
}
