// Helpers de apresentação compartilhados entre as telas.

/** Nomes (sem acento, espaço ou pontuação) que apontam para o mesmo console. */
const BAND_ALIASES = {
    playstation: 'ps1', ps1: 'ps1', psone: 'ps1', psx: 'ps1', playstation1: 'ps1',
    playstation2: 'ps2', ps2: 'ps2', playstation3: 'ps3', ps3: 'ps3', playstation4: 'ps4', ps4: 'ps4',
    playstation5: 'ps5', ps5: 'ps5', playstationportable: 'psp', psp: 'psp',
    xbox: 'xbox', xbox360: 'x360', xboxseries: 'xseries', xboxseriesx: 'xseries', xboxseriess: 'xseries', xboxseriesxs: 'xseries',
    nintendo: 'nes', nes: 'nes', nintendoentertainmentsystem: 'nes', famicom: 'nes',
    supernintendo: 'snes', snes: 'snes', superfamicom: 'snes', nintendo64: 'n64', n64: 'n64',
    nintendogamecube: 'gc', gamecube: 'gc', wii: 'wii', nintendowii: 'wii', switch: 'switch', nintendoswitch: 'switch',
    nintendods: 'ds', ds: 'ds', nintendo3ds: '3ds', '3ds': '3ds',
    gameboy: 'gb', gb: 'gb', gameboycolor: 'gbc', gbc: 'gbc', gameboyadvance: 'gba', gba: 'gba',
    mastersystem: 'sms', segamastersystem: 'sms', segagenesis: 'genesis', genesis: 'genesis', megadrive: 'genesis', segamegadrive: 'genesis',
    segacd: 'segacd', megacd: 'segacd', segasaturn: 'saturn', saturn: 'saturn', segadreamcast: 'dreamcast', dreamcast: 'dreamcast',
    pc: 'pc', windows: 'pc', arcade: 'arcade', atari2600: 'atari', atari: 'atari',
    wonderswan: 'wswan', wonderswancolor: 'wswan', wonderswancrystal: 'wswan',
};
/** c = cor base (vira um leve degradê) · bg = fundo pronto · fg = texto · led = pontinho · line = fio de 2px embaixo */
const BAND_COLORS = {
    ps1: { c: '#d1d5db', fg: '#1f2937', led: '#6b7280' },
    ps2: { c: '#0b1f5c' },
    ps3: { bg: 'linear-gradient(#2a2a30,#0c0c0e)', line: '#9ca3af,#f4f4f5,#9ca3af' },
    ps4: { c: '#1d4ed8' },
    ps5: { c: '#f4f4f5', fg: '#111827', led: '#1d4ed8' },
    psp: { c: '#52525b' },
    xbox: { c: '#14532d', led: '#4ade80' },
    x360: { c: '#4d7c0f', led: '#d9f99d' },
    xseries: { c: '#166534', led: '#4ade80' },
    nes: { c: '#78716c', led: '#ef4444' },
    snes: { c: '#a78bfa', fg: '#1e1b4b', led: '#4c1d95' },
    n64: { bg: 'linear-gradient(90deg,#b91c1c,#15803d 33%,#1d4ed8 66%,#a16207)' },
    gc: { c: '#581c87', led: '#c4b5fd' },
    wii: { c: '#f8fafc', fg: '#0284c7', led: '#38bdf8' },
    switch: { c: '#e60012' },
    ds: { c: '#64748b' },
    '3ds': { c: '#881337', led: '#fda4af' },
    gb: { c: '#9bbc0f', fg: '#0f380f', led: '#306230' },
    gbc: { c: '#eab308', fg: '#422006', led: '#7c2d12' },
    gba: { c: '#6366f1' },
    sms: { bg: 'linear-gradient(#26262b,#0a0a0c)', line: '#dc2626,#dc2626', led: '#ef4444' },
    genesis: { bg: 'linear-gradient(#26262b,#0a0a0c)', fg: '#fbbf24', led: '#fbbf24' },
    segacd: { c: '#0c4a6e', led: '#7dd3fc' },
    saturn: { c: '#4b5563', led: '#fb923c' },
    dreamcast: { c: '#f5f5f4', fg: '#ea580c', led: '#ea580c' },
    pc: { bg: 'linear-gradient(#26262b,#0a0a0c)', line: '#ef4444,#22c55e,#3b82f6', led: '#22c55e' },
    arcade: { bg: 'linear-gradient(90deg,#db2777,#7c3aed)', led: '#fde047' },
    atari: { c: '#7c4a1e', line: '#f97316,#f97316', led: '#fb923c' },
    wswan: { c: '#0891b2' },
};

window.UI = {
    /** Matiz estável (0-359) a partir de um texto, para colorir a lombada de cada sistema. */
    hue(text) {
        let h = 0;
        for (const c of String(text)) h = (h * 31 + c.charCodeAt(0)) % 360;
        return h;
    },
    spineStyle(platform) {
        const h = this.hue(platform);
        return `background:linear-gradient(90deg,hsl(${h} 48% 24%),hsl(${h} 55% 36%) 45%,hsl(${h} 48% 22%))`;
    },
    /** Fundo de avatar estável por nome (ângulo de ouro: nomes parecidos caem em cores bem diferentes). */
    /**
     * O que vai dentro do círculo/quadrado de um usuário: a foto, o ícone escolhido ou a inicial.
     * $avatar é {img} ou {icon}; sem ele, procura pelo nome em APP.avatars. Use com x-html.
     */
    avatarContent(name, avatar) {
        const a = avatar !== undefined ? avatar : window.APP.avatars?.[name];
        const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        if (a?.img) return '<img src="' + esc(a.img) + '" alt="" class="h-full w-full object-cover" style="border-radius:inherit">';
        if (a?.icon) return '<span aria-hidden="true">' + esc(a.icon) + '</span>';
        return esc(String(name ?? '?').charAt(0).toUpperCase());
    },
    avatarStyle(name) {
        let x = 0; for (const c of String(name ?? '')) x = (x * 31 + c.charCodeAt(0)) >>> 0;
        const h = Math.round((x * 137.508) % 360);
        return `background:linear-gradient(135deg,hsl(${h} 65% 45%),hsl(${(h + 40) % 360} 70% 35%));color:#fff`;
    },
    /**
     * Enquadramento de uma imagem com object-cover: ponto focal p.x/p.y (%) e zoom p.z (1–3) em direção a esse ponto.
     * Usado no card e no banner da franquia (cada um com o seu enquadramento).
     */
    focusStyle(p) {
        if (!p) return '';
        const x = p.x ?? 50, y = p.y ?? 50, z = p.z ?? 1;
        return `object-position:${x}% ${y}%;transform-origin:${x}% ${y}%;transform:scale(${z})`;
    },
    /**
     * Cor da faixa do estojo conforme o sistema. Nomes diferentes do mesmo console (PS1 / Playstation, SNES / Super Nintendo…)
     * caem na mesma cor; sistemas que não estão na lista ganham uma cor estável calculada a partir do nome.
     * Devolve variáveis CSS lidas por .case-band (fundo, texto, pontinho e fio de baixo).
     */
    bandStyle(platform) {
        if (!platform) return '';
        const key = String(platform).normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/[^a-z0-9]/g, '');
        const id = BAND_ALIASES[key] ?? key;
        const b = BAND_COLORS[id];
        if (!b) {
            const h = this.hue(platform);
            return `--band-bg:linear-gradient(hsl(${h} 42% 30%),hsl(${h} 48% 18%));--band-fg:#f4f4f7;--band-led:hsl(${h} 80% 62%)`;
        }
        const solid = (c) => `linear-gradient(color-mix(in srgb,${c} 88%,white),${c} 55%,color-mix(in srgb,${c} 82%,black))`;
        const bg = b.line
            ? `linear-gradient(90deg,${b.line}) bottom/100% 2px no-repeat,${b.bg ?? solid(b.c)}`
            : (b.bg ?? solid(b.c));
        return `--band-bg:${bg};--band-fg:${b.fg ?? '#f4f4f7'};--band-led:${b.led ?? (b.fg ?? '#f4f4f7')}`;
    },
    fmtDate(s) {
        const [y, m, d] = s.split('-').map(Number);
        return new Date(y, m - 1, d).toLocaleDateString('pt-BR');
    },
    fmtTime(min) {
        const h = Math.floor(min / 60), m = min % 60;
        return h ? (m ? `${h}h ${m}min` : `${h}h`) : `${m}min`;
    },
    initials(title) {
        return title.split(/\s+/).filter(Boolean).slice(0, 2).map((w) => w[0]).join('').toUpperCase();
    },
    /** Classes do selo de dificuldade (C mais fácil -> AAA mais difícil). */
    diffClass(d) {
        return {
            C: 'bg-slate-50/15 text-slate-200',
            B: 'bg-ok/20 text-ok',
            A: 'bg-brand-500/25 text-brand-400',
            AA: 'bg-accent/30 text-slate-50',
            AAA: 'bg-gradient-to-r from-danger to-accent text-white',
        }[d];
    },
    /** Selo de dificuldade com cor fixa (legível sobre qualquer capa): C cinza, B verde, A azul, AA roxo, AAA vermelho. */
    diffStyle(d) {
        const bg = { C: '#64748b', B: '#16a34a', A: '#2563eb', AA: '#9333ea', AAA: 'linear-gradient(135deg,#dc2626,#7f1d1d)' }[d];
        return `background:${bg};color:#fff`;
    },
    /**
     * Cor de cada nota (1 a 11) - fixa em todos os temas, para a nota significar sempre a mesma coisa:
     * vermelho (ruim) -> amarelo (médio) -> verde (bom) -> ciano (ótimo) -> dourado (11, lendário).
     */
    RATING_COLORS: { 1: '#dc2626', 2: '#ea580c', 3: '#f97316', 4: '#f59e0b', 5: '#eab308', 6: '#a3e635', 7: '#4ade80', 8: '#22c55e', 9: '#10b981', 10: '#06b6d4' },
    ratingStyle(n) {
        if (n === 11) return 'color:#3b1d00'; // o fundo dourado animado vem da classe rating-gold
        const light = n >= 4 && n <= 7;
        return `background:${this.RATING_COLORS[n]};color:${light ? '#1a1300' : '#fff'}`;
    },
    /** Chance de voltar a jogar um dropado: cores fixas (verde = alta, âmbar = média, vermelho = baixa). */
    CHANCE: {
        high: { label: 'Alta', bg: '#15803d', ink: '#ffffff' },
        medium: { label: 'Média', bg: '#f59e0b', ink: '#1a1300' },
        low: { label: 'Baixa', bg: '#dc2626', ink: '#ffffff' },
    },
    /** Prioridade de um jogo na fila do backlog. */
    PRIORITY: {
        high: { label: 'Alta', bg: '#7c3aed', ink: '#ffffff' },
        medium: { label: 'Média', bg: '#0e7490', ink: '#ffffff' },
        low: { label: 'Baixa', bg: '#64748b', ink: '#ffffff' },
    },
    priorityLabel(p) { return this.PRIORITY[p]?.label ?? ''; },
    priorityStyle(p) { const x = this.PRIORITY[p]; return x ? `background:${x.bg};color:${x.ink}` : ''; },
    chanceLabel(c) { return this.CHANCE[c]?.label ?? ''; },
    chanceStyle(c) { const x = this.CHANCE[c]; return x ? `background:${x.bg};color:${x.ink}` : ''; },
    ratingClass(n) {
        return n === 11 ? 'rating-gold' : '';
    },
};

/**
 * Modo apresentar: tela cheia sem menu, faixa de página e botão flutuante, para mostrar uma análise aos amigos.
 * Sai com Esc (ou pelo botão que aparece no canto).
 */
window.Present = (() => {
    let pill = null;
    const html = document.documentElement;
    const off = () => { html.removeAttribute('data-present'); pill?.remove(); pill = null; };
    const on = () => {
        html.setAttribute('data-present', '');
        pill = document.createElement('button');
        pill.type = 'button';
        pill.textContent = 'Sair da apresentação (Esc)';
        pill.className = 'btn-ghost btn-sm fixed right-4 top-4 z-viewer bg-slate-950/80 backdrop-blur';
        pill.addEventListener('click', toggle);
        document.body.appendChild(pill);
        pill.focus();
    };
    function toggle() {
        if (html.hasAttribute('data-present')) { off(); if (document.fullscreenElement) document.exitFullscreen?.(); return; }
        on();
        try { html.requestFullscreen?.().catch(() => {}); } catch { /* sem tela cheia: segue só com o visual limpo */ }
    }
    document.addEventListener('fullscreenchange', () => { if (!document.fullscreenElement && html.hasAttribute('data-present')) off(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && html.hasAttribute('data-present') && !document.fullscreenElement) off(); });
    return { toggle, get active() { return html.hasAttribute('data-present'); } };
})();

/**
 * Cartão para compartilhar (PNG gerado aqui, sem enviar nada a lugar nenhum). O mesmo visual em todas as análises.
 * ShareCard.download({ kicker, title, sub, stats: [[valor, rótulo], ...até 4], file })
 */
window.ShareCard = {
    download({ kicker = '', title = '', sub = '', stats = [], file = 'mygamelist' }) {
        const p = Theme.palette(), W = 1080, H = 1350, cv = document.createElement('canvas');
        cv.width = W; cv.height = H;
        const c = cv.getContext('2d'), font = (w, s) => `${w} ${s}px ${p.font}`;
        const g = c.createLinearGradient(0, 0, W, H);
        g.addColorStop(0, '#0c0c12'); g.addColorStop(.55, p.brand + '66'); g.addColorStop(1, p.accent + '88');
        c.fillStyle = '#0c0c12'; c.fillRect(0, 0, W, H); c.fillStyle = g; c.fillRect(0, 0, W, H);
        c.fillStyle = 'rgba(255,255,255,.65)'; c.font = font('700', 30); c.fillText(kicker.toUpperCase(), 80, 150);
        c.fillStyle = '#fff'; c.font = font('900', 96);
        const wrap = (text, max) => { const out = []; let line = ''; for (const w of String(text).split(' ')) { if (c.measureText(line + w).width > max && line) { out.push(line.trim()); line = ''; } line += w + ' '; } out.push(line.trim()); return out; };
        let y = 270;
        for (const l of wrap(title, W - 160).slice(0, 3)) { c.fillText(l, 80, y); y += 108; }
        c.fillStyle = 'rgba(255,255,255,.8)'; c.font = font('500', 40);
        for (const l of wrap(sub, W - 160).slice(0, 4)) { c.fillText(l, 80, y + 30); y += 56; }
        const top = Math.max(y + 90, 620), cell = (W - 160 - 40) / 2;
        stats.slice(0, 4).forEach(([v, k], i) => {
            const x = 80 + (i % 2) * (cell + 40), yy = top + Math.floor(i / 2) * 270;
            c.fillStyle = 'rgba(255,255,255,.08)'; c.beginPath(); c.roundRect(x, yy, cell, 230, 28); c.fill();
            c.fillStyle = '#fff'; c.font = font('900', 78); c.fillText(String(v).slice(0, 12), x + 36, yy + 112);
            c.fillStyle = 'rgba(255,255,255,.6)'; c.font = font('600', 30); c.fillText(String(k), x + 36, yy + 172);
        });
        c.fillStyle = 'rgba(255,255,255,.45)'; c.font = font('700', 26); c.textAlign = 'center'; c.fillText('MYGAMELIST', W / 2, H - 56);
        cv.toBlob((b) => { const a = document.createElement('a'); a.href = URL.createObjectURL(b); a.download = file + '.png'; a.click(); setTimeout(() => URL.revokeObjectURL(a.href), 2000); }, 'image/png');
    },
};