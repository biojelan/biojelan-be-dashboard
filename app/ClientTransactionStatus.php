<?php

namespace App;

enum ClientTransactionStatus: string
{
    case Pending = 'PENDING';
    case Accepted = 'ACCEPTED';
    case Rejected = 'REJECTED';
    case CancelRequested = 'CANCEL_REQUESTED';
    case Cancelled = 'CANCELLED';
}
