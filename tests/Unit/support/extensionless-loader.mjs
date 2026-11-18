// Vite resuelve imports sin extensión ('./workspace-ui'); Node no. Este cargador añade «.js»
// solo a los imports relativos de resources/js. Se registra con module.register (Node ≥ 20.6).
const workspaceUrl = new URL('../../../resources/js/', import.meta.url).href;

export async function resolve(specifier, context, nextResolve) {
    if (context.parentURL?.startsWith(workspaceUrl) && specifier.startsWith('./') && !/\.[^/]+$/.test(specifier)) {
        return nextResolve(`${specifier}.js`, context);
    }

    return nextResolve(specifier, context);
}
