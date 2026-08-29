<?php

namespace App\Enums;

enum ResourceType: string
{
    case Reading = 'reading';
    case Video = 'video';
    case Exercise = 'exercise';
}
