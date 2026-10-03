export const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[character]);
export const normalize = (value) => String(value ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es');
export const axisTone = (axis) => ({ operational: 'blue', mission: 'green', strategic: 'violet', operativo: 'blue', misional: 'green', estrategico: 'violet' })[axis.code ?? normalize(axis.name)] ?? 'gray';

let refreshing;

export async function requestData(resource, { method = 'GET', data, signal } = {}, retried = false) {
    const token = document.querySelector('meta[name="api-access-token"]')?.content;
    if (!token) throw new Error('Inicia sesión para consultar los datos de tu organización.');
    const response = await fetch(`/api/v1/${resource}`, {
        method,
        headers: { Accept: 'application/json', Authorization: `Bearer ${token}`, ...(data !== undefined ? { 'Content-Type': 'application/json' } : {}) },
        body: data !== undefined ? JSON.stringify(data) : undefined,
        signal: signal ?? AbortSignal.timeout(20000),
    });
    if (response.status === 401 && !retried && document.querySelector('meta[name="session-refresh-url"]')) {
        refreshing ??= refreshSession().finally(() => { refreshing = undefined; });
        await refreshing;
        return requestData(resource, { method, data, signal }, true);
    }
    if (response.status === 204) return null;
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
        const error = new Error(response.status === 401 ? 'La sesión venció. Vuelve a iniciar sesión.'
            : response.status === 403 ? 'Tu cuenta no tiene permiso para consultar esta información.'
                : response.status === 429 ? 'Demasiadas solicitudes. Espera un momento antes de volver a intentar.'
                    : payload.message ?? 'No se pudieron cargar los datos. Inténtalo de nuevo.');
        error.status = response.status;
        error.fields = payload.errors ?? {};
        throw error;
    }
    return payload.data ?? payload;
}

async function refreshSession() {
    const response = await fetch(document.querySelector('meta[name="session-refresh-url"]').content, {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        signal: AbortSignal.timeout(20000),
    });
    if (!response.ok) throw new Error('La sesión venció. Vuelve a iniciar sesión.');
    const { data } = await response.json();
    document.querySelector('meta[name="api-access-token"]').content = data.access_token;
}

export const getData = (resource) => requestData(resource);

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
