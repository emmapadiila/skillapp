<?php

namespace App\Enums;

enum QuestionType: string
{
    case SelfReport = 'self_report';
    case Situational = 'situational';
    case SituationalJudgment = 'situational_judgment';
}
