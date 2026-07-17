<?php

namespace App\Enums;

/**
 * Shared by contact_messages and property_requests.
 */
enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case InProgress = 'in_progress';
    case Closed = 'closed';
}
