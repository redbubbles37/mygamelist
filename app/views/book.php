<?php
/** @var array|null $me */
/** @var bool $demo */
$demo = $demo ?? false;
?>
<main class="page page-narrow" x-data='book(<?= e(json_encode(['demo' => $demo])) ?>)'>
    <?php require __DIR__ . '/partials/crumb.php'; ?>

    <div class="no-print">
        <?php if ($demo): ?>
        <?php ob_start(); ?> com um livro montado sobre o catálogo fictício.<?php $demoText = ob_get_clean(); require __DIR__ . '/partials/demo_note.php'; ?>
        <?php endif; ?>

        <!-- Destaque: o livro em 3D e as opções de montagem -->
        <section class="hero-box p-5 text-white sm:p-8" aria-labelledby="h-book">
            <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
                <img x-show="bookCovers[0]" :src="bookCovers[0]" alt="" class="h-full w-full scale-150 object-cover opacity-25 blur-3xl">
                <div class="absolute inset-0 bg-gradient-to-br from-black/80 via-black/65 to-gold-900/30"></div>
            </div>
            <div class="grid items-center gap-8 md:grid-cols-[13rem_1fr]">
                <!-- o livro -->
                <div class="mx-auto [perspective:900px]" aria-hidden="true">
                    <div class="relative h-64 w-44 transition duration-500 [transform-style:preserve-3d] [transform:rotateY(-24deg)_rotateX(4deg)] hover:[transform:rotateY(-8deg)] motion-reduce:transition-none">
                        <!-- lombada -->
                        <div class="absolute left-0 top-0 h-full w-6 origin-left bg-gradient-to-b from-gold-950 via-gold-900 to-gold-950 [transform:rotateY(90deg)_translateX(-1.5rem)]"></div>
                        <!-- páginas -->
                        <div class="absolute right-0 top-1 h-[calc(100%-.5rem)] w-5 origin-right bg-[repeating-linear-gradient(90deg,#f5efe0_0_2px,#e2d8c0_2px_3px)] [transform:rotateY(90deg)_translateX(1.25rem)]"></div>
                        <!-- capa -->
                        <div class="absolute inset-0 overflow-hidden rounded-r-md rounded-l-sm bg-gradient-to-br from-[#3a2412] via-[#2a180b] to-[#1a0e05] shadow-[12px_18px_30px_rgba(0,0,0,.6)] ring-1 ring-gold-200/10">
                            <div class="grid grid-cols-3 gap-0.5 p-3 opacity-80">
                                <template x-for="(c, i) in bookCovers.slice(0, 6)" :key="i"><span class="block aspect-[16/25] overflow-hidden rounded-sm bg-black/40"><img :src="c" alt="" class="h-full w-full object-cover"></span></template>
                            </div>
                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-[#1a0e05] via-[#1a0e05]/95 to-transparent px-3 pb-4 pt-8 text-center">
                                <p class="text-[8px] font-semibold uppercase tracking-[.3em] text-gold-200/70">MyGameList</p>
                                <p class="mt-1 font-serif text-sm font-bold leading-tight text-gold-50">A história da minha vida gamer</p>
                                <p class="mt-1 font-serif text-2xs italic text-gold-200/70" x-text="data ? 'de ' + data.username : ''"></p>
                                <p class="mt-1 text-2xs tracking-widest text-gold-200/80" x-text="period"></p>
                            </div>
                            <div class="absolute inset-y-0 left-0 w-2 bg-gradient-to-r from-black/50 to-transparent"></div>
                        </div>
                    </div>
                </div>

                <!-- opções -->
                <div class="min-w-0">
                    <h1 id="h-book" class="t-hero">Livro da vida gamer</h1>
                    <p class="mt-1 text-sm text-white/65">Todos os seus jogos por ano, com memórias e diário, numa página feita para imprimir ou salvar em PDF (no diálogo de impressão, escolha “Salvar como PDF”).</p>
                    <div x-show="data" x-cloak>
                        <p class="mt-4 text-sm text-white/85"><strong class="text-white" x-text="nf(chosenGames)"></strong> jogos em <strong class="text-white" x-text="years.length"></strong> <span x-text="years.length === 1 ? 'capítulo' : 'capítulos'"></span><template x-if="data && data.totals.notes"><span> · <span x-text="data.totals.notes"></span> notas de diário</span></template></p>
                        <div class="mt-4 flex flex-wrap items-end gap-3">
                            <label class="text-xs text-white/60">De
                                <select x-model.number="from" class="mt-1 block rounded-lg border border-white/20 bg-black/40 px-3 py-2 text-sm text-white">
                                    <template x-for="y in yearList" :key="y"><option :value="y" x-text="y"></option></template>
                                </select></label>
                            <label class="text-xs text-white/60">Até
                                <select x-model.number="to" class="mt-1 block rounded-lg border border-white/20 bg-black/40 px-3 py-2 text-sm text-white">
                                    <template x-for="y in yearList" :key="y"><option :value="y" x-text="y"></option></template>
                                </select></label>
                        </div>
                        <fieldset class="mt-4 flex flex-wrap gap-2 text-sm">
                            <legend class="sr-only">O que incluir</legend>
                            <template x-for="o in bookOptions" :key="o.k">
                                <label class="flex cursor-pointer items-center gap-2 rounded-full border px-3 py-1.5 transition" :class="opt[o.k] ? 'border-gold-300/60 bg-gold-300/15 text-gold-50' : 'border-white/15 text-white/60 hover:text-white'">
                                    <input type="checkbox" x-model="opt[o.k]" class="sr-only"><span aria-hidden="true" x-text="opt[o.k] ? '✓' : '+'"></span><span x-text="o.l"></span>
                                </label>
                            </template>
                        </fieldset>
                        <label class="mt-4 block text-xs text-white/60">Dedicatória <span class="text-white/40">(opcional, aparece na capa)</span>
                            <input type="text" maxlength="140" x-model="dedication" @input="saveDedication()" placeholder="Ex.: Para todos que dividiram o controle comigo." class="mt-1 block w-full rounded-lg border border-white/20 bg-black/40 px-3 py-2 text-sm text-white placeholder-white/35 outline-none focus:ring-2 focus:ring-gold-300">
                        </label>
                        <div class="mt-5 flex flex-wrap items-center gap-3">
                            <div class="inline-flex rounded-lg bg-white/10 p-1 text-sm font-semibold" role="group" aria-label="Forma de leitura">
                                <button type="button" @click="setReader('flip')" :aria-pressed="reader === 'flip'" class="seg-btn" :class="reader === 'flip' ? 'bg-white text-black' : 'text-white/70 hover:text-white'">📖 Folhear</button>
                                <button type="button" @click="setReader('scroll')" :aria-pressed="reader === 'scroll'" class="seg-btn" :class="reader === 'scroll' ? 'bg-white text-black' : 'text-white/70 hover:text-white'">📜 Rolar</button>
                            </div>
                            <button type="button" @click="printBook()" class="rounded-lg bg-gold-300 px-5 py-2.5 text-sm font-bold text-gold-950 shadow-lg transition hover:bg-gold-200">🖨 Imprimir / salvar em PDF</button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <p x-show="!data && !error" class="mt-6 text-sm text-slate-400">Montando o livro…</p>
        <div x-show="error" x-cloak role="alert" class="mt-6 alert-error" x-text="error"></div>
        <p x-show="data && !data.years.length" x-cloak class="mt-6 empty">Seu livro ainda não tem capítulos. Registre alguns jogos primeiro.</p>
    </div>

    <!-- O livro (sempre em "papel", independente do tema) -->
    <div x-show="data && data.years.length" x-cloak class="book mt-8">
        <!-- Capa -->
        <section class="book-page book-cover" x-show="reader === 'scroll' || page === 0">
            <p class="book-kicker">MyGameList</p>
            <h2 class="book-title">A história da minha vida gamer</h2>
            <p class="book-by" x-text="'de ' + (data && data.username)"></p>
            <p class="book-period" x-text="period"></p>
            <dl class="book-facts">
                <div><dt>jogos zerados</dt><dd x-text="nf(chosenGames)"></dd></div>
                <div><dt>horas jogadas</dt><dd x-text="nf(chosenMinutes / 60)"></dd></div>
                <div><dt>sistemas</dt><dd x-text="chosenPlatforms"></dd></div>
            </dl>
            <p x-show="dedication.trim()" class="book-sub" style="font-style:italic;margin-top:2rem" x-text="'“' + dedication.trim() + '”'"></p>
            <p class="book-gen" x-text="'Gerado em ' + (data && UI.fmtDate(data.generated))"></p>
        </section>

        <!-- Sumário -->
        <section class="book-page" x-show="reader === 'scroll' || page === 1">
            <h2 class="book-h2">Sumário</h2>
            <ol class="book-toc">
                <template x-for="y in years" :key="y.year">
                    <li @click="reader === 'flip' && (page = years.indexOf(y) + 2)" :style="reader === 'flip' ? 'cursor:pointer' : ''"><span class="book-toc-year" x-text="y.year"></span><span class="book-toc-dots"></span><span x-text="y.games.length + (y.games.length === 1 ? ' jogo' : ' jogos') + ' · ' + nf(y.minutes / 60) + ' h'"></span></li>
                </template>
            </ol>
        </section>

        <!-- Capítulos -->
        <template x-for="(y, yi) in years" :key="y.year">
            <section class="book-page book-chapter" x-show="reader === 'scroll' || page === yi + 2">
                <p class="book-kicker" x-text="'Capítulo ' + (yi + 1)"></p>
                <h2 class="book-year" x-text="y.year"></h2>
                <p class="book-sub" style="font-style:italic" x-text="chapterTitle(y)"></p>
                <p class="book-sub" x-text="y.games.length + (y.games.length === 1 ? ' jogo zerado' : ' jogos zerados') + ' · ' + nf(y.minutes / 60) + ' horas'"></p>
                <template x-for="g in y.games" :key="g.id">
                    <article class="book-entry">
                        <div x-show="opt.covers" class="book-cover-img">
                            <img x-show="g.cover_url" :src="g.cover_url" alt="" loading="lazy">
                            <span x-show="!g.cover_url" x-text="UI.initials(g.title)"></span>
                        </div>
                        <div class="book-entry-body">
                            <h3 x-text="(isStar(y, g) ? '★ ' : '') + g.title"></h3>
                            <p class="book-meta"><span x-text="g.platform"></span> · <template x-if="g.started_at"><span>de <span x-text="UI.fmtDate(g.started_at)"></span> a </span></template><span x-text="longDate(g.finished_at)"></span> · <span x-text="UI.fmtTime(g.playtime_minutes)"></span><template x-if="opt.rating"><span> · nota <strong x-text="g.rating"></strong> · dificuldade <strong x-text="g.difficulty"></strong></span></template></p>
                            <template x-if="opt.memories && memoryLines(g).length">
                                <ul class="book-mem"><template x-for="l in memoryLines(g)" :key="l"><li x-text="l"></li></template></ul>
                            </template>
                            <template x-if="opt.notes && g.notes.length">
                                <div class="book-notes">
                                    <template x-for="n in g.notes" :key="n.note_date + n.body">
                                        <p><span class="book-note-date" x-text="UI.fmtDate(n.note_date)"></span> <span x-text="n.body"></span></p>
                                    </template>
                                </div>
                            </template>
                            <template x-if="opt.shots && g.shots?.length">
                                <div class="book-shots"><template x-for="s in g.shots" :key="s.id"><figure><img :src="s.thumb" alt="" loading="lazy"><figcaption x-show="s.caption" x-text="s.caption"></figcaption></figure></template></div>
                            </template>
                        </div>
                    </article>
                </template>
            </section>
        </template>
    </div>

    <!-- navegação do modo folhear -->
    <nav x-show="reader === 'flip' && data && data.years.length" x-cloak class="no-print sticky bottom-4 z-20 mx-auto mt-6 flex w-fit items-center gap-3 rounded-full border border-slate-50/10 bg-slate-900/95 p-1.5 shadow-2xl backdrop-blur" aria-label="Páginas do livro">
        <button type="button" @click="turn(-1)" :disabled="page === 0" class="rounded-full px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-slate-50/10 disabled:opacity-30">← Anterior</button>
        <span class="min-w-[8rem] text-center text-sm tabular-nums text-slate-400" x-text="pageLabel"></span>
        <button type="button" @click="turn(1)" :disabled="page >= pageCount - 1" class="rounded-full bg-gold-300 px-4 py-2 text-sm font-bold text-gold-950 hover:bg-gold-200 disabled:opacity-30">Próxima →</button>
    </nav>
</main>

<script>
function book(init) {
    const nf = (v) => new Intl.NumberFormat('pt-BR', { maximumFractionDigits: 0 }).format(v ?? 0);
    return {
        nf, data: null, error: '', from: 0, to: 0,
        opt: { covers: true, memories: true, notes: true, rating: true, shots: true },
        longDate(s) { return new Date(+s.slice(0, 4), +s.slice(5, 7) - 1, +s.slice(8, 10)).toLocaleDateString('pt-BR', { day: 'numeric', month: 'long', year: 'numeric' }); },
        bookOptions: [{ k: 'covers', l: 'Capas' }, { k: 'memories', l: 'Memórias' }, { k: 'notes', l: 'Diário' }, { k: 'shots', l: 'Screenshots' }, { k: 'rating', l: 'Nota e dificuldade' }],
        /** capas para o mosaico da capa do livro (as dos anos escolhidos, as mais recentes primeiro) */
        get bookCovers() { return [...this.years].reverse().flatMap((y) => y.games.map((g) => g.cover_url)).filter(Boolean); },
        get yearList() { return (this.data?.years ?? []).map((y) => y.year); },
        get years() { return (this.data?.years ?? []).filter((y) => y.year >= this.from && y.year <= this.to); },
        get chosenGames() { return this.years.reduce((s, y) => s + y.games.length, 0); },
        get chosenMinutes() { return this.years.reduce((s, y) => s + y.minutes, 0); },
        get chosenPlatforms() { return new Set(this.years.flatMap((y) => y.games.map((g) => g.platform))).size; },
        get period() { return this.from === this.to ? String(this.from) : this.from + ' – ' + this.to; },
        // ---------- leitura ----------
        reader: (() => { try { return localStorage.getItem('mgl.book.reader') || 'flip'; } catch (e) { return 'flip'; } })(),
        page: 0,
        dedication: (() => { try { return localStorage.getItem('mgl.book.dedication') || ''; } catch (e) { return ''; } })(),
        setReader(v) { this.reader = v; this.page = 0; try { localStorage.setItem('mgl.book.reader', v); } catch (e) { /* sem storage */ } },
        saveDedication() { try { localStorage.setItem('mgl.book.dedication', this.dedication); } catch (e) { /* sem storage */ } },
        get pageCount() { return this.years.length + 2; },
        get pageLabel() { return this.page === 0 ? 'Capa' : this.page === 1 ? 'Sumário' : 'Capítulo ' + (this.page - 1) + ' · ' + this.years[this.page - 2]?.year; },
        turn(d) { this.page = Math.max(0, Math.min(this.pageCount - 1, this.page + d)); window.scrollTo({ top: document.querySelector('.book')?.offsetTop - 16, behavior: 'smooth' }); },
        /** para imprimir, o livro inteiro precisa estar na página */
        printBook() {
            const prev = this.reader;
            this.reader = 'scroll';
            this.$nextTick(() => { window.print(); this.reader = prev; });
        },
        /** o título do capítulo: o gênero (ou sistema) que mais apareceu no ano */
        chapterTitle(y) {
            const top = (k) => { const c = {}; for (const g of y.games) if (g[k]) c[g[k]] = (c[g[k]] ?? 0) + 1; return Object.entries(c).sort((a, b) => b[1] - a[1])[0]; };
            const gn = top('genre'), pl = top('platform');
            if (y.games.length === 1) return 'O ano de ' + y.games[0].title;
            if (gn && gn[1] >= 2) return 'Um ano de ' + gn[0];
            return pl ? 'Um ano de ' + pl[0] : 'Um ano de jogos';
        },
        /** a estrela do capítulo: o de maior nota do ano */
        isStar(y, g) { return y.games.length > 1 && g.id === [...y.games].sort((a, b) => b.rating - a.rating)[0].id; },
        memoryLines(g) {
            const m = g.memory || {}, out = [];
            const intro = [m.intro_by && 'Apresentado por ' + m.intro_by, m.intro_where && 'Conseguido: ' + m.intro_where, m.intro_age != null && 'aos ' + m.intro_age + ' anos'].filter(Boolean);
            if (intro.length) out.push(intro.join(' · '));
            if (m.music_title) out.push('♪ ' + m.music_title);
            const play = [m.people?.length && 'Jogou com ' + m.people.join(', '), m.played_where && 'Onde: ' + m.played_where].filter(Boolean);
            if (play.length) out.push(play.join(' · '));
            return out;
        },
        async init() {
            try {
                this.data = (await api.get(init.demo ? '/api/demo/book' : '/api/book')).data;
                const ys = this.yearList;
                this.from = ys[0] ?? 0; this.to = ys[ys.length - 1] ?? 0;
            } catch (e) { this.error = e.message; }
            window.addEventListener('beforeprint', () => { this._prev = this.reader; this.reader = 'scroll'; });
            window.addEventListener('afterprint', () => { if (this._prev) this.reader = this._prev; });
            window.addEventListener('keydown', (e) => { if (this.reader === 'flip' && !/^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement?.tagName)) { if (e.key === 'ArrowRight') this.turn(1); if (e.key === 'ArrowLeft') this.turn(-1); } });
        },
    };
}
</script>
