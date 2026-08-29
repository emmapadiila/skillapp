<?php

namespace App\Services;

use App\Contracts\AssessmentAnalyzer;

class NullAssessmentAnalyzer implements AssessmentAnalyzer
{
    public function analyze(array $context): array
    {
        return ['analysis' => null, 'strengths' => [], 'opportunities' => [], 'confidence' => null];
    }
}
