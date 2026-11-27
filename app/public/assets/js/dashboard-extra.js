// Blocos "de história" do dashboard: estante, hoje na sua história, carta de jogador, trilha,
// pessoas, constelação, rio de gêneros e o painel montável. Mesclado ao estado Alpine de views/home.php.
window.dashExtra = function ({ me, isDemo, base, widgets }) {
    const ids = Object.keys(widgets);
    const NUMERIC = ids.filter((k) => widgets[k].numbers);
    const charts = {};

    const normalize = (l) => {
        const order = [];
        for (const id of (l?.order ?? [])) if (ids.includes(id) && !order.includes(id)) order.push(id);
        for (const id of ids) {
            if (order.includes(id)) continue;
            const prev = ids[ids.indexOf(id) - 1];
            order.splice(prev ? order.indexOf(prev) + 1 : 0, 0, id);
        }
        return { order, hidden: (l?.hidden ?? []).filter((id, i, a) => ids.includes(id) && a.indexOf(id) === i) };
    };
    const draw = (id, config) => {
        charts[id]?.destroy();
        delete charts[id];
        const el = document.getElementById(id);
        if (el && window.Chart) charts[id] = new Chart(el, config);
    };
    const day = (s) => Date.UTC(+s.slice(0, 4), +s.slice(5, 7) - 1, +s.slice(8, 10));

    return {
        // ---------- estado ----------
        extra: null, extraError: '', calm: !!me?.calm, widgets,
        layout: normalize(me?.dash), editing: false, layoutNotice: '', savingLayout: false,
        openPerson: null, todayModal: false, todayOpener: null,
        dragId: null, dragOver: null, flash: null,
        // celular: abas Agora / História / Números (no computador, tudo aparece de uma vez)
        wide: window.matchMedia('(min-width: 1024px)').matches, tab: 'now',
        tabs: [{ v: 'now', l: 'Agora' }, { v: 'story', l: 'História' }, { v: 'numbers', l: 'Números' }],
        // constelação: pedida só quando o bloco aparece na tela
        constPts: null, constSeen: false, constLoading: false,

        // ---------- painel montável ----------
        initLayout() {
            if (isDemo) { try { this.layout = normalize(JSON.parse(localStorage.getItem('mgl-dash'))); } catch { /* usa o padrão */ } }
        },
        has(id) {
            const e = this.extra;
            switch (id) {
                case 'shelf': return !!e && (e.playing.length > 0 || e.recent.length > 0);
                case 'today': return !!e && (e.on_this_day.length > 0 || !!e.memory_pick);
                case 'card': return !!e && !e.card.empty;
                case 'constellation': return !!e && e.constellation_count >= 2;
                case 'next': return !!e && (e.next?.length ?? 0) > 0;
                case 'river': return !!e && e.river.series.length > 0;
                case 'soundtrack': return !!e && e.soundtrack.length > 0;
                case 'people': return !!e && e.people.length > 0;
                case 'week': return !!e && !!e.week && e.has_games;
                default: return !!this.data;   // blocos de números dependem das estatísticas do ano
            }
        },
        vis(id) {
            return !this.layout.hidden.includes(id) && !(this.calm && NUMERIC.includes(id)) && this.has(id)
                && (this.wide || (widgets[id].group ?? 'story') === this.tab);
        },
        /** Largura do bloco na grade de 2 colunas (computador): inteiro ou metade; a carta ocupa 2 linhas. */
        cell(id) {
            const w = widgets[id];
            // min-w-0: na grade, o bloco pode encolher até a largura da tela; o bloco mexido no painel pisca
            return 'min-w-0 transition-shadow duration-300' + (w?.span === 'full' ? ' lg:col-span-2' : '') + (this.flash === id ? ' rounded-2xl ring-2 ring-brand-400 ring-offset-4 ring-offset-slate-950' : '');
        },
        /** Abas visíveis no celular: só as que têm algum bloco. */
        get tabsShown() {
            return this.tabs.filter((t) => ids.some((id) => (widgets[id].group ?? 'story') === t.v && !this.layout.hidden.includes(id) && !(this.calm && NUMERIC.includes(id)) && this.has(id)));
        },
        setTab(t) {
            this.tab = t;
            this.$nextTick(() => { this.drawAll?.(); this.paintExtra(); });
        },
        pos(id) { return this.layout.order.indexOf(id); },
        get editable() { return this.layout.order.filter((id) => !(this.calm && NUMERIC.includes(id))); },
        move(id, dir) {
            const o = [...this.layout.order], list = this.editable, i = list.indexOf(id), j = i + dir;
            if (j < 0 || j >= list.length) return;
            const a = o.indexOf(id), b = o.indexOf(list[j]);
            [o[a], o[b]] = [o[b], o[a]];
            this.layout = { ...this.layout, order: o };
            this.layoutNotice = '';
            this.flashWidget(id);
            this.$nextTick(() => this.paintExtra());
        },
        /** arrastar e soltar: o bloco arrastado vai para o lugar daquele em que foi solto */
        dropOn(target) {
            const id = this.dragId;
            this.dragId = null; this.dragOver = null;
            if (!id || id === target) return;
            const o = this.layout.order.filter((x) => x !== id);
            const at = o.indexOf(target) + (this.layout.order.indexOf(id) < this.layout.order.indexOf(target) ? 1 : 0);
            o.splice(at, 0, id);
            this.layout = { ...this.layout, order: o };
            this.layoutNotice = '';
            this.flashWidget(id);
            this.$nextTick(() => this.paintExtra());
        },
        /** destaca por um instante o bloco que acabou de ser mexido */
        flashWidget(id) {
            this.flash = id;
            setTimeout(() => { if (this.flash === id) this.flash = null; }, 1400);
        },
        toggleWidget(id) {
            const h = this.layout.hidden.includes(id) ? this.layout.hidden.filter((x) => x !== id) : [...this.layout.hidden, id];
            this.layout = { ...this.layout, hidden: h };
            this.layoutNotice = '';
            if (!h.includes(id)) this.flashWidget(id);
            setTimeout(() => this.paintExtra(), 60);
        },
        async saveLayout() {
            this.savingLayout = true;
            try {
                if (isDemo) localStorage.setItem('mgl-dash', JSON.stringify(this.layout));
                else this.layout = normalize((await api.put('/api/me/dashboard', this.layout)));
                this.layoutNotice = isDemo ? 'Salvo neste navegador (é só um exemplo).' : 'Painel salvo.';
            } catch (e) { this.layoutNotice = e.message || 'Não foi possível salvar.'; }
            this.savingLayout = false;
        },
        resetLayout() {
            this.layout = normalize(null);
            this.layoutNotice = '';
            setTimeout(() => this.paintExtra(), 60);
        },

        // ---------- carregamento ----------
        get greeting() {
            const h = new Date().getHours();
            return h < 5 ? 'Boa madrugada' : h < 12 ? 'Bom dia' : h < 18 ? 'Boa tarde' : 'Boa noite';
        },
        async loadExtra() {
            this.extraError = '';
            try {
                const { data } = await api.get((isDemo ? '/api/demo/dashboard' : '/api/dashboard') + '?year=' + this.year);
                this.extra = data;
                if (!this.openPerson) this.openPerson = data.people?.[0]?.name ?? null;
                setTimeout(() => this.paintExtra(), 80);
            } catch (e) {
                if (e.status === 401) return (location.href = base + '/login');
                this.extraError = e.message;
            }
        },
        /** Trocar o ano: só a trilha é buscada de novo. */
        async loadSoundtrack() {
            if (!this.extra) return;
            try {
                const { data } = await api.get((isDemo ? '/api/demo/dashboard/soundtrack' : '/api/dashboard/soundtrack') + '?year=' + this.year);
                this.extra.soundtrack = data;
            } catch { /* mantém a trilha anterior */ }
        },
        // ---------- jogando agora ----------
        get freeSlots() { return Math.max(0, (this.extra?.playing_max ?? 4) - (this.extra?.playing.length ?? 0)); },
        /** "Zerei": abre o cadastro já preenchido; ao salvar, o jogo sai do backlog (mesmo atalho do backlog) */
        doneUrl(g) {
            const p = new URLSearchParams({ title: g.title, from_backlog: g.id });
            if (g.platform) p.set('platform', g.platform);
            return `${base}/jogos/novo?${p}`;
        },
        // ---------- resumo da semana ----------
        weekDelta(now, prev, unit) {
            const d = now - prev;
            if (!prev && !now) return '';
            if (d === 0) return 'igual à semana anterior';
            return (d > 0 ? '+' : '−') + Math.abs(d) + unit + ' que a semana anterior';
        },
        /** Os 4 jogos de outros anos que ficam mais perto de hoje (empate: a melhor nota, depois o ano mais recente). */
        get todayTop() {
            return [...(this.extra?.on_this_day ?? [])]
                .sort((a, b) => Math.abs(a.days_diff) - Math.abs(b.days_diff) || b.rating - a.rating || a.years_ago - b.years_ago)
                .slice(0, 4);
        },
        /** Frase sob o título: quantos jogos e a que distância de hoje o mais longe deles ficou. */
        seasonNote() {
            const n = this.extra?.on_this_day.length ?? 0, far = this.extra?.season_days ?? 0, t = this.extra?.today ?? '';
            const day = t ? t.slice(8, 10) + '/' + t.slice(5, 7) : 'hoje';
            const rule = this.extra?.season_rule ?? 5, d = (x) => (x === 1 ? '1 dia' : x + ' dias');
            if (far > rule) return 'Nenhum jogo seu foi zerado até ' + d(rule) + ' antes ou depois de ' + day + '. Estes são os ' + (n === 1 ? 'mais perto' : n + ' mais perto') + ' (o mais longe, a ' + d(far) + ').';
            return (n === 1 ? 'Um jogo zerado' : n + ' jogos zerados') + ' em anos anteriores até ' + d(rule) + ' antes ou depois de ' + day + ', os mais perto da data primeiro.';
        },
        /** Todos os jogos desta época agrupados por ano, do mais recente ao mais antigo. */
        get todayGroups() {
            const by = new Map(), y = +(this.extra?.today ?? '0').slice(0, 4);
            for (const g of (this.extra?.on_this_day ?? [])) {
                if (!by.has(g.years_ago)) by.set(g.years_ago, { years: g.years_ago, year: y - g.years_ago, games: [] });
                by.get(g.years_ago).games.push(g);
            }
            return [...by.values()].sort((a, b) => a.years - b.years)
                .map((grp) => ({ ...grp, games: grp.games.sort((a, b) => b.rating - a.rating || Math.abs(a.days_diff) - Math.abs(b.days_diff)) }));
        },
        openToday() {
            this.todayOpener = document.activeElement;
            this.todayModal = true;
            this.$nextTick(() => this.$refs.todayClose?.focus());
        },
        closeToday() {
            this.todayModal = false;
            this.$nextTick(() => this.todayOpener?.focus?.());
        },
        /** Tab e Shift+Tab ficam circulando dentro da janela aberta */
        trapFocus(e, box) {
            const els = [...box.querySelectorAll('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])')].filter((el) => el.offsetParent !== null);
            if (!els.length) return;
            const first = els[0], last = els[els.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        },
        yearsAgo(n) { return n === 1 ? 'há 1 ano' : 'há ' + n + ' anos'; },
        /** Quão perto de hoje (no calendário) o jogo do hall da fama foi zerado. */
        memoryWhen(m) {
            const ago = m.years_ago > 0 ? this.yearsAgo(m.years_ago) : 'este ano';
            if (m.days_away === 0) return m.years_ago > 0 ? 'Zerado neste mesmo dia, ' + ago : 'Zerado hoje!';
            const d = m.days_away === 1 ? '1 dia' : m.days_away + ' dias';
            return m.days_away <= 15 ? `Nesta mesma época, ${ago} (${d} de diferença)` : `O mais perto de hoje: ${d} de diferença, ${ago}`;
        },
        /** "no mesmo dia", "3 dias antes", "1 dia depois" (em relação a hoje, naquele ano). */
        seasonLabel(g) {
            const d = g.days_diff ?? 0, n = Math.abs(d);
            if (d === 0) return 'no mesmo dia';
            return n + (n === 1 ? ' dia ' : ' dias ') + (d < 0 ? 'antes' : 'depois');
        },
        longDate(s) { return new Date(+s.slice(0, 4), +s.slice(5, 7) - 1, +s.slice(8, 10)).toLocaleDateString('pt-BR', { day: 'numeric', month: 'long', year: 'numeric' }); },
        monthOf(s) { return ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'][+s.slice(5, 7) - 1]; },

        // ---------- carta de jogador ----------
        attrColor(v) { return v >= 70 ? 'bg-gradient-to-r from-accent to-brand-400' : v >= 35 ? 'bg-gradient-to-r from-brand-600 to-brand-400' : 'bg-brand-600/70'; },
        async downloadCard() {
            const c = this.extra?.card;
            if (!c || c.empty) return;
            const p = Theme.palette(), W = 720, H = 1010;
            const cv = document.createElement('canvas');
            cv.width = W; cv.height = H;
            const g = cv.getContext('2d');
            const font = (w, s) => `${w} ${s}px ${p.font}`;
            const grad = g.createLinearGradient(0, 0, W, H);
            grad.addColorStop(0, p.brand); grad.addColorStop(1, p.accent);
            g.fillStyle = grad; g.fillRect(0, 0, W, H);
            g.fillStyle = p.bg; g.beginPath(); g.roundRect(14, 14, W - 28, H - 28, 26); g.fill();
            g.textBaseline = 'alphabetic';
            g.fillStyle = p.brand; g.font = font('700', 22); g.fillText('MYGAMELIST', 50, 70);
            g.fillStyle = '#fff'; g.font = font('800', 46); g.fillText(c.username, 50, 130);
            g.fillStyle = p.accent; g.font = font('700', 28); g.fillText(c.title, 50, 172);
            // capas favoritas
            const imgs = await Promise.all(c.favorites.map((f) => new Promise((res) => {
                if (!f.cover_url) return res(null);
                const im = new Image(); im.onload = () => res(im); im.onerror = () => res(null); im.src = f.cover_url;
            })));
            imgs.forEach((im, i) => {
                const x = 50 + i * 210, w = 190, h = 297;
                g.save(); g.shadowColor = 'rgba(0,0,0,.5)'; g.shadowBlur = 18;
                g.fillStyle = '#222'; g.fillRect(x, 215, w, h); g.restore();
                if (im) g.drawImage(im, x, 215, w, h);
                else { g.fillStyle = p.brand; g.globalAlpha = .5; g.fillRect(x, 215, w, h); g.globalAlpha = 1; g.fillStyle = '#fff'; g.font = font('700', 20); g.fillText((c.favorites[i].title || '').slice(0, 14), x + 12, 215 + h / 2); }
            });
            // atributos
            let y = 575;
            for (const a of c.attributes) {
                g.fillStyle = p.text; g.font = font('600', 22); g.fillText(a.k, 50, y);
                g.fillStyle = 'rgba(255,255,255,.12)'; g.beginPath(); g.roundRect(240, y - 20, 420, 22, 11); g.fill();
                g.fillStyle = p.accent; g.beginPath(); g.roundRect(240, y - 20, Math.max(22, 420 * a.v / 100), 22, 11); g.fill();
                y += 50;
            }
            // números
            const stats = [[String(c.games), 'jogos'], [String(c.hours), 'horas'], [String(c.avg_rating).replace('.', ','), 'nota média']];
            if (!this.calm) stats.push(['Nv ' + c.level, 'nível']);
            stats.forEach(([v, l], i) => {
                const x = 50 + i * (620 / stats.length);
                g.fillStyle = '#fff'; g.font = font('800', 40); g.fillText(v, x, 830);
                g.fillStyle = p.text; g.font = font('500', 18); g.fillText(l, x, 858);
            });
            // frase
            g.fillStyle = p.text; g.font = font('500', 20);
            const words = c.phrase.split(' ');
            let line = '', yy = 925;
            for (const w of words) {
                if (g.measureText(line + w).width > 620) { g.fillText(line.trim(), 50, yy); line = ''; yy += 28; }
                line += w + ' ';
            }
            g.fillText(line.trim(), 50, yy);
            cv.toBlob((blob) => {
                const a = document.createElement('a');
                a.href = URL.createObjectURL(blob); a.download = 'carta-de-jogador.png';
                a.click(); setTimeout(() => URL.revokeObjectURL(a.href), 2000);
            }, 'image/png');
        },

        // ---------- alternativas em texto para os gráficos ----------
        /** constelação: os jogos que você zerou mais tempo depois do lançamento */
        get lateList() {
            return [...(this.constPts ?? [])].map((p) => ({ ...p, lag: Math.floor(p.finish) - p.release_year }))
                .sort((a, b) => b.lag - a.lag).slice(0, 5);
        },
        get riverSummary() {
            const r = this.extra?.river;
            if (!r || !r.series.length) return '';
            const tot = r.series.map((s) => ({ name: s.name, n: s.data.reduce((a, b) => a + b, 0) })).sort((a, b) => b.n - a.n);
            return 'Gêneros ao longo do tempo (' + r.labels[0] + ' a ' + r.labels[r.labels.length - 1] + '): ' + tot.slice(0, 4).map((t) => t.name + ' com ' + t.n).join(', ') + '.';
        },

        // ---------- gráficos ----------
        paintExtra() {
            if (!this.extra) return;
            if (this.vis('constellation')) {
                if (this.constPts) this.drawConstellation();
                else if (this.constSeen || !('IntersectionObserver' in window)) this.loadConstellation();
            }
            if (this.vis('river')) this.drawRiver();
        },

        /** Busca os pontos (formato compacto: colunas fixas e a pasta das capas uma vez só) e desenha. */
        async loadConstellation() {
            if (this.constPts || this.constLoading) return;
            this.constLoading = true;
            try {
                const { data } = await api.get(isDemo ? '/api/demo/dashboard/constellation' : '/api/dashboard/constellation');
                this.constPts = data.points.map(([id, title, platform, release_year, finish, hours, rating, cover]) => ({
                    id, title, platform, release_year, finish, hours, rating,
                    cover_url: cover ? (cover.startsWith('/') || cover.includes('://') ? cover : data.cover_dir + cover) : null,
                }));
                this.$nextTick(() => this.drawConstellation());
            } catch { /* o bloco fica com o aviso de carregando; tenta de novo na próxima vez que aparecer */ }
            this.constLoading = false;
        },

        drawConstellation() {
            const pts = this.constPts, pal = Theme.palette();
            if (!pts) return;
            const rel = pts.map((p) => p.release_year), fin = pts.map((p) => p.finish);
            const xMin = Math.min(...rel) - 1, xMax = Math.max(...rel) + 1;
            const yMin = Math.floor(Math.min(...fin)) - 1, yMax = Math.ceil(Math.max(...fin)) + 1;
            const lineA = Math.max(xMin, yMin), lineB = Math.min(xMax, yMax);   // trecho em que a diagonal cabe no gráfico
            const tip = document.getElementById('const-tip');
            const external = ({ chart, tooltip }) => {
                if (!tip) return;
                if (tooltip.opacity === 0) { tip.style.opacity = '0'; return; }
                const p = tooltip.dataPoints?.[0]?.raw;
                if (!p || !p.title) { tip.style.opacity = '0'; return; }
                tip.replaceChildren();
                if (p.cover_url) { const im = document.createElement('img'); im.src = p.cover_url; im.alt = ''; im.className = 'h-16 w-10 shrink-0 rounded object-cover'; tip.append(im); }
                const box = document.createElement('div');
                const t = document.createElement('p'); t.className = 'font-bold text-slate-50'; t.textContent = p.title;
                const s = document.createElement('p'); s.className = 'text-xs text-slate-400';
                s.textContent = `${p.platform} · lançado em ${p.release_year}, zerado em ${Math.floor(p.finish)}`;
                const r = document.createElement('p'); r.className = 'text-xs text-slate-300'; r.textContent = `nota ${p.rating} · ${p.hours} h`;
                box.append(t, s, r); tip.append(box);
                tip.style.opacity = '1';
                const x = tooltip.caretX, w = chart.width;
                tip.style.left = Math.min(Math.max(8, x + 14), w - 260) + 'px';
                tip.style.top = Math.max(4, tooltip.caretY - 30) + 'px';
            };
            draw('chart-constellation', {
                type: 'bubble',
                data: {
                    datasets: [
                        { type: 'line', label: 'Zerou no ano do lançamento', data: [{ x: lineA, y: lineA }, { x: lineB, y: lineB }], borderColor: pal.grid, borderDash: [6, 6], borderWidth: 1.5, pointRadius: 0, order: 2 },
                        {
                            type: 'bubble', label: 'Jogos', order: 1,
                            data: pts.map((p) => ({ x: p.release_year, y: p.finish, r: Math.max(4, Math.min(16, 3 + Math.sqrt(p.hours) * 1.1)), ...p })),
                            backgroundColor: pts.map((p) => (UI.RATING_COLORS[p.rating] || pal.accent) + 'cc'),
                            borderColor: pal.bg, borderWidth: 1.5,
                        },
                    ],
                },
                options: {
                    responsive: true, maintainAspectRatio: false, interaction: { mode: 'nearest', intersect: true },
                    plugins: { legend: { display: false }, tooltip: { enabled: false, external } },
                    scales: {
                        x: { min: xMin, max: xMax, title: { display: true, text: 'Ano de lançamento' }, ticks: { precision: 0, callback: (v) => (Number.isInteger(v) ? v : '') } },
                        y: { min: yMin, max: yMax, title: { display: true, text: 'Quando você zerou' }, ticks: { precision: 0, stepSize: 1, callback: (v) => (Number.isInteger(v) ? v : '') } },
                    },
                },
            });
        },

        drawRiver() {
            const r = this.extra.river, pal = Theme.palette();
            draw('chart-river', {
                type: 'line',
                data: {
                    labels: r.labels,
                    datasets: r.series.map((s, i) => ({
                        label: s.name, data: s.data, fill: true, tension: 0.42, pointRadius: 0, borderWidth: 1.5,
                        backgroundColor: pal.series[i % pal.series.length] + 'cc', borderColor: pal.bg,
                    })),
                },
                options: {
                    responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, boxHeight: 12, padding: 14 } } },
                    scales: { y: { stacked: true, beginAtZero: true, ticks: { precision: 0 }, title: { display: true, text: 'Jogos zerados' } }, x: { grid: { display: false } } },
                },
            });
        },

        initExtra() {
            this.initLayout();
            window.addEventListener('themechange', () => this.paintExtra());
            // ao cruzar o tamanho de computador, as abas aparecem/somem e os gráficos são redesenhados no novo tamanho
            window.matchMedia('(min-width: 1024px)').addEventListener('change', (e) => {
                this.wide = e.matches;
                this.$nextTick(() => { this.drawAll?.(); this.paintExtra(); });
            });
            if ('IntersectionObserver' in window) {
                this.$nextTick(() => {
                    const el = this.$refs.constSection;
                    if (!el) return;
                    const io = new IntersectionObserver((entries) => {
                        if (entries.some((x) => x.isIntersecting)) { this.constSeen = true; io.disconnect(); this.paintExtra(); }
                    }, { rootMargin: '200px' });
                    io.observe(el);
                });
            }
        },
    };
};

/** Mescla os blocos novos no estado do dashboard mantendo getters como getters (Object.assign/spread os congelariam). */
window.withDashExtra = (state, ctx) => Object.defineProperties(state, Object.getOwnPropertyDescriptors(window.dashExtra(ctx)));
