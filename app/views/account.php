<?php /** @var array $me */ ?>
<main class="page page-narrow" x-data="account()">
    <?php require __DIR__ . '/partials/crumb.php'; ?>

    <header>
        <h1 class="t-page">Minha conta</h1>
        <p class="page-sub ">Sua imagem, seu nome, a privacidade e a exportação de tudo o que você registrou.</p>
    </header>

    <div x-show="error" x-cloak role="alert" class="mt-6 alert-error">
        <span x-text="error"></span> <button type="button" @click="load()" class="ml-2 font-semibold underline">Tentar de novo</button>
    </div>
    <div x-show="!ov && !error" class="mt-6 animate-pulse space-y-4" aria-label="Carregando"><div class="h-40 rounded-2xl bg-slate-50/5"></div><div class="h-56 rounded-2xl bg-slate-50/5"></div></div>

    <template x-if="ov">
        <div class="mt-6 space-y-6">

            <!-- Resumo -->
            <section class="card flex flex-wrap items-center gap-4" aria-label="Resumo da conta">
                <div class="grid h-16 w-16 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-brand-600 to-accent text-2xl font-black text-on-brand" aria-hidden="true" x-html="UI.avatarContent(ov.user.username, ov.user.avatar)"></div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xl font-bold text-slate-50" x-text="ov.user.username"></p>
                    <p class="mt-0.5 text-xs text-slate-500">Nível <span x-text="ov.user.level"></span> · membro desde <span x-text="memberSince"></span></p>
                </div>
                <a :href="base + '/u/' + encodeURIComponent(ov.user.username)" class="btn-ghost">Ver meu perfil</a>
            </section>

            <!-- Imagem do usuário -->
            <section class="card" aria-labelledby="h-img">
                <h2 id="h-img" class="t-section">Sua imagem</h2>
                <p class="mt-1 text-sm text-slate-400">Aparece na tela de entrada, no menu, no perfil e no ranking.</p>
                <div class="mt-4">
                    <?php require __DIR__ . '/partials/avatar_picker.php'; ?>
                </div>
                <p x-show="av.err" x-cloak role="alert" class="err mt-3" x-text="av.err"></p>
            </section>

            <!-- Dados de acesso -->
            <section class="card" aria-labelledby="h-id">
                <h2 id="h-id" class="t-section">Nome de usuário</h2>

                <form class="mt-4 space-y-2" @submit.prevent="saveUsername" novalidate>
                    <label for="a-username" class="lbl sr-only">Nome de usuário</label>
                    <div class="flex flex-wrap gap-2">
                        <input id="a-username" type="text" maxlength="30" autocomplete="username" x-model="u.value" class="field min-w-[12rem] flex-1" :class="u.err && 'border-danger/60'">
                        <button type="submit" class="btn" :disabled="u.saving || u.value.trim() === ov.user.username" x-text="u.saving ? 'Salvando…' : 'Salvar'"></button>
                    </div>
                    <p class="text-xs text-slate-500">Muda o endereço do seu perfil (<span x-text="'/u/' + (u.value.trim() || '…')"></span>).</p>
                    <p x-show="u.err" x-cloak class="err" x-text="u.err"></p><p x-show="u.msg" x-cloak class="ok" x-text="u.msg"></p>
                </form>
            </section>

            <!-- Privacidade -->
            <section class="card flex flex-wrap items-center justify-between gap-3" aria-labelledby="h-priv">
                <div>
                    <h2 id="h-priv" class="t-section">Perfil visível para outros usuários</h2>
                    <p class="text-sm text-slate-400">Desligado, você some do ranking e ninguém abre o seu perfil.</p>
                </div>
                <button type="button" role="switch" :aria-checked="ov.user.profile_public" @click="togglePrivacy" :disabled="pv.saving"
                        class="relative h-7 w-12 shrink-0 rounded-full transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 disabled:opacity-60" :class="ov.user.profile_public ? 'bg-brand-600' : 'bg-slate-50/20'">
                    <span class="absolute top-1 h-5 w-5 rounded-full bg-slate-50 transition-all" :class="ov.user.profile_public ? 'left-6' : 'left-1'"></span>
                    <span class="sr-only" x-text="ov.user.profile_public ? 'Perfil público' : 'Perfil oculto'"></span>
                </button>
            </section>

            <!-- Seus dados -->
            <section class="card" aria-labelledby="h-data">
                <h2 id="h-data" class="t-section">Seus dados</h2>
                <p class="mt-1 text-sm text-slate-400">Baixe uma cópia do que você registrou: serve de backup e abre em qualquer planilha.</p>
                <dl class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <template x-for="c in [['Jogos zerados', ov.counts.games], ['Dropados', ov.counts.dropped], ['Franquias', ov.counts.franchises], ['Capas', ov.counts.covers]]" :key="c[0]">
                        <div class="rounded-xl border border-slate-50/10 bg-slate-950/40 p-3.5"><dt class="text-xs uppercase tracking-wider text-slate-500" x-text="c[0]"></dt><dd class="mt-0.5 text-xl font-black tabular-nums text-slate-50" x-text="c[1]"></dd></div>
                    </template>
                </dl>
                <div class="mt-4 flex flex-wrap gap-3">
                    <a :href="base + '/api/account/export?format=json'" download class="btn">Tudo (JSON)</a>
                    <a :href="base + '/api/account/export?format=csv'" download class="btn-ghost">Jogos zerados (CSV)</a>
                    <a :href="base + '/api/account/export?format=dropped-csv'" download class="btn-ghost">Dropados (CSV)</a>
                </div>
                <p class="mt-2 text-xs text-slate-500">O JSON inclui tarefas, franquias, GOTY e top 3 de cada ano. As imagens das capas não vão no arquivo.</p>
            </section>

            <!-- Zona de perigo -->
            <section class="rounded-2xl border border-danger/40 bg-danger/5 p-5 sm:p-6" aria-labelledby="h-del">
                <h2 id="h-del" class="text-lg font-bold text-danger">Excluir conta</h2>
                <p class="mt-1 text-sm text-slate-300">Apaga para sempre a sua conta, os <strong x-text="ov.counts.games"></strong> jogos zerados, os dropados, as franquias, as escolhas de GOTY e as capas enviadas. <strong>Não dá para desfazer.</strong> Baixe os seus dados antes.</p>
                <button type="button" x-show="!d.open" @click="d.open = true" class="mt-4 rounded-lg border border-danger/50 px-4 py-2.5 text-sm font-semibold text-danger transition hover:bg-danger/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-danger">Quero excluir a minha conta</button>
                <form x-show="d.open" x-cloak class="mt-4 space-y-3" @submit.prevent="deleteAccount" novalidate>
                    <div>
                        <label for="d-confirm" class="lbl">Digite o seu nome de usuário (<strong class="text-slate-100" x-text="ov.user.username"></strong>) para confirmar</label>
                        <input id="d-confirm" type="text" autocomplete="off" x-model="d.confirm" class="field" :class="d.errs.confirm && 'border-danger/60'">
                        <p x-show="d.errs.confirm" x-cloak class="err" x-text="d.errs.confirm"></p>
                    </div>
                    <p x-show="d.err" x-cloak role="alert" class="err" x-text="d.err"></p>
                    <div class="flex gap-3">
                        <button type="submit" :disabled="d.saving" class="rounded-lg bg-danger px-5 py-2.5 text-sm font-semibold text-white hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-danger disabled:opacity-60" x-text="d.saving ? 'Excluindo…' : 'Excluir para sempre'"></button>
                        <button type="button" @click="d.open = false; d.err = ''; d.errs = {}" class="btn-ghost">Cancelar</button>
                    </div>
                </form>
            </section>
        </div>
    </template>
</main>

<script>
function account() {
    const base = window.APP.base;
    const clean = () => ({ saving: false, msg: '', err: '', errs: {} });
    return {
        base, ov: null, error: '',
        u: { value: '', ...clean() },
        pv: { saving: false },
        av: { saving: false, err: '' },
        d: { open: false, confirm: '', ...clean() },

        get memberSince() { return new Date(this.ov.user.created_at.replace(' ', 'T')).toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' }); },
        async init() { await this.load(); },
        async load() {
            this.error = '';
            try {
                this.ov = await api.get('/api/account');
                this.u.value = this.ov.user.username;
            } catch (e) {
                if (e.status === 401) return (location.href = base + '/login');
                this.error = e.message;
            }
        },

        /** Executa uma ação de formulário: limpa mensagens, trata erros por campo e o 401 (sessão expirada). */
        async run(sec, fn) {
            sec.saving = true; sec.msg = ''; sec.err = ''; sec.errs = {};
            try { await fn(); }
            catch (e) {
                if (e.status === 401) return (location.href = base + '/login');
                sec.errs = e.errors || {};
                if (!Object.keys(sec.errs).length) sec.err = e.message;
            } finally { sec.saving = false; }
        },

        async saveUsername() {
            this.u.err = '';
            await this.run(this.u, async () => {
                const r = await api.put('/api/account/username', { username: this.u.value.trim() });
                this.ov.user.username = r.username; this.u.value = r.username; this.u.msg = 'Nome atualizado.';
            });
            if (this.u.errs.username) this.u.err = this.u.errs.username;
        },
        /** chamado pelo seletor de imagem: arquivo, ícone pronto ou nada (volta para a inicial) */
        async saveAvatar(choice) {
            this.av.saving = true; this.av.err = '';
            const f = new FormData();
            if (choice.file) f.append('avatar', choice.file);
            f.append('preset', choice.preset ?? '');
            try {
                const r = await api.post('/api/account/avatar', f);
                this.ov.user.avatar = r.avatar;
                if (r.avatar) window.APP.avatars[this.ov.user.username] = r.avatar; else delete window.APP.avatars[this.ov.user.username];
                location.reload();   // o menu e o resto da tela passam a mostrar a nova imagem
            } catch (e) { this.av.err = e.errors?.avatar || e.message; this.av.saving = false; }
        },

        async togglePrivacy() {
            this.pv.saving = true;
            try { this.ov.user.profile_public = (await api.put('/api/me/privacy', { profile_public: !this.ov.user.profile_public })).profile_public; }
            catch (e) { this.error = e.message; } finally { this.pv.saving = false; }
        },
        async deleteAccount() {
            const d = this.d;
            await this.run(d, async () => {
                await api.post('/api/account/delete', { confirm: d.confirm });
                location.href = base + '/cadastro';
            });
        },
    };
}
</script>
