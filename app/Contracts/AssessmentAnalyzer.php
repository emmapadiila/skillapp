<?php

namespace App\Contracts;

interface AssessmentAnalyzer
{
    /** @return array{analysis: ?string, strengths: array, opportunities: array, confidence: ?float} */
    public function analyze(array $context): array;
}
