<?php

namespace App\Enums;

enum ImprovementActivityStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
}
