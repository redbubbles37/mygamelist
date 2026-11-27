<?php
/** @var bool $hasUsers  já existe algum usuário neste computador */
/** @var list<string> $presets */
?>
<main class="mx-auto flex min-h-screen max-w-lg flex-col justify-center px-4 py-10" x-data="newUser()">
    <p class="text-center text-2xl font-bold tracking-tight text-slate-50">My<span class="text-brand-400">Game</span>List</p>
    <?php if (!$hasUsers): ?>
    <p x-show="step < 3" class="mt-2 text-center text-sm text-slate-400">Bem-vindo! O banco de dados dos jogos que você zerou. Vamos criar o primeiro usuário.</p>
    <?php endif; ?>

    <div class="panel mt-6 p-6 shadow-2xl shadow-black/40 backdrop-blur sm:p-8">
        <!-- passos -->
        <ol x-show="step < 3" class="mb-6 flex gap-2" aria-label="Etapas">
            <template x-for="(label, i) in ['Nome', 'Imagem']" :key="label">
                <li class="flex-1" :aria-current="step === i + 1 ? 'step' : null">
                    <span class="block h-1.5 rounded-full transition-colors" :class="step > i ? 'bg-brand-500' : 'bg-slate-50/10'"></span>
                    <span class="mt-1 block text-xs font-semibold" :class="step === i + 1 ? 'text-brand-400' : 'text-slate-500'" x-text="label"></span>
                </li>
            </template>
        </ol>
        <div x-show="error" x-cloak role="alert" class="alert-error mb-4" x-text="error"></div>

        <!-- 1) nome -->
        <form x-show="step === 1" @submit.prevent="toImage" novalidate>
            <h1 class="t-section">Como você quer ser chamado?</h1>
            <p class="mt-1 text-sm text-slate-400">Aparece embaixo da sua imagem na tela de entrada.</p>
            <label for="username" class="sr-only">Nome de usuário</label>
            <input id="username" x-ref="username" type="text" autocomplete="username" maxlength="30" x-model="form.username" class="field mt-4 w-full text-lg" placeholder="ex.: player_one">
            <p class="caption mt-1">3 a 30 caracteres: letras, números e _</p>
            <button type="submit" class="btn btn-lg mt-5 w-full">Continuar</button>
        </form>

        <!-- 2) imagem -->
        <div x-show="step === 2" x-cloak>
            <h1 class="t-section">Escolha a sua imagem</h1>
            <p class="mt-1 text-sm text-slate-400">Aparece na tela de entrada. Dá para trocar depois em Minha conta.</p>
            <div class="mt-4 flex justify-center">
                <span class="grid h-32 w-32 place-items-center overflow-hidden rounded-3xl text-6xl font-black shadow-xl"
                      :style="preview?.icon ? 'background:rgb(var(--s-50) / .1)' : UI.avatarStyle(form.username)" x-html="UI.avatarContent(form.username, preview)"></span>
            </div>
            <div class="mt-5">
                <?php require __DIR__ . '/partials/avatar_picker.php'; ?>
            </div>
            <div class="mt-6 flex gap-3">
                <button type="button" @click="go(1)" class="btn-ghost btn-lg">Voltar</button>
                <button type="button" @click="create()" :disabled="loading" class="btn btn-lg flex-1" x-text="loading ? 'Criando…' : 'Criar usuário'"></button>
            </div>
        </div>

        <!-- 3) pronto: o tour é opcional -->
        <div x-show="step === 3" x-cloak class="text-center">
            <span class="mx-auto grid h-28 w-28 place-items-center overflow-hidden rounded-3xl text-5xl font-black shadow-xl"
                  :style="preview?.icon ? 'background:rgb(var(--s-50) / .1)' : UI.avatarStyle(form.username)" x-html="UI.avatarContent(form.username, preview)"></span>
            <h1 class="mt-4 text-2xl font-black text-slate-50">Tudo pronto, <span x-text="form.username"></span>!</h1>
            <p class="mt-2 text-sm text-slate-400">Quer um tour rápido mostrando como usar o programa? São 8 telas, e você pode sair quando quiser.</p>
            <div class="mt-6 space-y-3">
                <a x-ref="tour" href="<?= e(url('/tutorial')) ?>" class="btn btn-lg w-full">Sim, me ensine a usar</a>
                <a href="<?= e(url('/')) ?>" class="btn-ghost btn-lg w-full">Pular e ir para o Painel</a>
            </div>
            <p class="caption mt-4">O tour fica no menu, em <strong>Como usar</strong>, para ver quando quiser.</p>
        </div>
    </div>

    <?php if ($hasUsers): ?>
    <a x-show="step < 3" href="<?= e(url('/login')) ?>" class="back-link mx-auto mt-5">← Voltar para os usuários</a>
    <?php endif; ?>
</main>

<script>
function newUser() {
    return {
        step: 1, loading: false, error: '',
        form: { username: '' },
        preview: null,           // {icon} ou {img} mostrado na tela
        choice: {},              // o que será enviado: {preset} ou {file}

        init() { this.$nextTick(() => this.$refs.username.focus()); },
        go(n) {
            this.step = n; this.error = '';
            this.$nextTick(() => ({ 1: this.$refs.username, 3: this.$refs.tour }[n])?.focus());
        },
        toImage() {
            this.form.username = this.form.username.trim();
            if (!/^[A-Za-z0-9_]{3,30}$/.test(this.form.username)) { this.error = 'Use de 3 a 30 caracteres: letras, números e _.'; return; }
            this.go(2);
        },
        /** chamado pelo seletor de imagem (aqui só guarda a escolha; grava junto com a conta) */
        saveAvatar({ file, preset }) {
            this.error = '';
            if (this.preview?.img) URL.revokeObjectURL(this.preview.img);
            if (file) {
                if (file.size > 8 * 1024 * 1024) { this.error = 'A imagem passa de 8 MB.'; return; }
                this.choice = { file }; this.preview = { img: URL.createObjectURL(file) };
            } else {
                this.choice = preset ? { preset } : {};
                this.preview = preset ? { icon: preset } : null;
            }
        },
        async create() {
            this.loading = true; this.error = '';
            const f = new FormData();
            f.append('username', this.form.username);
            if (this.choice.file) f.append('avatar', this.choice.file);
            if (this.choice.preset) f.append('preset', this.choice.preset);
            try {
                await api.post('/api/auth/register', f);
                try { localStorage.setItem('mgl.lastUser', this.form.username); } catch { /* sem storage */ }
                this.go(3);
            } catch (e) {
                const errs = e.errors || {};
                if (errs.username) this.go(1);
                this.error = errs.username || errs.avatar || e.message;
            } finally { this.loading = false; }
        },
    };
}
</script>
