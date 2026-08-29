<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreEmployeeRequest;
use App\Models\Position;
use App\Models\User;
use App\Services\SupabaseAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EmployeeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->with(['company:id,name', 'position:id,name']);

        if (! $request->user()->isSuperadmin()) {
            $query->where('company_id', $request->user()->company_id);
        }

        return response()->json(['data' => $query->latest()->paginate(max(1, min($request->integer('per_page', 20), 100)))]);
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $data = $this->tenantData($request);
        $data['name'] = $data['first_name'].' '.$data['last_name'];
        $user = User::query()->create($data);

        return response()->json(['data' => $user->load('company', 'position')], 201);
    }

    public function show(Request $request, User $employee): JsonResponse
    {
        $this->ensureVisible($request, $employee);

        return response()->json(['data' => $employee->load('company', 'position')]);
    }

    public function update(StoreEmployeeRequest $request, User $employee): JsonResponse
    {
        $this->ensureVisible($request, $employee);
        $data = $this->tenantData($request);
        $data['name'] = $data['first_name'].' '.$data['last_name'];
        $employee->update($data);

        return response()->json(['data' => $employee->refresh()->load('company', 'position')]);
    }

    public function destroy(Request $request, User $employee): JsonResponse
    {
        $this->ensureVisible($request, $employee);
        abort_if($employee->id === $request->user()->id, 422, 'No puede desactivar su propia cuenta.');
        $employee->update(['is_active' => false]);
        $employee->delete();

        return response()->json(status: 204);
    }

    public function invite(Request $request, User $employee, SupabaseAuthService $auth): JsonResponse
    {
        $this->ensureVisible($request, $employee);
        abort_unless($employee->is_active, 422, 'La cuenta debe estar activa para enviar la invitación.');
        $identity = $auth->invite($employee->email, [
            'name' => $employee->name,
            'role' => $employee->role->value,
            'company_id' => $employee->company_id,
        ]);

        if (isset($identity['id'])) {
            $employee->forceFill(['supabase_user_id' => $identity['id']])->save();
        }

        return response()->json(['message' => 'Invitación enviada.', 'data' => $employee->refresh()]);
    }

    private function tenantData(StoreEmployeeRequest $request): array
    {
        $data = $request->validated();
        $companyId = $request->user()->isSuperadmin() ? ($data['company_id'] ?? null) : $request->user()->company_id;

        if ($companyId === null) {
            throw ValidationException::withMessages(['company_id' => 'La empresa es obligatoria.']);
        }

        if (isset($data['position_id']) && ! Position::query()->whereKey($data['position_id'])->where('company_id', $companyId)->exists()) {
            throw ValidationException::withMessages(['position_id' => 'El cargo no pertenece a la empresa.']);
        }

        if (isset($data['manager_id']) && ! User::query()->whereKey($data['manager_id'])->where('company_id', $companyId)->exists()) {
            throw ValidationException::withMessages(['manager_id' => 'El responsable no pertenece a la empresa.']);
        }

        $data['company_id'] = $companyId;

        return $data;
    }

    private function ensureVisible(Request $request, User $employee): void
    {
        abort_unless($request->user()->isSuperadmin() || $employee->company_id === $request->user()->company_id, 404);
        abort_if($employee->role === UserRole::Superadmin && ! $request->user()->isSuperadmin(), 404);
    }
}
