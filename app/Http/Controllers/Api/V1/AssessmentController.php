<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use Illuminate\Http\JsonResponse;

class AssessmentController extends Controller
{
    public function index(): JsonResponse
    {
        $assessments = Assessment::query()
            ->with(['axis', 'questions.skill.category'])
            ->where('is_active', true)
            ->orderBy('name')
            ->paginate(50);

        return response()->json(['data' => $assessments]);
    }
}
