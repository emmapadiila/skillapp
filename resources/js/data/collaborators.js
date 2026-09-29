import { getAll, axisTone } from './platform';

export const collaborators = [];
export const collaboratorAxes = { unknown: { label: 'Sin eje asignado', tone: 'gray' } };
export const collaboratorStatuses = {
    evaluated: { label: 'Evaluado', tone: 'green' },
    pending: { label: 'Pendiente', tone: 'gray' },
    'in-progress': { label: 'En evaluación', tone: 'blue' },
    expired: { label: 'Vencida', tone: 'gray' },
    unassigned: { label: 'Sin evaluación', tone: 'gray' },
};

export async function loadCollaborators() {
    const [employees, positions, evaluations] = await Promise.all([getAll('employees'), getAll('positions'), getAll('evaluations')]);
    positions.forEach((position) => {
        const axis = position.organizational_axis;
        if (axis) collaboratorAxes[String(axis.id)] = { label: axis.name, tone: axisTone(axis) };
    });
    const latest = new Map();
    evaluations.forEach((evaluation) => {
        const previous = latest.get(evaluation.user_id);
        if (!previous || new Date(evaluation.assigned_at) > new Date(previous.assigned_at)) latest.set(evaluation.user_id, evaluation);
    });
    collaborators.splice(0, collaborators.length, ...employees.map((employee) => {
        const position = positions.find((item) => item.id === employee.position_id);
        const evaluation = latest.get(employee.id);
        return {
            id: employee.id, name: employee.name, email: employee.email,
            position: position?.name ?? employee.position?.name ?? 'Sin cargo',
            area: position?.area?.name ?? 'Sin área', axis: position?.organizational_axis ? String(position.organizational_axis.id) : 'unknown',
            score: evaluation?.result?.total_score == null ? null : Number(evaluation.result.total_score),
            status: ({ completed: 'evaluated', pending: 'pending', in_progress: 'in-progress', expired: 'expired' })[evaluation?.status] ?? 'unassigned',
        };
    }));
}