<?php

namespace App\Enums\Task;

enum TaskStatus: string
{
    case Pending     = 'pending';
    case InProgress  = 'in_progress';
    case Completed   = 'completed';
}
