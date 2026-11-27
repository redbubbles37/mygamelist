// Cliente mínimo da API: cuida do token CSRF e normaliza erros.
(() => {
    const base = window.APP.base;
    let csrf = null;

    async function token() {
        if (!csrf) {
            const res = await fetch(base + '/api/csrf', { credentials: 'same-origin' });
            csrf = (await res.json()).csrf_token;
        }
        return csrf;
    }

    async function api(method, path, body) {
        const headers = { Accept: 'application/json' };
        const opts = { method, headers, credentials: 'same-origin' };

        if (method !== 'GET') {
            headers['X-CSRF-Token'] = await token();
        }
        if (body instanceof FormData) {
            opts.body = body;
        } else if (body !== undefined) {
            headers['Content-Type'] = 'application/json';
            opts.body = JSON.stringify(body);
        }

        let res;
        try {
            res = await fetch(base + path, opts);
        } catch {
            throw { status: 0, message: 'Sem conexão com o servidor.', errors: {} };
        }
        const data = res.status === 204 ? null : await res.json().catch(() => ({}));
        if (!res.ok) {
            if (res.status === 419) csrf = null; // sessão expirou: renova no próximo envio
            throw { status: res.status, message: data?.error || 'Erro inesperado.', errors: data?.errors || {} };
        }
        return data;
    }

    window.api = {
        get: (p) => api('GET', p),
        post: (p, b) => api('POST', p, b ?? {}),
        put: (p, b) => api('PUT', p, b ?? {}),
        del: (p) => api('DELETE', p),
    };
})();
