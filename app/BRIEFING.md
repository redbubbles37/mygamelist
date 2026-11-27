# BRIEFING — MyGameList

## 1. Visão geral
Catálogo multiusuário de jogos finalizados, com gamificação por XP e níveis (1–100).
Escopo pessoal / entre amigos. Dados inseridos manualmente (sem IGDB por enquanto).
**Fase 1:** backend PHP + MySQL (API JSON). Front (Tailwind + Alpine) vem depois.

## 2. Decisões fechadas
- **Multiusuário**: cada usuário tem a sua própria lista de jogos.
- **Sem rejogatinas**: o mesmo jogo só pode ser registrado uma vez por usuário **em cada sistema**
  (ex.: RE4 de GameCube e RE4 de Wii são registros válidos; RE4 de GameCube duas vezes não).
  Regra no banco: UNIQUE (user_id, título normalizado, sistema, ano de lançamento) — o mesmo título pode repetir no mesmo sistema
  se o ano de lançamento for outro (ex.: Doom 1993 e Doom 2016 no PC). Objetivo: zerar o máximo de jogos diferentes.
- **Texto livre**: título, gênero, sistema, desenvolvedora, publisher e tarefas são digitados pelo usuário.
  Internamente são normalizados (find-or-create por nome em minúsculas/sem espaços extras) para permitir estatísticas e autocomplete.
- **Dificuldade (C, B, A, AA, AAA)**: é a percebida pelo jogador, definida por registro.
- **Capa**: o usuário pode enviar imagem (upload).
- **Stack**: HTML, CSS, JS, Tailwind, Alpine.js, PHP **puro e organizado** (Composer, PDO, router próprio, MVC enxuto), MySQL.

## 3. Campos do registro de jogo
título, data de finalização, tempo de jogo (minutos), gênero (um só), sistema, ano de lançamento, desenvolvedora, publisher,
nota (1–11), dificuldade (C/B/A/AA/AAA), capa (opcional), tarefas extras (texto livre + tier 1–3).

## 4. XP e níveis (valores iniciais, ajustáveis em tabela `settings`)
- XP base por jogo: 100
- Dificuldade: C=0, B=15, A=40, AA=80, AAA=150
- Tarefa extra: Tier 1=25, Tier 2=60, Tier 3=120
- Média ≈ 150 XP/jogo → nível 100 ≈ 300.000 XP (~2000 jogos)
- Curva: `XP_acumulado(n) = 300000 × ((n-1)/99)^1,8`
- Extrato `xp_events` (origem, valor, data) em vez de só um total → permite recalcular e auditar.
- Cada evento pertence a um jogo: ao editar, os eventos do jogo são refeitos; ao excluir, somem em cascata.
  O total do usuário (`users.total_xp`) é sempre recalculado como `SUM(xp_events)`, então não dessincroniza.
- Mudou valores em `settings`? Rode `php bin/recalc_xp.php`.

## 5. Modelagem (rascunho)
`users`, `games` (por usuário; UNIQUE user_id + título normalizado + platform_id + release_year; guarda genre_id e release_year), `genres`, `platforms`, `companies`,
`game_tasks` (texto livre + tier), `xp_events`, `settings`.
Desenvolvedora e publisher compartilham `companies`.

## 6. Upload de capa (segurança)
- Aceitar JPEG/PNG/WebP, máx. ~2 MB, validar o tipo real com `finfo` (não confiar na extensão).
- Reprocessar com GD (redimensionar, remover EXIF, salvar em WebP/JPEG) e gerar nome aleatório.
- Salvar em `public/uploads/covers/` com `.htaccess` bloqueando execução de PHP.
- Apagar o arquivo antigo ao trocar/excluir.

## 7. Estrutura de pastas prevista
```
mygamelist/
  public/            index.php, .htaccess, uploads/, assets/
  src/               Core/ (Router, Database, Request, Response, Auth, Csrf)
                     Controllers/  Models/  Services/ (XpService, UploadService)
  config/            config.php (+ .env)
  database/          migrations/*.sql  seeds/
  routes/            api.php
```

## 8. Segurança base
`password_hash` (Argon2id), sessões com cookie HttpOnly/SameSite, CSRF token, PDO com prepared statements,
validação server-side, rate limit simples no login.

## 9. Fases
1. Schema + migrations, auth, CRUD de jogos (com tarefas e capa), motor de XP/nível, API JSON.
2. Front com Tailwind + Alpine (lista, filtros, formulário, dashboard, barra de XP).
3. Páginas "navegar por" (sistemas, gêneros, dificuldades, ano de lançamento, desenvolvedoras, publishers), temas e catálogo estilo estojo de PS2 (feito).
4. Franquias (feito): coleções manuais organizadas em seções (Principal, Remakes, Spin-offs...). Um item conta como feito quando o catálogo do usuário
   tem um jogo com o mesmo título (e, se o item informar, o mesmo ano de lançamento; o sistema não conta) ou quando há vínculo manual. Tabelas: `franchises`, `franchise_sections`, `franchise_entries`.
5. Perfil público e ranking entre amigos (feito).
6. GOTY (feito): o usuário escolhe o melhor jogo de cada ANO DE LANÇAMENTO; o ano só aparece com 5+ jogos zerados lançados nele. Tabela `goty_picks`.

7. Backlog (quero jogar / jogando agora), Metas do ano e Jogos dropados (com "voltei a jogar") (feito). Tabelas: `backlog_games`, `year_goals`, `dropped_games`.
8. Importação de jogos (feito): `/importar` (exemplo em `/importar/exemplo`). CSV (`;` `,` tab `|`, UTF-8 ou Windows-1252), XLSX (leitor próprio `XlsxReader`, sem extensão zip) ou JSON de backup.
   Fluxo: arquivo -> associar colunas (sugestão automática por nome) + valores padrão -> conferência linha a linha (prontas / repetidas / com erro) -> gravação em lotes de 100.
   Repetido = mesmo título + sistema + ano (no catálogo ou na própria planilha). Datas ISO, dd/mm/aaaa ou número do Excel; tempo `8h30`, `8:30`, `12,5` (h ou min, escolha do usuário) ou minutos.
   Limites: 5 MB, 2000 linhas. O CSV exportado em Minha conta pode ser importado de volta. Modelo em `/api/import/template`.

9. Estatísticas (feito): `/estatisticas` (exemplo em `/estatisticas/exemplo`). `StatsService::compute()` recebe a lista de jogos e devolve totais, recordes, dia da semana, distribuição de notas,
   dificuldade, décadas, horas por ano, rankings (gênero, sistema, desenvolvedora, publisher) e curiosidades em texto (inclui correlação nota x dificuldade/duração). A mesma função alimenta a página de exemplo.
10. Edição em massa (feito): no catálogo, "Selecionar vários" liga a seleção; a barra inferior altera sistema, gênero, dificuldade, nota, ano de lançamento, desenvolvedora ou publisher, ou exclui
   (`POST /api/games/bulk`, até 500 por vez; conflitos de jogo repetido e ano depois da finalização são pulados e listados; trocar a dificuldade recalcula o XP).
11. Organizar nomes (feito): `/organizar` (exemplo em `/organizar/exemplo`). Junta variações de sistema, gênero, desenvolvedora e publisher (ou renomeia) só nos dados do usuário
   (catálogo, backlog e dropados). Sugere duplicados por chave canônica (apelidos de sistemas, ruído de empresas como "Co., Ltd.", erro de 1 letra em gêneros/empresas).
   Se o nome de destino já tem o mesmo jogo (título+ano) a linha fica como estava e é listada. Nomes que ninguém mais usa são removidos da tabela compartilhada.

12. Memórias e diário (feito): no formulário do jogo, seção recolhida "Memórias (opcional)": quem apresentou, onde conseguiu, idade na época, onde jogou e com quem (vários nomes).
   Diário de bordo = notas datadas (`game_notes`) na página do jogo e no backlog; ao zerar um jogo do backlog as notas passam para o jogo (`from_backlog_id`). Página `/pessoas` (pessoas e lugares).
13. Máquina do tempo (feito): `/maquina-do-tempo` mostra o catálogo como era em uma data (jogos, horas, sistemas, nível) e o que foi zerado por volta dela. Calculada das datas de finalização dos jogos atuais.
14. Modo calmo (feito): `users.calm_mode`; escondido com a classe `xp-ui` + `html[data-calm]` (XP, nível), menu sem Ranking/Metas/Estatísticas, essas rotas redirecionam e o dashboard esconde os blocos de números
   (meta, números, por mês, gêneros e sistemas) e o nível da carta. O XP continua sendo calculado.
15. Visuais do catálogo (feito): `users.catalog_layout`; três visuais: estojo, estante 3D (capas grandes e inteiras, pouco ângulo, informações abaixo da caixa) e terminal
   (`views/partials/catalog_layouts.php` + `public/assets/css/layouts.css`). Lombadas, mural, lista, fichas e carrossel foram testados e removidos por decisão do dono.
   Troca na barra do catálogo e em `/visual`; `?visual=shelf` abre direto sem salvar.
16. Livro da vida gamer (feito): `/livro` monta capa, sumário e um capítulo por ano (memórias e diário), sempre em "papel" e com CSS de impressão; salvar em PDF pelo navegador.

17. Música do momento (feito): `games.music_title` + `music_url` (só http/https) nas memórias; aparece na página do jogo e no livro.
18. Save state (feito): `games.used_save_states`; marcado no formulário, o XP do jogo cai para 50% (`XpService::halve`, total arredondado, repartido entre os eventos), vale também ao editar,
   mudar a dificuldade em massa e em `rebuildAll`. A página do jogo mostra "Zerado com save state". No modo calmo o aviso de XP some, a marca fica.

19. Dashboard de história (feito): `views/home.php` + `views/partials/dash_widgets.php` + `public/assets/js/dashboard-extra.js`, dados em `DashboardService` (`GET /api/dashboard?year=`; exemplo em `/api/demo/dashboard`).
   Blocos: estante (jogando agora "em cima da mesa" + últimos zerados em prateleira 3D), hoje na sua história (neste dia em outros anos + nota antiga do diário), carta de jogador (título calculado, 4 atributos, baixar como imagem), constelação (lançamento x quando zerou), rio de gêneros, trilha do ano, com quem você jogou, e os antigos (meta, números, por mês, gêneros e sistemas).
   Painel montável: "Personalizar" liga/desliga e reordena os blocos; salvo em `users.dashboard_layout` (JSON). Frase de contexto no topo, sem cobrança. Sem jogos: os blocos some sozinhos.

20. Screenshots (feito): tabela `game_shots` (jogo do catálogo OU do backlog; `note_id` opcional para anexar a uma nota do diário, `ON DELETE SET NULL`).
   `ShotService` reprocessa (sem EXIF), limita a 1920 px e salva WebP + miniatura de 480 px em `public/uploads/shots/` (até 8 MB por arquivo, 300 por jogo).
   Galeria em `partials/shots.php` (página do jogo e edição do backlog), anexos no formulário do diário (`partials/diary.php`) e visualizador em tela cheia (`partials/lightbox.php`, setas e Esc).
   Ao zerar a partir do backlog as imagens passam para o jogo; ao apagar jogo, backlog ou conta, os arquivos são removidos. O livro mostra até 4 por jogo (opção "Screenshots").

21. Data de início (feito): `games.started_at` (opcional, não pode ser depois da finalização). No "Zerei!" do backlog, o formulário traz a data de "jogando agora" e a capa;
   o servidor copia a capa do backlog para um arquivo próprio do jogo (`UploadService::copy`), a menos que o usuário troque ou tire a capa (`skip_backlog_cover`).
   A página do jogo mostra "começou em … · N dias" e o livro "de … a …".

22. Importação do GamerTracker (feita em 27/10/2026 para o dono do projeto): `bin/import_gamertracker.php` (simulação por padrão; `--commit` grava; `--franchises` traz as franquias, que ficaram para depois).
   327 jogos com capa, nota 1..5 -> 2..10, dificuldade Jornalista Gamer/Humano/Herói/Divindade -> C/B/A/AAA, tempo HH:MM:SS -> minutos, "(Remake)"/"(AAAA)" tirados do título,
   desenvolvedora/publisher do Wikidata quando título e ano batem (`--enrich`, gerado por um script de busca à parte). Backups do banco em `backups/` (fora da web).

23. Menu em tela única (feito): `views/partials/menu.php` + `menu_item.php`. Sem submenus: busca no topo (sem acento, palavras-chave extras), linha "Mais acessados" (6)
   e quatro seções lado a lado (Principal, Análises e história, Navegar por, Configurações), cada uma ordenada pelos mais visitados. Visitas contadas no servidor
   (`menu_usage`, em `PageController::view`), então a ordem vale em qualquer aparelho. Teclado/controle: setas percorrem tudo em ordem, Enter abre, Esc limpa a busca/fecha; Temas continua como tela própria.

24. Cota de notas altas (feito): `RatingQuotaService` — 1 nota 10 a cada 10 jogos zerados e 1 nota 11 a cada 50 (cotas independentes; o jogo sendo cadastrado entra na conta).
   Vale no cadastro, na edição (quem já tinha a nota mantém), na edição em massa e na importação (a pré-visualização marca as linhas que passam do limite, na ordem da planilha).
   Notas existentes nunca são rebaixadas. O formulário mostra quantas estão livres e trava os botões 10/11 sem cota (`GET /api/me/rating-quota`).

25. Navegar por nota (feito): `/notas` (exemplo em `/exemplo/notas`), na mesma página `browse.php` dos outros agrupamentos (dim `rating`, sempre 11 -> 1).
   Mostra a cota das notas 10 e 11 (vagas usadas, quanto falta para a próxima, barra de progresso) e um selo em cada cartão. O catálogo ganhou o filtro `rating`.
   Os jogos de exemplo respeitam a cota (as notas altas que sobravam descem um degrau).

26. **Diário** (`/diario`, exemplo em `/diario/exemplo`): uma linha do tempo única de tudo, do mais recente ao mais antigo (ou ao contrário).
   Eventos: entrou na fila (data de criação no backlog), começou a jogar, zerou (nota, dificuldade, tempo, "em N dias", com quem, onde, música),
   largou (motivo), anotações do diário de bordo com imagens, screenshots soltas e **registros do dia** sem jogo (tabela `journal_entries`, migration 024).
   Tela: meses como capítulos, dia em destaque, filtros por tipo, busca, filtro por pessoa, "história de um jogo" (`?jogo=g12`), índice por mês e
   escrita direto na página (nota de um jogo ou registro livre). `JournalService::build` é compartilhado com o exemplo; API em `/api/journal`.
   A data de entrada na fila é guardada em `queued_at` (games, backlog_games, dropped_games; migration 025) e acompanha o jogo ao zerar, dropar ou retomar.
27. **Dropados só pelo backlog**: não existe mais "adicionar dropado". O "Dropei" do backlog abre `/dropados/novo?from_backlog=<id>` já com a capa;
   `DroppedService::create` exige `from_backlog_id`, copia a capa (ou usa a nova), leva `queued_at`/`started_at` e apaga o item do backlog na mesma transação.
   O diário acompanha o jogo: `game_notes` e `game_shots` têm `dropped_id` (migration 026); notas e screenshots passam do backlog para o dropado
   ao dropar e voltam no "voltei a jogar". A edição do dropado mostra o diário e a galeria; apagar o dropado apaga as imagens.

28. **Limite de 4 jogos "jogando agora"** (`BacklogService::MAX_PLAYING`): `create`, `update`, `start` e o "voltei a jogar" dos dropados barram o 5º com erro 422
   (`errors.status`). Quem já estava jogando não ocupa vaga nova ao ser editado. Na tela do backlog há o quadro "Como funciona" com as vagas, o botão
   "Comecei a jogar" vira "Mesa cheia" e o formulário desabilita "Jogando agora". No dashboard, a "mesa" mostra 4 vagas lado a lado, com "Vaga livre" nas vazias.

29. **Faixa do estojo na cor do console** (`UI.bandStyle` em `public/assets/js/ui.js`): `BAND_ALIASES` junta os nomes do mesmo console
   (PS1/Playstation, PS5/Playstation 5, SNES/Super Nintendo…) e `BAND_COLORS` define fundo, texto, pontinho e fio de cada um.
   Sistemas fora da lista ganham uma cor estável pelo nome. A `.case-band` (themes.css) lê `--band-bg`, `--band-fg` e `--band-led`.
   Usada no catálogo, backlog, dropados e franquias.
30. **Capas sem título**: com imagem, o estojo mostra só os chips (gênero/tempo/ano); o título aparece apenas quando não há capa.
31. **Imagem do card da franquia** (migração 027, `franchises.cover_path`): no formulário, upload próprio ou a capa de um jogo da
   franquia que está no catálogo (copiada com `UploadService::copy`). `POST /api/franchises/{id}/cover` aceita multipart
   (`cover`) ou JSON `{game_id}`; `DELETE` remove. Sem imagem, o card continua com o mosaico das capas dos jogos zerados.
   A mesma imagem vira o banner hero da página da franquia (título por cima); sem ela, o cabeçalho é o simples.
   Essa imagem usa o perfil `upload.banner` do config (até 2560×1600, 8 MB, WebP q90); as capas seguem em 600×900.
   Enquadramento separado para card e banner (migração 028, `franchises.cover_focus` JSON `{card,hero}` com x/y em % e zoom 1–3),
   ajustado no formulário arrastando a prévia, com setas/+−, roda do mouse ou o controle de zoom; aplicado por `UI.focusStyle()`.
   Imagem própria do banner (migração 029, `franchises.hero_path`; `POST/DELETE /api/franchises/{id}/hero`). Cada lugar usa a sua
   imagem e, sem ela, a do outro; o formulário edita os dois por abas (● própria, ◐ herdada, ○ nenhuma).
   A página da franquia mostra o tempo total gasto nela (soma de `playtime_minutes` dos jogos zerados, cada jogo uma vez).
32. **Best of** (migração 030, tabela `best_picks`; `BestOfService`, `BestOfController`): o melhor jogo escolhido pelo usuário
   em cada sistema, gênero, desenvolvedora, publisher e franquia. Regras: só concorrem jogos zerados da própria categoria;
   a categoria precisa de `BestOfService::MIN_GAMES` (2) jogos; a escolha é revalidada na leitura (se o jogo muda de
   categoria, some). API: `GET /api/best/candidates?kind=&value=`, `PUT /api/best {kind, value, game_id|null}`.
   Aparece nos cards de Sistemas/Gêneros/Desenvolvedoras/Publishers (capa em destaque + faixa), na página e no card da
   franquia, e como selos na página do jogo. Seletor compartilhado: `views/partials/best_of.php` (evento `best-open`).
   Juntar nomes em "Organizar" muda a chave do grupo: o best of do nome antigo deixa de valer.
33. **Hoje na sua história → "Do seu hall da fama"** (substitui a nota aleatória do diário): entre os jogos com GOTY ou best of,
   mostra o zerado mais perto da data de hoje no calendário (dia/mês; dez↔jan são vizinhos; empate = o mais antigo).
   `DashboardService::memoryPick()` recebe as honras (`GotyService::picks` + `BestOfService::honors`); campo `memory_pick`.
34. **Dashboard enxuto**: grade de 2 colunas no computador (`WIDGETS[span]` = full|half; a carta ocupa 2 linhas) e abas
   Agora/História/Números no celular (`WIDGETS[group]`). "Meta do ano" entrou na faixa de números (bloco `goal` removido);
   "Por mês" ficou só com o gráfico; "Gêneros e sistemas" mostra um gráfico por vez. Bloco novo **Próximos passos**
   (`NextStepsService`: vaga em jogando agora + próximo da fila, franquia quase completa, GOTY e best of pendentes).
   A constelação saiu do `/api/dashboard` e vem de `/api/dashboard/constellation` (formato compacto, só quando o bloco aparece).
35. **Catálogo enxuto**: barra única (busca, botão Filtros com contagem, ordenação, densidade, visual); os 9 filtros abrem num
   painel (gaveta no celular) e os ativos viram etiquetas removíveis; atalhos "Hall da fama" e "N sem <dado>".
   Grade em divisões conforme a ordenação (ano, nota, letra, faixa de horas, dificuldade) com índice "Ir para" fixo,
   densidade Confortável/Compacto (`mgl-density`) e desenho em lotes de 60. `GET /api/games` ganhou `view=grid` (lista enxuta,
   até 250 por página, com `honors` de cada jogo), `honors=1`, `missing=developer|publisher|genre|cover|release_year` e
   `meta.summary`/`meta.best` na página 1; `/api/me/filters` traz `missing` (contagens). "Preencher todos de uma vez" abre a
   edição em massa já no campo que falta.
36. **Página do jogo**: destaque com a capa desfocada ao fundo, honras (GOTY com o comentário, best of) e os números principais;
   duas colunas (ficha fixa à esquerda: "No seu catálogo", ficha com "+ adicionar" nos dados vazios, franquias, XP recolhido;
   memórias, tarefas, diário e screenshots à direita); "Você também zerou" (franquia, desenvolvedora, sistema) e
   anterior/próximo na ordem em que foram zerados (também pelas setas ← →). Dados de `GameContextService`.
37. **Backlog**: "Como funciona" recolhível e vagas no cabeçalho; "Jogando agora" virou a mesa (painel escuro com as 4 vagas:
   cada jogo com a última nota do diário, Zerei!/Dropei e menu ⋯; as vagas livres sugerem o próximo da fila com "Começar").
   Fila dividida por prioridade, estojos menores, "esperando há N meses" (dourado a partir de 6 meses), selo "Próximo",
   franquia de cada item, filtro por sistema e visão Capas/Lista (`mgl-backlog-view`). `BacklogService::list` traz
   `last_note`, `franchise` e `queued_at`.
38. **Formulários (jogo, backlog, dropados)**: estojo ao vivo (`partials/form_case.php`: mostra como o jogo vai ficar e recebe a
   capa), quadro "No seu catálogo" enquanto digita o título (`partials/title_check.php` + `GET /api/me/title-check`: já
   zerado, repetido no mesmo sistema, no backlog, nos dropados, franquia e "com este, fica completa") e barra fixa de salvar
   com o que falta. No jogo: seções numeradas, nota e dificuldade nas cores do site e sucesso com o estojo e "Ver o jogo".
39. **Navegar por (cada página com identidade própria)**: destaque escuro no topo (`partials/browse_hero.php`) e lista própria
   (`partials/browse_lists.php`). Sistemas: sistema de cabeceira + barra por família e cartões divididos por família
   (PlayStation, Nintendo, Xbox, Sega, PC, Arcade...) com a faixa do console. Gêneros: nuvem (tamanho = jogos, cor = nota).
   Desenvolvedoras/Publishers: pódio e ranking com as parceiras (grupos trazem `partners`). Lançamento: linha do tempo por ano
   e anos por década. Notas: distribuição 1–11, média, mediana e moda. Dificuldades: escada C→AAA. Grupos trazem `years`.
   Pessoas e lugares: parceiro de jogatina e lugar favorito, nuvem, avatares por nome, ícone por tipo de lugar e o
   cruzamento "onde se joga com quem".
40. **Análises e história (destaque escuro em cada uma)**: Retrospectiva (ano gigante sobre a capa do nº 1 + a frase do ano),
   GOTY (prateleira dourada do hall da fama, em ordem de ano), Máquina do tempo (painel de viagem com a data em dígitos,
   "você voltou N anos" e o jogo que tinha acabado de zerar), Estatísticas (ficha do jogador com ritmo e perfil),
   Timeline (minimapa de meses clicável, maior jejum), Mapa de calor (sequência maior/atual e 12 semanas em chamas),
   Metas (previsão numa pista jan→meta: você, "hoje" e a chegada prevista) e Livro (livro em 3D com mosaico das capas).
41. **Reestruturação completa (cada página com recursos próprios)**:
   - Franquias: estantes (continue de onde parou com a trilha de cada série, galeria de troféus, ainda não começou) ou grade;
     em foco a mais perto do fim. A lista traz `minutes`, `next`, `last_done` e `track` (FranchiseService::extras).
     Página da franquia: trilha clicável no banner, filtro todos/zerados/faltando, visão por seção ou linha do tempo por
     ano de lançamento, lateral com best of, números, ordem em que zerou e atalho das seções. Formulário: prévia ao vivo
     do banner e do card + checklist.
   - Dropados: sala de espera (mais perto de voltar, esperando há mais tempo), quadro em 3 colunas por chance (↑/↓ move)
     ou estante, padrões (gênero/sistema que mais larga). Formulário em 3 etapas com motivos rápidos.
   - Perfil (/u/{nome}): cartão de jogador (anel de XP, vitrine em leque), Você × fulano (API devolve `versus`),
     afinidade, DNA gamer (`dna`: gêneros, sistemas, dificuldade, ritmo por ano). Ranking: pódio, "faltam X para passar",
     destaques por categoria e "em alta" (API devolve `last` e `month` de cada jogador).
   - Retrospectiva: capítulos (pódio, jornada mês a mês com capas, recordes, perfil do ano) + modo história em slides.
     GOTY: linha dourada por década, indicados do ano e duelo mata-mata para decidir. Máquina do tempo: naquele dia × hoje,
     estante daquele dia e eixo de ±45 dias. Estatísticas: abas (visão geral, hábitos, gostos, rankings), arquétipo,
     relógio da semana, mosaico de gêneros (sem Chart.js). Timeline: eras (pausas > 60 dias ou por ano) e modo mural.
     Mapa de calor: 12 calendários, termômetro dos meses e estações. Metas: checkpoints 25/50/75/100% e plano mês a mês.
     Livro: modo folhear (setas/teclado), dedicatória, título de capítulo e estrela do ano.
42. **Painel (antigo "Dashboard")**: destaque escuro com saudação, anel de XP e os números do ano (o bloco "numbers" do painel
   montável agora vive no destaque; o seletor de ano fica ali e só muda números, gráficos e trilha). Trocar o ano não
   recarrega `/api/dashboard`: a trilha vem de `/api/dashboard/soundtrack?year=` (e `/api/demo/...`). Bloco novo "Sua semana"
   (`week` na API: últimos 7 dias × 7 anteriores, notas, sequência de semanas; no exemplo, a semana termina em 15/10/2026).
   "Zerei" direto em "Jogando agora", vagas livres como links (compactas no celular), esqueleto e aviso de erro dos blocos,
   carta de jogador em linha inteira, gráficos de mês e de gêneros/sistemas em HTML (meta do mês tracejada), alternativas
   em texto para constelação e rio, janela "ver todos" com foco preso, painel montável com arrastar e soltar e destaque
   do bloco mexido. Blocos novos entram na posição padrão em layouts já salvos.
43. **Formulários de jogo e de backlog**: destaque escuro com o registro ao vivo; no celular o estojo vira uma linha compacta
   (os campos vêm primeiro) e "No seu catálogo" aparece logo abaixo do título. Jogo: datas lado a lado em "Como foi", atalhos
   de tempo (~5h…~80h), nota/dificuldade navegáveis pelas setas, "você também deu N para…" (calibra a nota), "usar gênero,
   ano e empresas" de outra versão já registrada (o partial title_check dispara o evento `title-check`), rascunho no navegador
   (`mgl.draft.game` / `mgl.draft.backlog`), aviso ao sair sem salvar, comemoração ao registrar e "Adicionar outro" mantendo
   sistema e data. Backlog: etapas, lugar na fila com as capas vizinhas, "Adicionar e continuar" e "Vários de uma vez"
   (um título por linha, `Título | Ano`). A capa aceita Ctrl+V (form_case). Código comum em public/assets/js/form-helpers.js
   (`FormKit.mix`), usado também pelo formulário de dropados.
44. **Backlog (lista)**: destaque escuro com mesa, fila, espera média e previsão "no seu ritmo" (jogos zerados nos últimos
   12 meses via /api/me/stats; no exemplo, 2,5/mês fixo) + adicionar rápido. Fila ordenada guardada em `queueAll` e refeita
   só quando a lista muda (`resort()`). Sorteio ponderado "O que eu jogo agora?", arrastar capas entre prioridades,
   chips de filtro por prioridade, selecionar vários (prioridade/excluir), seção "Esquecidos" (> 1 ano na fila), alerta de
   jogo parado na mesa (> 45 dias sem nota no diário), vagas livres compactas no celular, menu ⋯ e janelas com foco/teclado.
45. **Catálogo**: destaque escuro com os números do catálogo/recorte, "Me surpreenda" (sorteia, rola e destaca), "⬇ CSV" do
   recorte (gerado no navegador, `;` e BOM para o Excel), visões salvas (`mgl-catalog-views` no navegador), atalho "/" para a
   busca. A busca da API (`GameService::list`, parâmetro q) agora procura também em sistema, gênero, desenvolvedora e publisher;
   nova ordenação `release` (ano de lançamento, divisões por década). Divisões calculadas uma vez (`regroup()`), com nota
   média e o melhor da divisão no cabeçalho. 5 capas por linha em telas largas, "👁 Espiar" (painel lateral com detalhes,
   memórias e a última nota do diário), seleção acessível (role=button + aria-pressed, espaço marca) e o visual "Mosaico"
   (aceito em `MeController::LAYOUTS`).
46. **Página do jogo**: destaque sem espaço vazio (no celular, capa pequena ao lado do título e números embaixo), 2 curiosidades
   em destaque, ações ✎ Editar / 📝 Nova nota / 🔁 Jogar de novo (POST /api/backlog; 409 = "já está no backlog") e menu ⋯
   (baixar o cartão do jogo em PNG via canvas, copiar link, excluir). Datas: "Começou e zerou no mesmo dia" ou "em N dias".
   "A jornada": linha do tempo começo → notas do diário (com marcador 🖼 quando a nota tem imagens; abre o lightbox) → fim.
   Aside: "Na sua história" e "Comparado aos outros" (`GameContextService` devolve `story` e `compare`), XP sem lista vazia.
   No celular as histórias vêm antes da ficha. Relacionados viram abas (tablist + setas) só com mais de um grupo. Atalhos E e N.
   Parciais compartilhadas: `diary.php` e `shots.php` ficam compactas quando vazias; o diário ouve `diary-new` e emite
   `notes-changed`; a galeria emite `shots-loaded` (usados pela linha do tempo).
47. **Franquias (lista e página)**: na página da franquia os zerados ficam em estojo e os que faltam em cartões baixos
   (com "✓ Na fila / ▶ Jogando / ✕ Dropado" de `FranchiseService::queueStatus`, montado em `PageController::franchiseShow`),
   seções recolhíveis (`mgl.franchise.closed.{id}`), busca + atalho "/", trilha com uma parada no Tab (setas + Enter),
   menu ⋯ (cartão PNG, copiar link, excluir), previsão de conclusão, "Sua jornada na série" (lançamento × quando zerou)
   e franquia anterior/seguinte (`FranchiseService::neighbors`, teclas ← →). "itens" no lugar de "jogos" quando o mesmo
   título+ano aparece em mais de uma seção. Lista: trilha completa (sem o corte de 60 em `extras()`), "▶ Próximos das suas
   séries" (manda o próximo de cada série para o backlog), 🎲 sortear série não iniciada, ordenação também nas estantes
   ("Padrão" mantém a ordem de cada grupo), plural certo e atalho "/".

48. **Revisão geral de design e coesão** (decisões do dono: menu em tela cheia fica; termos em inglês e linguagem de jogador ficam; nenhum tema é oficial, então tudo vale para os 20 temas).
   - **Kit oficial** em `resources/css/app.css` (@layer components): botões `.btn` / `.btn-ghost` / `.btn-light` / `.btn-danger` em 3 tamanhos (`.btn-sm`, padrão, `.btn-lg`),
     `.seg` + `.seg-btn` (controle segmentado), `.panel` / `.card` / `.card-sm`, `.hero-box` (herói), `.empty`, `.skeleton`, `.alert-error`, `.demo-note`, `.modal-back` + `.modal-box`,
     tipografia `.t-page` `.t-hero` `.t-hero-xl` `.t-section` `.t-card` `.kicker` `.kicker-hero` `.caption` `.page-sub`, `.back-link`, esqueleto `.page` + `.page-narrow` (5xl, formulários/ajustes) /
     `.page-normal` (6xl) / `.page-wide` (7xl, listas). Usar sempre estas classes em tela nova (veja `/guia-visual`).
   - **Tokens novos** em `theme.js`: ouro `--gold-*` (Tailwind `gold-50..950`, substitui todos os `amber-*`) e `--hero` (Tailwind `bg-hero`: fundo escuro dos heróis, puxado para a cor da marca do tema).
     Tamanho `text-2xs` (11px) no lugar de `text-[10px]/[11px]`; pesos: `font-extrabold` virou `font-black`. Camadas nomeadas: `z-pop` 30, `z-fab` 40, `z-modal` 45, `z-menu` 50, `z-viewer` 60.
   - **Navegação**: `NavService` (src/Services/NavService.php) é a lista única de páginas, usada pelo menu em tela cheia e pela faixa "onde você está" (`partials/crumb.php`, em todas as páginas
     de `<main class="page ...">`), com as páginas irmãs; página nova entra no `NavService::groups()`. `PageController::view()` passa `navPath` para as views.
   - **Menu**: busca também em jogos e franquias (a partir de 2 letras) e ação "Sortear o próximo do backlog" (`/backlog?sortear=1`); "Continue de onde parou" (última página + jogando agora);
     prévia dos temas na página inteira ao passar o mouse; atalhos globais `M` menu, `?` ajuda, `G` + letra navega (P J N B D F U R E T C Y O A). Faixa de XP no topo (`.xp-strip`, some no modo calmo).
   - **Peças novas**: `/guia-visual` (todas as peças no tema atual, com troca de tema só para conferir), página de erro/404 própria (`views/error.php`, só para quem pede HTML; `/api/*` continua em JSON),
     `favicon.svg`, `<meta description>` e `og:` no layout (`PageController::view(..., description:)`), login/cadastro com apresentação do site (`partials/auth_pitch.php`), `icon()` em `helpers.php`
     (busca/fechar/mais/lixeira/relógio; emojis continuam no conteúdo), transição entre páginas (`@view-transition`, desligada com "reduzir movimento"),
     modo "Apresentar" (`Present.toggle()`, tela cheia sem menus) e "🖼 Cartão" (`ShareCard.download`) em Estatísticas, Metas, Retrospectiva e Perfil (`shareCard()` em cada página),
     marco discreto na página do jogo (1º, 10º, 25º, 50º, 100º e múltiplos de 50).
   - **Vocabulário** (também no `/guia-visual`): Painel (não "Dashboard"), zerado/zerar, Registrar, Backlog ("Na fila" / "Jogando"), Dropado, Franquia (não "série"), Catálogo (não "Meu catálogo"), Timeline.
   - Fontes: já eram carregadas só quando usadas (`@font-face` local); nada a mudar.

49. **Menu em tela cheia mais dinâmico** (`partials/menu.php` + `menu_item.php`, modo `icon`): em vez de listas por seção, todas as páginas viram uma grade compacta de ícones (4 grupos lado a lado em telas largas, 2 em médias, 1 no celular), na ordem fixa do `NavService`; tudo cabe em 1366x768 sem rolar. Sem favoritos nem "mais acessados" (a grade fixa dispensa; decisão do dono). "Continue de onde parou" fica numa linha de atalhos acima da grade. Barra de dica embaixo mostra o que o ícone selecionado faz. Setas ↑↓ andam pela grade por proximidade (`moveGrid`), ←→ em sequência. Os ícones entram em cascata ao abrir (`.menu-cell`, desligada com "reduzir movimento"). A busca continua como lista de resultados.

50. **Retrospectiva: só nota 9, 10 ou 11 entra no pódio.** Regra em `RetrospectiveService::MIN_RATING_TOP`: `setTop` recusa (422) jogo com nota menor; o seletor lista só `electable` (`candidates` continua com todos os jogos do ano, usado nos gráficos e meses); `can_pick` exige ao menos 1 jogo elegível e a tela avisa quando não há nenhum; top antigo com jogo abaixo da regra não é apagado do banco, mas deixa de aparecer no pódio e no perfil público (quem estava abaixo sobe). GOTY e Best of não mudaram.

51. **GOTY e Best of: só nota 9, 10 ou 11 podem ser eleitos** (a mesma constante do pódio da Retrospectiva, `RetrospectiveService::MIN_RATING_TOP`; `BestOfService::MIN_RATING`). GOTY: `years()` só traz como `candidates` os jogos 9+ (`eligible` = quantos; `games` continua o total do ano), `set()` recusa nota menor (422), `picks()` (perfil, honras) ignora escolha abaixo da regra. Best of: `candidates()` traz só 9+ (`$eligibleOnly=false` conta todos), `choose()` recusa nota menor, `forDim/forFranchise/forGame/honors` ignoram escolha abaixo da regra, `/api/best/candidates` devolve `total` e `min_rating`, e o seletor avisa quando nenhum jogo da categoria cumpre. Escolhas antigas abaixo da regra NÃO são apagadas do banco: só deixam de valer/aparecer (voltam se a nota do jogo subir).

52. **Painel, "Em outros anos" (Hoje na sua história) nunca fica vazia**: `TimeService::onThisDayFrom` não usa mais a janela de ±2 dias (`SAME_SEASON_DAYS` foi removida); **regra de 5 dias** (`TimeService::SEASON_DAYS`, depois da revisão): valem os jogos até 5 dias antes ou depois de hoje, no máximo `ON_THIS_DAY_LIMIT` = 10, os mais perto da data primeiro (empate: ano mais recente, depois maior nota); se nenhum cair nos 5 dias, a lista não fica vazia e traz os 10 mais perto, a qualquer distância (e a frase sob o título avisa). Jogos do ano atual não entram. `season_days` agora é a distância do mais longe da lista; a frase sob o título (`seasonNote()` em `dashboard-extra.js`) diz quantos jogos e a que distância. A aba só some se não houver nenhum jogo de ano anterior. O endpoint `/api/time/on-this-day` usa a mesma função.

53. **"Jogando agora" ordenado pela anotação mais recente** (Painel e Backlog): quem recebeu uma nota no diário por último vem primeiro, com a nota dele (a mais recente escrita, não a de data mais nova: `game_notes.id` maior); quem não tem nota vem depois, do que começou há menos tempo. `BacklogService::playing()` agora ordena e traz `last_note` (id, date, body); `list()` também devolve `last_note.id`; `DashboardService` deixou de consultar as notas por conta própria; a tela do backlog ordena `get playing()` pelo mesmo critério.

54. **Ícones do menu maiores, acompanhando a tela**: `.menu-ico` usa `clamp()` pela altura da janela (até 6,5rem; em telas largas ≥1280px cresce mais, 9,5vh; abaixo disso 7vh), o desenho ocupa 52% do quadrado e o rótulo cresce junto (até 1rem, até 3 linhas). Em ≥1280px o menu usa quase a largura toda (`xl:max-w-[min(94vw,96rem)]`) com 3 ícones por linha em cada grupo. Testado sem rolagem em 1024x900, 1280x720, 1366x768 e 1920x1080; no celular continua rolando.

Fora do escopo (decisão do dono do projeto): conquistas/troféus.

## 10. Front-end hospedado no próprio site (sem CDNs)
- **Tailwind** é compilado para `public/assets/css/app.css` com a CLI standalone (sem Node): `binuild_css.bat` (ou `binuild_css.bat watch` ao editar).
  Rode de novo sempre que adicionar/trocar classes nas telas. Config: `tailwind.config.js`; entrada: `resources/css/app.css` (componentes `.field`, `.card`, `.btn`...).
  O executável fica em `tools/tailwindcss.exe` (v3.4.16, fora da pasta pública, não vai para o git).
- **Alpine.js e Chart.js**: arquivos em `public/assets/vendor/` (versões fixas no nome do arquivo).
- **Fontes dos temas**: `public/assets/fonts/` (woff2, subconjunto latin) + `fonts.css`. Para refazer: `python bin/fetch_fonts.py`.
- Os assets são servidos com `asset('/caminho')` (acrescenta `?v=<data do arquivo>`), então o cache longo é seguro.
- Apache: `mod_deflate` e `mod_expires` ligados (compressão e cache); `public/.htaccess` já traz as regras.

---

## Versão local (programa para Windows) — pasta `C:\MyGameList`

Projeto separado do site em XAMPP (que não foi alterado). Diferenças:

- **Banco SQLite** em `%LOCALAPPDATA%\MyGameList\mygamelist.db` (variável `MGL_DATA` muda a pasta). Esquema em `app/database/schema/NNN_*.sql`, aplicado sozinho
  pela `Core\Database` (`PRAGMA user_version`); mudança de banco = novo arquivo `002_*.sql`.
- `Core\Database` registra a collation `UCI` (sem maiúsculas/acentos), `LIKE` com a mesma regra e as funções `YEAR`, `MONTH`, `CURDATE`, `NOW`, `FIELD`.
  Em SQL novo: `ON CONFLICT(...) DO UPDATE`, `INSERT OR IGNORE`, `Database::isDuplicate($e)`.
- **Contas só com login e senha** (mínimo 4, diferente do login); sem e-mail.
- Sem Apache/.env: `public/router.php` no servidor embutido do PHP, só em 127.0.0.1; uploads e sessões na pasta de dados.
- **Lançador** `lancador/Lancador.cs` -> `MyGameList.exe` (porta 47615, janela do Edge em modo aplicativo, encerra o PHP ao fechar).
- Os itens do BRIEFING acima que falam de MySQL, `.env`, `database/migrations` e e-mail valem só para o site em XAMPP.
- **Entrada e usuários** (2026-11-26): `/login` é a tela "Quem vai jogar?" (um ícone por usuário; sem usuários vai para `/cadastro`); `/cadastro` é um assistente (nome, senha, imagem) que termina
  oferecendo o tour opcional `/tutorial` (8 passos, imagens em `public/assets/tutorial/`, feitas só com dados de exemplo; refazer se as telas mudarem muito). Imagem do usuário: `users.avatar`
  (migração `002_avatar.sql`), `AvatarService` (ícones prontos ou foto recortada em quadrado em `uploads/avatars`), `POST /api/account/avatar`, `UI.avatarContent()` + `APP.avatars` nas telas.
- A partir de 2026-11-26 os pedidos do dono são só para este app (o site em XAMPP não deve ser alterado).
- **Sem senha** (pedido do dono, 2026-11-26): entrar é clicar no usuário (`POST /api/auth/login {login}`); criar pede só nome e imagem; excluir conta pede digitar o nome. Saíram troca/redefinição de senha, bloqueio por tentativas e histórico de logins. A coluna `users.password_hash` e as tabelas `login_attempts`/`login_events` continuam no banco, sem uso (vale o que está aqui, não os itens acima sobre senha).
