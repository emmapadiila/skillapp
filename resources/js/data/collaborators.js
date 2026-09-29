export const collaborators = [
    { id: 1, name: 'Andrés López', email: 'andres.lopez@empresa.com', position: 'Gerente de Operaciones', area: 'Dirección', axis: 'estrategico', score: 87, status: 'evaluated', period: '2026-S1' },
    { id: 2, name: 'Camilo Ríos', email: 'camilo.rios@empresa.com', position: 'Técnico de Mantenimiento', area: 'Mantenimiento', axis: 'operativo', score: 61, status: 'evaluated', period: '2026-S1' },
    { id: 3, name: 'Carlos Pérez', email: 'carlos.perez@empresa.com', position: 'Supervisor de Producción', area: 'Producción', axis: 'operativo', score: 82, status: 'evaluated', period: '2026-S1' },
    { id: 4, name: 'Daniela Castro', email: 'daniela.castro@empresa.com', position: 'Analista de RRHH', area: 'Talento Humano', axis: 'misional', score: 79, status: 'evaluated', period: '2026-S1' },
    { id: 5, name: 'Felipe Morales', email: 'felipe.morales@empresa.com', position: 'Operario de Producción', area: 'Producción', axis: 'operativo', score: 65, status: 'evaluated', period: '2026-S1' },
    { id: 6, name: 'Jorge Herrera', email: 'jorge.herrera@empresa.com', position: 'Jefe de Calidad', area: 'Calidad', axis: 'misional', score: 76, status: 'evaluated', period: '2026-S1' },
    { id: 7, name: 'Laura Gómez', email: 'laura.gomez@empresa.com', position: 'Coordinadora Comercial', area: 'Comercial', axis: 'misional', score: null, status: 'pending', period: '2026-S1' },
    { id: 8, name: 'Lucía Mendoza', email: 'lucia.mendoza@empresa.com', position: 'Supervisora de Calidad', area: 'Calidad', axis: 'misional', score: 74, status: 'evaluated', period: '2026-S1' },
    { id: 9, name: 'María Rodríguez', email: 'maria.rodriguez@empresa.com', position: 'Analista de Producción', area: 'Producción', axis: 'operativo', score: 78, status: 'evaluated', period: '2026-S1' },
    { id: 10, name: 'Natalia Jiménez', email: 'natalia.jimenez@empresa.com', position: 'Analista Financiera', area: 'Finanzas', axis: 'estrategico', score: 81, status: 'evaluated', period: '2026-S1' },
    { id: 11, name: 'Patricia Soto', email: 'patricia.soto@empresa.com', position: 'Directora Comercial', area: 'Comercial', axis: 'estrategico', score: 84, status: 'evaluated', period: '2026-S1' },
    { id: 12, name: 'Ricardo Torres', email: 'ricardo.torres@empresa.com', position: 'Especialista de Tecnología', area: 'Tecnología', axis: 'operativo', score: 72, status: 'evaluated', period: '2025-S2' },
    { id: 13, name: 'Sofía Ramírez', email: 'sofia.ramirez@empresa.com', position: 'Líder de Servicio', area: 'Atención al cliente', axis: 'misional', score: 89, status: 'evaluated', period: '2025-S2' },
    { id: 14, name: 'Tomás Vargas', email: 'tomas.vargas@empresa.com', position: 'Coordinador de Calidad', area: 'Calidad', axis: 'estrategico', score: null, status: 'pending', period: '2025-S2' },
    { id: 15, name: 'Valentina Ruiz', email: 'valentina.ruiz@empresa.com', position: 'Analista de Talento', area: 'Talento Humano', axis: 'misional', score: null, status: 'in-progress', period: '2025-S2' },
];

export const collaboratorAxes = {
    operativo: { label: 'Operativo', tone: 'blue' },
    misional: { label: 'Misional', tone: 'green' },
    estrategico: { label: 'Estratégico', tone: 'violet' },
};

export const collaboratorStatuses = {
    evaluated: { label: 'Evaluado', tone: 'green' },
    pending: { label: 'Pendiente', tone: 'gray' },
    'in-progress': { label: 'En evaluación', tone: 'blue' },
};