<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Assessment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $isCollaborator = $request->user()->role === UserRole::Collaborator;

        $assessments = Assessment::query()
            ->with(['axis', 'skills', 'questions.skill.category', 'questions.options'])
            ->where('is_active', true)
            // El colaborador solo ve la prueba del eje de su cargo.
            ->when($isCollaborator, fn ($query) => $query->where(
                'organizational_axis_id',
                $request->user()->position?->organizational_axis_id,
            ))
            ->orderBy('name')
            ->paginate(50);

        if ($isCollaborator) {
            $assessments->getCollection()->each(fn (Assessment $assessment) => $assessment->questions
                ->each(fn ($question) => $question->options->each->makeHidden('score')));
        }

        return response()->json(['data' => $assessments]);
    }
}
