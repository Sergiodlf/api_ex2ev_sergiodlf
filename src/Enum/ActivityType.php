<?php

namespace App\Enum;

enum ActivityType: string
{
    case BODY_PUMP = 'BodyPump';
    case SPINNING = 'Spinning';
    case CORE = 'Core';
}
