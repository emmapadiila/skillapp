export const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[character]);
export const normalize = (value) => String(value ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es');
export const axisTone = (axis) => ({ operational: 'blue', mission: 'green', strategic: 'violet', operativo: 'blue', misional: 'green', estrategico: 'violet' })[axis.code ?? normalize(axis.name)] ?? 'gray';

export async function getData(resource) {
    const token = document.querySelector('meta[name="api-access-token"]')?.content;
    if (!token) throw new Error('Inicia sesión para consultar los datos de tu organización.');
    const response = await fetch(`/api/v1/${resource}`, {
        headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
        signal: AbortSignal.timeout(20000),
    });
    if (!response.ok) {
        throw new Error(response.status === 401 ? 'La sesión venció. Vuelve a iniciar sesión.'
            : response.status === 403 ? 'Tu cuenta no tiene permiso para consultar esta información.'
                : 'No se pudieron cargar los datos. Inténtalo de nuevo.');
    }
    return (await response.json()).data;
}

export async function getAll(resource) {
    const records = [];
    let page = 1;
    let lastPage = 1;
    do {
        const result = await getData(`${resource}?per_page=100&page=${page}`);
        if (!Array.isArray(result.data)) throw new Error('La respuesta no contiene una lista válida.');
        records.push(...result.data);
        lastPage = result.last_page;
        page += 1;
    } while (page <= lastPage);
    return records;
}

export function connectionMessage(error) {
    return error.name === 'TimeoutError' || error instanceof TypeError
        ? 'No fue posible conectar con el servidor. Inténtalo de nuevo.' : error.message;
}
