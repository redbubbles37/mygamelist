<?php /** @var list<array> $users  usuários deste computador: username, level, avatar */ ?>
<main class="mx-auto flex min-h-screen max-w-4xl flex-col items-center justify-center px-4 py-10" x-data='userPicker(<?= e(json_encode($users, JSON_UNESCAPED_UNICODE)) ?>)'>
    <p class="text-2xl font-bold tracking-tight text-slate-50">My<span class="text-brand-400">Game</span>List</p>

    <section class="mt-10 w-full text-center" aria-labelledby="h-who">
        <h1 id="h-who" class="text-3xl font-black tracking-tight text-slate-50 sm:text-4xl">Quem vai jogar?</h1>
        <div x-show="error" x-cloak role="alert" class="alert-error mx-auto mt-6 max-w-sm" x-text="error"></div>
        <ul class="mt-10 flex flex-wrap justify-center gap-6 sm:gap-8">
            <template x-for="u in users" :key="u.username">
                <li>
                    <button type="button" :data-user="u.username" @click="enter(u)" :disabled="!!entering" class="group flex w-32 flex-col items-center gap-3 focus:outline-none disabled:cursor-wait sm:w-36" :class="entering && entering !== u.username && 'opacity-40'">
                        <span class="grid aspect-square w-full place-items-center overflow-hidden rounded-3xl text-5xl font-black shadow-xl ring-4 ring-transparent transition group-hover:scale-105 group-hover:ring-slate-50/80 group-focus-visible:scale-105 group-focus-visible:ring-brand-400 motion-reduce:group-hover:scale-100 sm:text-6xl"
                              :class="entering === u.username && 'animate-pulse !ring-brand-400'"
                              :style="u.avatar?.icon ? 'background:rgb(var(--s-50) / .1)' : UI.avatarStyle(u.username)" x-html="UI.avatarContent(u.username, u.avatar)"></span>
                        <span class="block w-full truncate text-base font-semibold text-slate-300 group-hover:text-slate-50" x-text="u.username"></span>
                        <span class="xp-ui -mt-2 text-xs text-slate-500" x-text="'Nível ' + u.level"></span>
                    </button>
                </li>
            </template>
            <li>
                <a href="<?= e(url('/cadastro')) ?>" class="group flex w-32 flex-col items-center gap-3 focus:outline-none sm:w-36">
                    <span class="grid aspect-square w-full place-items-center rounded-3xl border-4 border-dashed border-slate-50/20 text-5xl text-slate-400 transition group-hover:scale-105 group-hover:border-brand-400 group-hover:text-brand-400 group-focus-visible:border-brand-400 motion-reduce:group-hover:scale-100" aria-hidden="true">+</span>
                    <span class="text-base font-semibold text-slate-400 group-hover:text-slate-50">Novo usuário</span>
                </a>
            </li>
        </ul>
    </section>
</main>

<script>
function userPicker(users) {
    return {
        users, entering: null, error: '',
        init() {
            // o último que entrou já fica em foco: é só apertar Enter
            let last = null;
            try { last = localStorage.getItem('mgl.lastUser'); } catch { /* sem storage */ }
            this.$nextTick(() => (this.$root.querySelector('[data-user="' + CSS.escape(last || '') + '"]') ?? this.$root.querySelector('[data-user]'))?.focus());
        },
        async enter(u) {
            if (this.entering) return;
            this.entering = u.username; this.error = '';
            try {
                await api.post('/api/auth/login', { login: u.username });
                try { localStorage.setItem('mgl.lastUser', u.username); } catch { /* sem storage */ }
                location.href = window.APP.base + '/';
            } catch (err) { this.error = err.message; this.entering = null; }
        },
    };
}
</script>
