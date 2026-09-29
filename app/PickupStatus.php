<?php

namespace App;

enum PickupStatus: string
{
    case Assigned = 'ASSIGNED';
    case Otw = 'OTW';
    case Arrived = 'ARRIVED';
    case Completed = 'COMPLETED';
    case Cancelled = 'CANCELLED';
}
