<?php
/** @var array|null $me */
/** @var array|null $demo  {people, places} fictícios da página de exemplo (nulo na página real) */
$demo = $demo ?? null;
?>
<main class="page page-normal" x-data='circles(<?= e(json_encode($demo, JSON_UNESCAPED_UNICODE)) ?>)'>
    <?php require __DIR__ . '/partials/crumb.php'; ?>

    <?php if ($demo !== null): ?>
    <?php ob_start(); ?> com pessoas e lugares fictícios ligados aos jogos do catálogo de exemplo.<?php $demoText = ob_get_clean(); require __DIR__ . '/partials/demo_note.php'; ?>
    <?php endif; ?>

    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="t-page">Pessoas e lugares</h1>
            <p class="page-sub " x-show="ready" x-text="data.people.length + (data.people.length === 1 ? ' pessoa' : ' pessoas') + ' · ' + data.places.length + (data.places.length === 1 ? ' lugar' : ' lugares') + ' · ' + sharedGames + ' jogos com companhia'"></p>
        </div>
        <div class="inline-flex rounded-xl bg-slate-50/5 p-1 text-sm font-semibold" role="tablist" aria-label="Pessoas ou lugares">
            <template x-for="t in tabs" :key="t.v">
                <button type="button" role="tab" :aria-selected="tab === t.v" @click="tab = t.v; open = null" class="rounded-lg px-4 py-2 transition"
                        :class="tab === t.v ? 'bg-brand-600 text-on-brand shadow' : 'text-slate-400 hover:text-slate-50'">
                    <span x-text="t.icon + ' ' + t.l"></span> <span class="text-xs opacity-70" x-text="data[t.v].length"></span>
                </button>
            </template>
        </div>
    </header>

    <div x-show="!ready" class="mt-6 h-64 animate-pulse rounded-3xl bg-slate-50/5" aria-label="Carregando"></div>
    <div x-show="error" x-cloak role="alert" class="mt-6 alert-error" x-text="error"></div>

    <!-- ===== Destaque: o parceiro de jogatina e o lugar favorito ===== -->
    <section x-show="ready && (data.people.length || data.places.length)" x-cloak class="relative isolate mt-6 overflow-hidden rounded-3xl border border-slate-50/10 bg-hero p-5 text-white sm:p-7" aria-label="Destaque">
        <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
            <img x-show="heroCover" :src="heroCover" alt="" class="h-full w-full scale-150 object-cover opacity-25 blur-3xl">
            <div class="absolute inset-0 bg-gradient-to-br from-black/75 via-black/60 to-brand-600/20"></div>
        </div>
        <div class="grid gap-6 md:grid-cols-2">
            <!-- quem -->
            <template x-if="topPerson">
                <div class="flex min-w-0 items-center gap-5">
                    <span class="grid h-24 w-24 shrink-0 place-items-center rounded-full text-3xl font-black text-white shadow-2xl ring-4 ring-white/15" :style="avatar(topPerson.name)" x-text="UI.initials(topPerson.name)"></span>
                    <div class="min-w-0">
                        <p class="kicker-hero">Seu parceiro de jogatina</p>
                        <p class="mt-1 truncate text-3xl font-black" x-text="topPerson.name"></p>
                        <p class="mt-1 text-sm text-white/75" x-text="topPerson.games.length + ' jogos juntos · ' + span(topPerson)"></p>
                        <p x-show="placesOf(topPerson)[0]" class="mt-0.5 text-sm text-white/55" x-text="placesOf(topPerson)[0] ? 'Geralmente em: ' + placesOf(topPerson)[0].name : ''"></p>
                    </div>
                </div>
            </template>
            <!-- onde -->
            <template x-if="topPlace">
                <div class="flex min-w-0 items-center gap-5">
                    <span class="grid h-24 w-24 shrink-0 place-items-center rounded-3xl bg-white/10 text-5xl shadow-2xl ring-4 ring-white/10" aria-hidden="true" x-text="placeIcon(topPlace.name)"></span>
                    <div class="min-w-0">
                        <p class="kicker-hero">Seu lugar favorito</p>
                        <p class="mt-1 truncate text-3xl font-black" x-text="topPlace.name"></p>
                        <p class="mt-1 text-sm text-white/75" x-text="topPlace.games.length + ' jogos zerados ali · ' + span(topPlace)"></p>
                        <p x-show="peopleOf(topPlace)[0]" class="mt-0.5 text-sm text-white/55" x-text="peopleOf(topPlace)[0] ? 'Quase sempre com ' + peopleOf(topPlace)[0].name : ''"></p>
                    </div>
                </div>
            </template>
        </div>
        <!-- nuvem da aba atual: tamanho = jogos -->
        <ul x-show="list.length > 1" class="mt-6 flex flex-wrap items-center justify-center gap-2 border-t border-white/10 pt-5">
            <template x-for="g in list" :key="g.name">
                <li><button type="button" @click="focus(g)" class="rounded-full px-3 py-1 font-bold text-white shadow ring-1 ring-white/15 transition hover:scale-105 motion-reduce:hover:scale-100"
                            :style="(tab === 'people' ? avatar(g.name) : 'background:rgb(255 255 255 / .1)') + ';font-size:' + bubble(g) + 'rem'" x-text="(tab === 'places' ? placeIcon(g.name) + ' ' : '') + g.name"></button></li>
            </template>
        </ul>
    </section>

    <section x-show="ready && !list.length" x-cloak class="mt-6 empty">
        <p class="t-section" x-text="tab === 'people' ? 'Nenhuma pessoa registrada ainda' : 'Nenhum lugar registrado ainda'"></p>
        <p class="mx-auto mt-1 max-w-md text-sm text-slate-400">Quando você preencher “Com quem você jogou” ou “Onde você jogou” nas memórias de um jogo, eles aparecem aqui.</p>
    </section>

    <!-- ===== Cartões ===== -->
    <ul class="mt-6 grid gap-4 md:grid-cols-2">
        <template x-for="g in list" :key="g.name">
            <li :id="'c-' + slug(g.name)" class="min-w-0 scroll-mt-6 rounded-2xl border bg-slate-900/70 p-5 transition" :class="open === g.name ? 'border-brand-400/70 ring-2 ring-brand-400/30' : 'border-slate-50/10'">
                <div class="flex items-center gap-4">
                    <span x-show="tab === 'people'" class="grid h-14 w-14 shrink-0 place-items-center rounded-full text-lg font-black text-white shadow ring-2 ring-black/30" :style="avatar(g.name)" x-text="UI.initials(g.name)"></span>
                    <span x-show="tab === 'places'" class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-slate-50/10 text-3xl" aria-hidden="true" x-text="placeIcon(g.name)"></span>
                    <div class="min-w-0 flex-1">
                        <h2 class="truncate text-lg font-bold text-slate-50" x-text="g.name"></h2>
                        <p class="text-sm text-slate-400" x-text="g.games.length + (g.games.length === 1 ? ' jogo' : ' jogos') + ' · ' + span(g)"></p>
                        <!-- o cruzamento: onde se joga com essa pessoa / com quem se joga nesse lugar -->
                        <p class="mt-1 flex flex-wrap gap-1.5 text-xs">
                            <template x-for="x in (tab === 'people' ? placesOf(g) : peopleOf(g)).slice(0, 3)" :key="x.name">
                                <span class="rounded-full bg-slate-50/10 px-2 py-0.5 text-slate-300" x-text="(tab === 'people' ? placeIcon(x.name) + ' ' : '👤 ') + x.name + ' · ' + x.n"></span>
                            </template>
                        </p>
                    </div>
                    <span class="text-right text-3xl font-black tabular-nums text-slate-50/80" x-text="g.games.length"></span>
                </div>
                <ul class="mt-4 flex gap-3 overflow-x-auto pb-2">
                    <template x-for="x in g.games" :key="x.id">
                        <li class="w-20 shrink-0">
                            <a :href="isDemo ? null : base + '/jogos/' + x.id" class="group block focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">
                                <span class="relative block aspect-[16/25] overflow-hidden rounded-md bg-black shadow ring-1 ring-slate-50/10 transition group-hover:-translate-y-0.5 motion-reduce:group-hover:translate-y-0">
                                    <img x-show="x.cover_url" :src="x.cover_url" :alt="'Capa de ' + x.title" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                                    <span x-show="!x.cover_url" class="absolute inset-0 grid place-items-center bg-gradient-to-br from-neutral to-brand-600/60 text-xl font-bold text-white/80" x-text="UI.initials(x.title)"></span>
                                </span>
                                <span class="mt-1 block truncate text-2xs font-semibold text-slate-200 group-hover:text-brand-400" :title="x.title" x-text="x.title"></span>
                                <span class="block truncate text-2xs text-slate-500" x-text="x.finished_at.slice(0, 4) + ' · ' + x.platform"></span>
                            </a>
                        </li>
                    </template>
                </ul>
            </li>
        </template>
    </ul>

    <p class="mt-8 text-center text-xs text-slate-500">Para registrar, abra um jogo, escolha <em>Editar</em> e preencha <em>Memórias</em>.</p>
</main>

<script>
function circles(demo) {
    return {
        isDemo: !!demo, base: window.APP.base,
        tabs: [{ v: 'people', l: 'Pessoas', icon: '👥' }, { v: 'places', l: 'Lugares', icon: '📍' }],
        tab: 'people', data: demo ?? { people: [], places: [] }, ready: !!demo, error: '', open: null,
        get list() { return this.data[this.tab]; },
        get topPerson() { return this.data.people[0] ?? null; },
        get topPlace() { return this.data.places[0] ?? null; },
        get heroCover() { return (this.topPerson ?? this.topPlace)?.games.find((g) => g.cover_url)?.cover_url ?? null; },
        get sharedGames() { return new Set(this.data.people.flatMap((p) => p.games.map((g) => g.id))).size; },

        /** "de 2019 a 2026" (ou "em 2025") a partir dos jogos do grupo */
        span(g) {
            const ys = g.games.map((x) => +x.finished_at.slice(0, 4));
            const a = Math.min(...ys), b = Math.max(...ys);
            return a === b ? 'em ' + a : 'de ' + a + ' a ' + b;
        },
        /** onde se jogou com uma pessoa (lugares dos mesmos jogos), do mais comum ao menos */
        placesOf(p) { return this.cross(p, this.data.places); },
        /** com quem se jogou num lugar */
        peopleOf(pl) { return this.cross(pl, this.data.people); },
        cross(g, others) {
            const ids = new Set(g.games.map((x) => x.id));
            return others.map((o) => ({ name: o.name, n: o.games.filter((x) => ids.has(x.id)).length })).filter((o) => o.n && o.name !== g.name).sort((a, b) => b.n - a.n);
        },
        /** cor de avatar estável por nome */
        avatar(name) {
            let x = 0; for (const c of String(name)) x = (x * 31 + c.charCodeAt(0)) >>> 0;
            const h = Math.round((x * 137.508) % 360); // ângulo de ouro: nomes parecidos caem em cores bem diferentes
            return `background:linear-gradient(135deg,hsl(${h} 65% 45%),hsl(${(h + 40) % 360} 70% 35%))`;
        },
        placeIcon(name) {
            const n = String(name).toLowerCase();
            const map = [[/locadora/, '📼'], [/escola|col[eé]gio|faculdade/, '🏫'], [/[ôo]nibus|viagem|carro|avi[ãa]o|metr[ôo]/, '🚌'], [/quarto|cama/, '🛏'], [/lan ?house|fliperama|arcade/, '🕹'],
                [/trabalho|escrit[óo]rio/, '💼'], [/primo|amigo|av[óo]|tio|vizinho|casa d[eoa]/, '🏡'], [/sala|casa|apartamento|sof[áa]/, '🛋'], [/praia|ch[áa]cara|s[íi]tio|f[ée]rias/, '🏖']];
            return map.find(([re]) => re.test(n))?.[1] ?? '📍';
        },
        bubble(g) { const max = this.list[0]?.games.length || 1; return (0.8 + 0.9 * Math.sqrt(g.games.length / max)).toFixed(2); },
        slug(s) { return String(s).toLowerCase().normalize('NFD').replace(/[^a-z0-9]+/g, '-'); },
        focus(g) {
            this.open = g.name;
            document.getElementById('c-' + this.slug(g.name))?.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
        },
        async init() {
            if (this.isDemo) return;
            try { this.data = (await api.get('/api/circles')).data; } catch (e) { this.error = e.message; }
            this.ready = true;
        },
    };
}
</script>
