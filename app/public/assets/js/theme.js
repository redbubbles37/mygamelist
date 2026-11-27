// Motor de temas. Cada tema define poucas cores-base; a escala "slate" (fundo -> texto) é derivada delas.
// Roda de forma síncrona no <head> para aplicar o tema antes da primeira pintura (sem "piscar").
(() => {
    const THEMES = {
        arcade: {
            name: 'Arcade Neon', desc: 'O clássico: roxo e magenta', dark: true,
            bg: '#020617', fg: '#f8fafc', brand: ['#a78bfa', '#8b5cf6', '#7c3aed'], onBrand: '#ffffff',
            accent: '#c026d3', c3: '#0369a1', danger: '#e11d48', ok: '#34d399', neutral: '#1e293b', onTile: '#ffffff',
            font: 'Inter', head: 'Inter', fs: '100%', r: [0.375, 0.5, 0.75, 1],
        },
        gameboy: {
            name: 'Game Boy', desc: 'Quatro tons de verde, pixel puro', dark: true,
            bg: '#0f380f', fg: '#9bbc0f', brand: ['#9bbc0f', '#8bac0f', '#306230'], onBrand: '#d7ef9a',
            accent: '#52801a', c3: '#1f5a2a', danger: '#ff7b5c', dangerSolid: '#8a3b24', ok: '#9bbc0f', neutral: '#1c4a1c', onTile: '#d7ef9a',
            font: 'VT323', head: 'VT323', fs: '120%', r: [0, 0, 0, 0],
            fonts: 'family=VT323',
        },
        cyberpunk: {
            name: 'Cyberpunk', desc: 'Amarelo e ciano sobre o preto', dark: true,
            bg: '#0a0a0f', fg: '#e6fbff', brand: ['#fcee0a', '#f5e500', '#d9cb00'], onBrand: '#0a0a0f',
            accent: '#ff2a6d', c3: '#00a7c4', danger: '#ff3860', ok: '#39ff88', neutral: '#9a9ab0', onTile: '#0a0a0f',
            font: 'Rajdhani', head: 'Rajdhani', fs: '108%', r: [0.125, 0.125, 0.125, 0.125],
            fonts: 'family=Rajdhani:wght@400;500;600;700',
        },
        synthwave: {
            name: 'Synthwave', desc: 'Pôr do sol dos anos 80', dark: true,
            bg: '#140a28', fg: '#f6e9ff', brand: ['#ff7ac6', '#ff2e97', '#d6197a'], onBrand: '#ffffff',
            accent: '#8b5cf6', c3: '#0891b2', danger: '#ff4d6d', ok: '#2de2a6', neutral: '#2a1650', onTile: '#ffffff',
            font: 'Exo 2', head: 'Orbitron', fs: '100%', r: [0.375, 0.5, 0.75, 1],
            fonts: 'family=Exo+2:wght@400;500;600;700&family=Orbitron:wght@600;700',
        },
        horror: {
            name: 'Survival Horror', desc: 'Sombrio, sujo e sem esperança', dark: true,
            bg: '#0c0a09', fg: '#e7dccb', brand: ['#ef4444', '#dc2626', '#991b1b'], onBrand: '#f5e9d8',
            accent: '#7f1d1d', c3: '#57534e', danger: '#ef4444', ok: '#84a98c', neutral: '#1c1917', onTile: '#f5e9d8',
            font: 'Crimson Text', head: 'Special Elite', fs: '108%', r: [0.125, 0.125, 0.125, 0.125],
            fonts: 'family=Crimson+Text:wght@400;600;700&family=Special+Elite',
        },
        rpg: {
            name: 'Taverna RPG', desc: 'Pergaminho, ouro e madeira', dark: true,
            bg: '#1a120b', fg: '#f3e3c3', brand: ['#e6b422', '#d4a017', '#9a6b0b'], onBrand: '#fff7e0',
            accent: '#8b2e1f', c3: '#2f5d3a', danger: '#c0392b', ok: '#7fb069', neutral: '#2a1d12', onTile: '#fff7e0',
            font: 'Lora', head: 'Cinzel', fs: '100%', r: [0.125, 0.25, 0.25, 0.375],
            fonts: 'family=Lora:wght@400;500;600;700&family=Cinzel:wght@500;600;700',
        },
        abyss: {
            name: 'Abismo', desc: 'Azul profundo e luz turquesa', dark: true,
            bg: '#03121c', fg: '#dff7f4', brand: ['#2dd4bf', '#14b8a6', '#0f766e'], onBrand: '#ffffff',
            accent: '#0284c7', c3: '#155e75', danger: '#e11d48', ok: '#34d399', neutral: '#0c2a3a', onTile: '#ffffff',
            font: 'Inter', head: 'Inter', fs: '100%', r: [0.5, 0.75, 1, 1.25],
        },
        cozy: {
            name: 'Vale Cozy', desc: 'Aconchegante, claro e quentinho', dark: false,
            bg: '#fbf5e6', fg: '#3b2a1a', brand: ['#4d7c2a', '#5b8c3a', '#4a7a2e'], onBrand: '#ffffff',
            accent: '#b4602f', c3: '#2b6f8a', danger: '#b91c1c', ok: '#3f7d3a', neutral: '#7a5c3e', onTile: '#ffffff',
            font: 'Nunito', head: 'Nunito', fs: '100%', r: [0.5, 0.75, 1, 1.5],
            fonts: 'family=Nunito:wght@400;600;700;800',
        },
        terminal: {
            name: 'Terminal', desc: 'Verde sobre preto, estilo hacker', dark: true,
            bg: '#000000', fg: '#00ff41', brand: ['#00ff41', '#00e03a', '#00b32d'], onBrand: '#000000',
            accent: '#00a86b', c3: '#00994d', danger: '#ff3b3b', ok: '#00ff41', neutral: '#2f9e55', onTile: '#000000',
            font: 'JetBrains Mono', head: 'JetBrains Mono', fs: '94%', r: [0, 0, 0, 0],
            fonts: 'family=JetBrains+Mono:wght@400;500;700',
        },
        paper: {
            name: 'Papel', desc: 'Claro, limpo e neutro', dark: false,
            bg: '#f8fafc', fg: '#0f172a', brand: ['#4f46e5', '#4f46e5', '#4338ca'], onBrand: '#ffffff',
            accent: '#7c3aed', c3: '#0369a1', danger: '#dc2626', ok: '#15803d', neutral: '#475569', onTile: '#ffffff',
            font: 'Inter', head: 'Inter', fs: '100%', r: [0.375, 0.5, 0.75, 1],
        },

        sakura: {
            name: 'Sakura', desc: 'Rosa claro com pétalas caindo', dark: false,
            bg: '#fff5f8', fg: '#4a2437', brand: ['#be185d', '#db2777', '#be185d'], onBrand: '#ffffff',
            accent: '#7e22ce', c3: '#0f766e', danger: '#be123c', ok: '#15803d', neutral: '#7a4a5f', onTile: '#ffffff',
            font: 'Quicksand', head: 'Quicksand', fs: '102%', r: [0.5, 0.75, 1, 1.5],
            fonts: 'family=Quicksand:wght@400;500;600;700',
        },
        dungeon: {
            name: 'Masmorra', desc: 'Pedra, tochas e escuridão', dark: true,
            bg: '#12100e', fg: '#d9ceb9', brand: ['#fb923c', '#f97316', '#c2410c'], onBrand: '#fff7ed',
            accent: '#7c2d12', c3: '#3f6212', danger: '#dc2626', ok: '#84cc16', neutral: '#2a241e', onTile: '#fff7ed',
            font: 'Alegreya', head: 'Metamorphous', fs: '108%', r: [0.125, 0.125, 0.25, 0.25],
            fonts: 'family=Alegreya:wght@400;500;700&family=Metamorphous',
        },
        galaxy: {
            name: 'Galáxia', desc: 'Espaço profundo e nebulosas', dark: true,
            bg: '#050816', fg: '#e8ecff', brand: ['#a5b4fc', '#818cf8', '#4f46e5'], onBrand: '#ffffff',
            accent: '#a21caf', c3: '#0e7490', danger: '#e11d48', ok: '#34d399', neutral: '#1b1f4b', onTile: '#ffffff',
            font: 'Space Grotesk', head: 'Space Grotesk', fs: '100%', r: [0.5, 0.75, 1, 1.25],
            fonts: 'family=Space+Grotesk:wght@400;500;600;700',
        },
        wasteland: {
            name: 'Terra Devastada', desc: 'Poeira, ferrugem e sobrevivência', dark: true,
            bg: '#1c1409', fg: '#ead9ae', brand: ['#f59e0b', '#d97706', '#b45309'], onBrand: '#fff8e6',
            accent: '#9a3412', c3: '#4d7c0f', danger: '#dc2626', ok: '#a3e635', neutral: '#2e2211', onTile: '#fff8e6',
            font: 'Barlow', head: 'Black Ops One', fs: '104%', r: [0.125, 0.125, 0.125, 0.125],
            fonts: 'family=Barlow:wght@400;500;600;700&family=Black+Ops+One',
        },
        frost: {
            name: 'Nevasca', desc: 'Gelo azul com neve caindo', dark: true,
            bg: '#08131f', fg: '#e6f4ff', brand: ['#7dd3fc', '#38bdf8', '#0284c7'], onBrand: '#ffffff',
            accent: '#4f46e5', c3: '#0e7490', danger: '#e11d48', ok: '#5eead4', neutral: '#14283b', onTile: '#ffffff',
            font: 'Outfit', head: 'Outfit', fs: '100%', r: [0.5, 0.75, 1, 1.25],
            fonts: 'family=Outfit:wght@400;500;600;700',
        },
        magma: {
            name: 'Magma', desc: 'Rocha negra e brasas subindo', dark: true,
            bg: '#0d0605', fg: '#ffe8d6', brand: ['#ff8a3d', '#f2541b', '#c2330f'], onBrand: '#ffffff',
            accent: '#b91c1c', c3: '#7c2d12', danger: '#ff4d4d', ok: '#fbbf24', neutral: '#2a0f0a', onTile: '#ffffff',
            font: 'Rubik', head: 'Bebas Neue', fs: '100%', r: [0.25, 0.375, 0.5, 0.75],
            fonts: 'family=Rubik:wght@400;500;600;700&family=Bebas+Neue',
        },
        sumi: {
            name: 'Sumi-e', desc: 'Tinta e papel de arroz (claro)', dark: false,
            bg: '#f4efe6', fg: '#1c1917', brand: ['#b91c1c', '#c2262c', '#9f1c1c'], onBrand: '#ffffff',
            accent: '#44403c', c3: '#57534e', danger: '#b91c1c', ok: '#3f6212', neutral: '#57534e', onTile: '#ffffff',
            font: 'Shippori Mincho', head: 'Shippori Mincho', fs: '104%', r: [0.125, 0.125, 0.25, 0.25],
            fonts: 'family=Shippori+Mincho:wght@400;500;600;700',
        },
        candy: {
            name: 'Doce', desc: 'Menta, rosa e muita cor (claro)', dark: false,
            bg: '#f0fdfa', fg: '#134e4a', brand: ['#be185d', '#ec4899', '#db2777'], onBrand: '#ffffff',
            accent: '#7c3aed', c3: '#0d9488', danger: '#e11d48', ok: '#15803d', neutral: '#0f766e', onTile: '#ffffff',
            font: 'Fredoka', head: 'Fredoka', fs: '102%', r: [0.75, 1, 1.25, 2],
            fonts: 'family=Fredoka:wght@400;500;600;700',
        },
        contrast: {
            name: 'Alto Contraste', desc: 'Preto e branco, máxima legibilidade', dark: true,
            bg: '#000000', fg: '#ffffff', brand: ['#ffeb3b', '#ffd600', '#ffd600'], onBrand: '#000000',
            accent: '#00e5ff', c3: '#76ff03', danger: '#ff5252', ok: '#69f0ae', neutral: '#bdbdbd', onTile: '#000000',
            font: 'Atkinson Hyperlegible', head: 'Atkinson Hyperlegible', fs: '108%', r: [0.25, 0.25, 0.375, 0.5],
            fonts: 'family=Atkinson+Hyperlegible:wght@400;700',
        },
        forest: {
            name: 'Floresta', desc: 'Verde profundo e raios de luz', dark: true,
            bg: '#07140c', fg: '#e3f5df', brand: ['#86efac', '#4ade80', '#15803d'], onBrand: '#ffffff',
            accent: '#a16207', c3: '#0f766e', danger: '#e11d48', ok: '#4ade80', neutral: '#12301c', onTile: '#ffffff',
            font: 'Cabin', head: 'Philosopher', fs: '104%', r: [0.375, 0.5, 0.75, 1],
            fonts: 'family=Cabin:wght@400;500;600;700&family=Philosopher:wght@400;700',
        },
    };
    const ORDER = ['arcade', 'gameboy', 'cyberpunk', 'synthwave', 'horror', 'rpg', 'abyss', 'cozy', 'terminal', 'paper',
        'sakura', 'dungeon', 'galaxy', 'wasteland', 'frost', 'magma', 'sumi', 'candy', 'contrast', 'forest'];
    const GOLD = { 50: '255 251 235', 200: '253 230 138', 300: '252 211 77', 400: '251 191 36', 500: '245 158 11', 600: '217 119 6', 700: '180 83 9', 900: '120 53 15', 950: '69 26 3' };
    const STOPS = { 900: 0.06, 800: 0.13, 700: 0.24, 600: 0.37, 500: 0.5, 400: 0.64, 300: 0.77, 200: 0.88, 100: 0.94 };
    const KEY_T = 'mgl.theme', KEY_FX = 'mgl.fx';

    const rgb = (h) => [1, 3, 5].map((i) => parseInt(h.slice(i, i + 2), 16));
    const trip = (c) => c.map((n) => Math.round(n)).join(' ');
    const mix = (a, b, t) => a.map((n, i) => n + (b[i] - n) * t);
    const hex = (c) => '#' + c.map((n) => Math.round(n).toString(16).padStart(2, '0')).join('');

    function vars(id) {
        const t = THEMES[id], bg = rgb(t.bg), fg = rgb(t.fg), v = {};
        v['--s-950'] = trip(bg);
        v['--s-50'] = trip(fg);
        for (const [k, p] of Object.entries(STOPS)) v['--s-' + k] = trip(mix(bg, fg, p));
        v['--brand-400'] = trip(rgb(t.brand[0]));
        v['--brand-500'] = trip(rgb(t.brand[1]));
        v['--brand-600'] = trip(rgb(t.brand[2]));
        v['--on-brand'] = trip(rgb(t.onBrand));
        v['--on-tile'] = trip(rgb(t.onTile));
        for (const k of ['accent', 'c3', 'danger', 'ok', 'neutral']) v['--' + k] = trip(rgb(t[k]));
        v['--danger-solid'] = trip(rgb(t.dangerSolid || t.danger)); // fundo de tiles/botões vermelhos (precisa contrastar com o texto do tile)
        // ouro (troféus, GOTY, best of): a mesma escala em qualquer tema, definida num lugar só
        for (const [k, c] of Object.entries(GOLD)) v['--gold-' + k] = c;
        // fundo dos heróis: sempre escuro (o texto é branco), mas puxado para a cor da marca do tema
        v['--hero'] = trip(mix([12, 12, 18], rgb(t.brand[2]), t.dark ? 0.1 : 0.2));
        v['--font-sans'] = `"${t.font}", system-ui, sans-serif`;
        v['--font-head'] = `"${t.head}", system-ui, sans-serif`;
        v['--fs'] = t.fs;
        ['md', 'lg', 'xl', '2xl'].forEach((k, i) => (v['--r-' + k] = t.r[i] + 'rem'));
        return v;
    }

    // As fontes de todos os temas ficam em assets/fonts/fonts.css (hospedadas aqui). O navegador só baixa
    // os arquivos das famílias que estão em uso, então não há nada para carregar sob demanda.
    const loadFont = () => {};

    const read = (k) => { try { return localStorage.getItem(k); } catch { return null; } };
    const write = (k, v) => { try { localStorage.setItem(k, v); } catch { /* sem storage */ } };

    let current = 'arcade', fx = true;

    function apply(id, withFx = fx) {
        if (!THEMES[id]) id = 'arcade';
        const root = document.documentElement;
        for (const [k, val] of Object.entries(vars(id))) root.style.setProperty(k, val);
        root.dataset.theme = id;
        root.dataset.fx = withFx ? 'on' : 'off';
        root.style.colorScheme = THEMES[id].dark ? 'dark' : 'light';
        current = id;
        fx = withFx;
        loadFont(id);
        window.dispatchEvent(new CustomEvent('themechange', { detail: { id, fx } }));
    }

    // Escolha do usuário: salva no navegador e (se logado) na conta.
    function set(id, withFx = fx) {
        apply(id, withFx);
        write(KEY_T, current);
        write(KEY_FX, fx ? '1' : '0');
        if (window.api) window.api.put('/api/me/theme', { theme: current, fx }).catch(() => {});
    }

    const sys = window.__THEME || {};
    const light = window.matchMedia?.('(prefers-color-scheme: light)').matches;
    const chosen = sys.theme || read(KEY_T) || (light ? 'paper' : 'arcade');
    const chosenFx = sys.fx ?? (read(KEY_FX) === null ? true : read(KEY_FX) === '1');
    apply(chosen, chosenFx);
    // espelha a escolha da conta no navegador (login em outro aparelho / páginas de login)
    if (sys.theme) { write(KEY_T, current); write(KEY_FX, fx ? '1' : '0'); }

    window.Theme = {
        list: ORDER.map((id) => ({ id, name: THEMES[id].name, desc: THEMES[id].desc, dark: THEMES[id].dark })),
        get current() { return current; },
        get fx() { return fx; },
        set, apply,
        /** CSS inline para escopar um tema num elemento (cartões de pré-visualização). */
        styleText: (id) => Object.entries(vars(id)).map(([k, v]) => `${k}:${v}`).join(';'),
        loadAllFonts: () => ORDER.forEach(loadFont),
        /** Cores do tema atual em hex, para o Chart.js. */
        palette() {
            const t = THEMES[current], bg = rgb(t.bg), fg = rgb(t.fg);
            const b = rgb(t.brand[1]), a = rgb(t.accent), c = rgb(t.c3);
            const series = [b, a, c, mix(b, fg, 0.5), mix(a, fg, 0.5), mix(c, fg, 0.5), mix(b, bg, 0.45), mix(a, bg, 0.45), mix(c, bg, 0.45), mix(fg, bg, 0.5)];
            return {
                brand: hex(b), accent: hex(a), c3: hex(c), bg: hex(bg),
                text: hex(mix(bg, fg, 0.64)), grid: `rgba(${trip(fg).replace(/ /g, ',')},.1)`,
                series: series.map(hex), font: `"${t.font}", system-ui, sans-serif`,
            };
        },
    };
})();
