<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, Config, Database, HttpException, Request, Response};
use App\Services\{BacklogService, DemoCatalog, DroppedService, FranchiseService, GameService, GotyService, RetrospectiveService, XpService};

final class PageController
{
    /** Páginas "navegar por...": caminho => configuração. `dim` é o agrupamento da API; `param` é o filtro do catálogo. */
    public const BROWSE = [
        '/sistemas'           => ['dim' => 'platform',     'param' => 'platform',     'title' => 'Sistemas',           'unit' => 'sistema',        'units' => 'sistemas',         'sort' => 'games:desc'],
        '/generos'            => ['dim' => 'genre',        'param' => 'genre',        'title' => 'Gêneros',            'unit' => 'gênero',         'units' => 'gêneros',          'sort' => 'games:desc'],
        '/notas'              => ['dim' => 'rating',       'param' => 'rating',       'title' => 'Notas',              'unit' => 'nota',           'units' => 'notas',            'sort' => 'order:desc'],
        '/dificuldades'       => ['dim' => 'difficulty',   'param' => 'difficulty',   'title' => 'Dificuldades',       'unit' => 'dificuldade',    'units' => 'dificuldades',     'sort' => 'order:asc'],
        '/ano-de-lancamento'  => ['dim' => 'release_year', 'param' => 'release_year', 'title' => 'Ano de lançamento', 'unit' => 'ano',           'units' => 'anos',             'sort' => 'name:desc'],
        '/desenvolvedoras'    => ['dim' => 'developer',    'param' => 'developer',    'title' => 'Desenvolvedoras',    'unit' => 'desenvolvedora', 'units' => 'desenvolvedoras',  'sort' => 'games:desc'],
        '/publishers'         => ['dim' => 'publisher',    'param' => 'publisher',    'title' => 'Publishers',         'unit' => 'publisher',      'units' => 'publishers',       'sort' => 'games:desc'],
    ];

    public function home(Request $req): Response
    {
        $user = Auth::user();
        if ($user === null) {
            return Response::redirect(url('/login'));
        }
        return $this->view('home', 'Painel', ['me' => $this->profile($user)], menuFor: $req->path, charts: true);
    }

    public function gameForm(Request $req): Response
    {
        $user = Auth::user();
        if ($user === null) {
            return Response::redirect(url('/login'));
        }
        return $this->view('game_form', 'Adicionar jogo', ['me' => $this->profile($user)], menuFor: $req->path);
    }

    public function catalog(Request $req): Response
    {
        $user = Auth::user();
        if ($user === null) {
            return Response::redirect(url('/login'));
        }
        return $this->view('catalog', 'Catálogo', ['me' => $this->profile($user)], menuFor: $req->path);
    }

    /** Catálogo com 60 jogos fictícios (não exige login, não toca no banco). */
    public function catalogDemo(Request $req): Response
    {
        $user = Auth::user();
        return $this->view('catalog', 'Catálogo (exemplo)', [
            'me' => $user ? $this->profile($user) : null,
            'demo' => DemoCatalog::games((string) Config::get('base_path', '')),
        ], menuFor: $user ? $req->path : null);
    }

    public function gameShow(Request $req, array $p): Response
    {
        [$user, $game] = $this->ownedGame($p['id']);
        if ($game === null) {
            return Response::redirect(url($user ? '/jogos' : '/login'));
        }
        $events = Database::all('SELECT source, amount, description FROM xp_events WHERE game_id = ? ORDER BY id', [$game['id']]);
        $franchises = (new FranchiseService())->forGame($user['id'], (int) $game['id']);
        $context = (new \App\Services\GameContextService())->for($user['id'], $game, $franchises);
        return $this->view('game_show', $game['title'], ['me' => $this->profile($user), 'game' => $game, 'events' => $events, 'franchises' => $franchises, 'context' => $context], menuFor: $req->path);
    }

    public function gameEdit(Request $req, array $p): Response
    {
        [$user, $game] = $this->ownedGame($p['id']);
        if ($game === null) {
            return Response::redirect(url($user ? '/jogos' : '/login'));
        }
        return $this->view('game_form', 'Editar jogo', ['me' => $this->profile($user), 'game' => $game], menuFor: $req->path);
    }

    /** @return array{0: ?array, 1: ?array} [usuário, jogo] — jogo nulo se não existir ou não for dele. */
    private function ownedGame(string $id): array
    {
        $user = Auth::user();
        if ($user === null || !ctype_digit($id)) {
            return [$user, null];
        }
        try {
            return [$user, (new GameService())->find($user['id'], (int) $id)];
        } catch (HttpException) {
            return [$user, null];
        }
    }

    public function franchises(Request $req): Response
    {
        $user = Auth::user();
        if ($user === null) {
            return Response::redirect(url('/login'));
        }
        return $this->view('franchises', 'Franquias', ['me' => $this->profile($user)], menuFor: $req->path);
    }

    public function franchiseForm(Request $req): Response
    {
        $user = Auth::user();
        if ($user === null) {
            return Response::redirect(url('/login'));
        }
        return $this->view('franchise_form', 'Nova franquia', ['me' => $this->profile($user), 'franchise' => null], menuFor: $req->path);
    }

    public function franchiseShow(Request $req, array $p): Response
    {
        [$user, $f] = $this->ownedFranchise($p['id']);
        if ($f === null) {
            return Response::redirect(url($user ? '/franquias' : '/login'));
        }
        $svc = new FranchiseService();
        $f['queue'] = $svc->queueStatus($user['id'], $f) ?: new \stdClass();
        $f['nav'] = $svc->neighbors($user['id'], (int) $f['id']);
        return $this->view('franchise_show', $f['name'], ['me' => $this->profile($user), 'franchise' => $f], menuFor: $req->path);
    }

    public function franchiseEdit(Request $req, array $p): Response
    {
        [$user, $f] = $this->ownedFranchise($p['id']);
        if ($f === null) {
            return Response::redirect(url($user ? '/franquias' : '/login'));
        }
        return $this->view('franchise_form', 'Editar franquia', ['me' => $this->profile($user), 'franchise' => $f], menuFor: $req->path);
    }

    /** @return array{0: ?array, 1: ?array} [usuário, franquia] — franquia nula se não existir ou não for dele. */
    private function ownedFranchise(string $id): array
    {
        $user = Auth::user();
        if ($user === null || !ctype_digit($id)) {
            return [$user, null];
        }
        try {
            return [$user, (new FranchiseService())->find($user['id'], (int) $id)];
        } catch (HttpException) {
            return [$user, null];
        }
    }

    public function retrospective(Request $req): Response
    {
        $user = Auth::user();
        if ($user === null) {
            return Response::redirect(url('/login'));
        }
        return $this->view('retrospective', 'Retrospectiva', ['me' => $this->profile($user), 'demo' => null], menuFor: $req->path);
    }

    /** Exemplo com dados fictícios (não exige login e não salva nada). */
    public function retrospectiveDemo(Request $req): Response
    {
        $user = Auth::user();
        return $this->view('retrospective', 'Retrospectiva (exemplo)', ['me' => $user ? $this->profile($user) : null, 'demo' => (new RetrospectiveService())->demo()], menuFor: $user ? $req->path : null);
    }

    public function goty(Request $req): Response
    {
        $user = Auth::user();
        if ($user === null) {
            return Response::redirect(url('/login'));
        }
        return $this->view('goty', 'GOTY', ['me' => $this->profile($user), 'demo' => null], menuFor: $req->path);
    }

    /** Exemplo com dados fictícios (não exige login e não salva nada). */
    public function gotyDemo(Request $req): Response
    {
        $user = Auth::user();
        return $this->view('goty', 'GOTY (exemplo)', ['me' => $user ? $this->profile($user) : null, 'demo' => (new GotyService())->demo()], menuFor: $user ? $req->path : null);
    }

    public function ranking(Request $req): Response
    {
        $user = Auth::user();
        if ($user === null) {
            return Response::redirect(url('/login'));
        }
        if (!empty($user['calm_mode'])) {
            return Response::redirect(url('/'));
        }
        return $this->view('ranking', 'Ranking', ['me' => $this->profile($user)], menuFor: $req->path);
    }

    public function userProfile(Request $req, array $p): Response
    {
        $user = Auth::user();
        if ($user === null) {
            return Response::redirect(url('/login'));
        }
        return $this->view('profile', 'Perfil de ' . $p['username'], ['me' => $this->profile($user), 'username' => $p['username']], menuFor: $req->path,
            description: 'Os jogos zerados, o nível e a afinidade de ' . $p['username'] . ' no MyGameList.');
    }

    public function backlog(Request $req): Response
    {
        $user = Auth::user();
        if ($user === null) {
            return Response::redirect(url('/login'));
        }
        return $this->view('backlog', 'Backlog', ['me' => $this->profile($user), 'demo' => null], menuFor: $req->path);
    }

    public function backlogForm(Request $req): Response
    {
        $user = Auth::user();
        if ($user === null) {
            return Response::redirect(url('/login'));
        }
        return $this->view('backlog_form', 'Adicionar ao backlog', [
            'me' => $this->profile($user), 'item' => null, 'playing_others' => (new BacklogService())->playingCount($user['id']),
        ], menuFor: $req->path);
    }

    public function backlogEdit(Request $req, array $p): Response
    {
        $user = Auth::user();
        if ($user === null) {
            return Response::redirect(url('/login'));
        }
        try {
            $item = ctype_digit($p['id']) ? (new BacklogService())->find($user['id'], (int) $p['id']) : null;
        } catch (HttpException) {
            $item = null;
        }
        if ($item === null) {
            return Response::redirect(url('/backlog'));
        }
        return $this->view('backlog_form', 'Editar backlog', [
            'me' => $this->profile($user), 'item' => $item, 'playing_others' => (new BacklogService())->playingCount($user['id'], (int) $item['id']),
        ], menuFor: $req->path);
    }

    /** Backlog com jogos fictícios (não exige login). */
    public function backlogDemo(Request $req): Response
    {
        $user = Auth::user();
        return $this->view('backlog', 'Backlog (exemplo)', [
            'me' => $user ? $this->profile($user) : null,
            'demo' => DemoCatalog::backlog((string) Config::get('base_path', '')),
        ], menuFor: $user ? $req->path : null);
    }

    public function goals(Request $req): Response
    {
        $user = Auth::user();
        if ($user === null) {
            return Response::redirect(url('/login'));
        }
        if (!empty($user['calm_mode'])) {
            return Response::redirect(url('/'));
        }
        return $this->view('goals', 'Metas do ano', ['me' => $this->profile($user), 'demo' => null], menuFor: $req->path, charts: true);
    }

    /** Metas com dados fictícios (não exige login). */
    public function goalsDemo(Request $req): Response
    {
        $user = Auth::user();
        return $this->view('goals', 'Metas do ano (exemplo)', ['me' => $user ? $this->profile($user) : null, 'demo' => DemoCatalog::goals()],
            menuFor: $user ? $req->path : null, charts: true);
    }

    public function account(Request $req): Response
    {
        $user = Auth::user();
        if ($user === null) {
            return Response::redirect(url('/login'));
        }
        return $this->view('account', 'Minha conta', ['me' => $this->profile($user), 'presets' => \App\Services\AvatarService::PRESETS], menuFor: $req->path);
    }

    public function book(Request $req): Response
    {
        $user = Auth::user();
        if ($user === null) {
            return Response::redirect(url('/login'));
        }
        return $this->view('book', 'Livro da vida gamer', ['me' => $this->profile($user), 'demo' => false], menuFor: $req->path);
    }

    /** O livro sobre o catálogo fictício (não exige login). */
    public function bookDemo(Request $req): Response
    {
        $user = Auth::user();
        return $this->view('book', 'Livro da vida gamer (exemplo)', ['me' => $user ? $this->profile($user) : null, 'demo' => true], menuFor: $user ? $req->path : null);
    }

    public function time(Request $req): Response
    {
        $user = Auth::user();
        if ($user === null) {
            return Response::redirect(url('/login'));
        }
        return $this->view('time', 'Máquina do tempo', ['me' => $this->profile($user), 'demo' => null], menuFor: $req->path);
    }

    /** Máquina do tempo sobre o catálogo fictício (não exige login). */
    public function timeDemo(Request $req): Response
    {
        $user = Auth::user();
        return $this->view('time', 'Máquina do tempo (exemplo)', [
            'me' => $user ? $this->profile($user) : null,
            'demo' => ['today' => '2026-10-15', 'first' => '2024-12-17'],
        ], menuFor: $user ? $req->path : null);
    }

    public function visual(Request $req): Response
    {
        $user = Auth::user();
        if ($user === null) {
            return Response::redirect(url('/login'));
        }
        return $this->view('visual', 'Visual e modo calmo', ['me' => $this->profile($user)], menuFor: $req->path);
    }

    public function styleGuide(Request $req): Response
    {
        $user = Auth::user();
        if ($user === null) {
            return Response::redirect(url('/login'));
        }
        return $this->view('style_guide', 'Guia visual', ['me' => $this->profile($user)], menuFor: $req->path);
    }

    public function journal(Request $req): Response
    {
        $user = Auth::user();
        if ($user === null) {
            return Response::redirect(url('/login'));
        }
        return $this->view('journal', 'Diário', ['me' => $this->profile($user), 'demo' => false], menuFor: $req->path);
    }

    /** Diário sobre o catálogo fictício (não exige login). */
    public function journalDemo(Request $req): Response
    {
        $user = Auth::user();
        return $this->view('journal', 'Diário (exemplo)', ['me' => $user ? $this->profile($user) : null, 'demo' => true], menuFor: $user ? $req->path : null);
    }

    public function people(Request $req): Response
    {
        $user = Auth::user();
        if ($user === null) {
            return Response::redirect(url('/login'));
        }
        return $this->view('people', 'Pessoas e lugares', ['me' => $this->profile($user), 'demo' => null], menuFor: $req->path);
    }

    /** Pessoas e lugares fictícios (não exige login). */
    public function peopleDemo(Request $req): Response
    {
        $user = Auth::user();
        return $this->view('people', 'Pessoas e lugares (exemplo)', [
            'me' => $user ? $this->profile($user) : null,
            'demo' => DemoCatalog::circles((string) Config::get('base_path', '')),
        ], menuFor: $user ? $req->path : null);
    }

    public function stats(Request $req): Response
    {
        $user = Auth::user();
        if ($user === null) {
            return Response::redirect(url('/login'));
        }
        if (!empty($user['calm_mode'])) {
            return Response::redirect(url('/'));
        }
        $svc = new \App\Services\StatsService();
        return $this->view('stats', 'Estatísticas', ['me' => $this->profile($user), 'demo' => false, 'stats' => $svc->compute($svc->gamesFor($user['id']))], menuFor: $req->path, charts: true);
    }

    /** Estatísticas calculadas sobre os jogos fictícios do catálogo de exemplo (não exige login). */
    public function statsDemo(Request $req): Response
    {
        $user = Auth::user();
        $svc = new \App\Services\StatsService();
        return $this->view('stats', 'Estatísticas (exemplo)', [
            'me' => $user ? $this->profile($user) : null, 'demo' => true,
            'stats' => $svc->compute(DemoCatalog::games((string) Config::get('base_path', ''))),
        ], menuFor: $user ? $req->path : null, charts: true);
    }

    public function organize(Request $req): Response
    {
        $user = Auth::user();
        if ($user === null) {
            return Response::redirect(url('/login'));
        }
        return $this->view('organize', 'Organizar nomes', ['me' => $this->profile($user), 'demo' => null], menuFor: $req->path);
    }

    /** Organizar nomes com variações fictícias (não exige login). */
    public function organizeDemo(Request $req): Response
    {
        $user = Auth::user();
        return $this->view('organize', 'Organizar nomes (exemplo)', [
            'me' => $user ? $this->profile($user) : null,
            'demo' => (new \App\Services\NamesService())->demo(),
        ], menuFor: $user ? $req->path : null);
    }

    public function import(Request $req): Response
    {
        $user = Auth::user();
        if ($user === null) {
            return Response::redirect(url('/login'));
        }
        return $this->view('import', 'Importar jogos', ['me' => $this->profile($user), 'demo' => null], menuFor: $req->path);
    }

    /** Importação com uma planilha fictícia já lida e validada (não exige login). */
    public function importDemo(Request $req): Response
    {
        $user = Auth::user();
        return $this->view('import', 'Importar jogos (exemplo)', [
            'me' => $user ? $this->profile($user) : null,
            'demo' => (new \App\Services\ImportService())->demo(),
        ], menuFor: $user ? $req->path : null);
    }

    public function dropped(Request $req): Response
    {
        $user = Auth::user();
        if ($user === null) {
            return Response::redirect(url('/login'));
        }
        return $this->view('dropped', 'Jogos dropados', ['me' => $this->profile($user), 'demo' => null], menuFor: $req->path);
    }

    public function droppedForm(Request $req): Response
    {
        $user = Auth::user();
        if ($user === null) {
            return Response::redirect(url('/login'));
        }
        // um dropado só nasce de um jogo do backlog ("Dropei"); sem ele, volta para o backlog
        $from = (string) ($req->query['from_backlog'] ?? '');
        if (!ctype_digit($from) || Database::one('SELECT id FROM backlog_games WHERE id = ? AND user_id = ?', [(int) $from, $user['id']]) === null) {
            return Response::redirect(url('/backlog'));
        }
        return $this->view('dropped_form', 'Dropar jogo', ['me' => $this->profile($user), 'item' => null], menuFor: $req->path);
    }

    public function droppedEdit(Request $req, array $p): Response
    {
        $user = Auth::user();
        if ($user === null) {
            return Response::redirect(url('/login'));
        }
        try {
            $item = ctype_digit($p['id']) ? (new DroppedService())->find($user['id'], (int) $p['id']) : null;
        } catch (HttpException) {
            $item = null;
        }
        if ($item === null) {
            return Response::redirect(url('/dropados'));
        }
        return $this->view('dropped_form', 'Editar dropado', ['me' => $this->profile($user), 'item' => $item], menuFor: $req->path);
    }

    /** Lista de dropados com jogos fictícios (não exige login). */
    public function droppedDemo(Request $req): Response
    {
        $user = Auth::user();
        return $this->view('dropped', 'Jogos dropados (exemplo)', [
            'me' => $user ? $this->profile($user) : null,
            'demo' => DemoCatalog::dropped((string) Config::get('base_path', '')),
        ], menuFor: $user ? $req->path : null);
    }

    public function heatmap(Request $req): Response
    {
        $user = Auth::user();
        if ($user === null) {
            return Response::redirect(url('/login'));
        }
        return $this->view('heatmap', 'Mapa de calor', ['me' => $this->profile($user), 'demo' => null], menuFor: $req->path);
    }

    /** Mapa de calor com dados fictícios (não exige login). */
    public function heatmapDemo(Request $req): Response
    {
        $user = Auth::user();
        return $this->view('heatmap', 'Mapa de calor (exemplo)', ['me' => $user ? $this->profile($user) : null, 'demo' => DemoCatalog::heatmap()], menuFor: $user ? $req->path : null);
    }

    public function timeline(Request $req): Response
    {
        $user = Auth::user();
        if ($user === null) {
            return Response::redirect(url('/login'));
        }
        return $this->view('timeline', 'Timeline', ['me' => $this->profile($user), 'demo' => null], menuFor: $req->path);
    }

    /** Timeline com os 60 jogos fictícios do catálogo de exemplo (não exige login). */
    public function timelineDemo(Request $req): Response
    {
        $user = Auth::user();
        return $this->view('timeline', 'Timeline (exemplo)', [
            'me' => $user ? $this->profile($user) : null,
            'demo' => DemoCatalog::games((string) Config::get('base_path', '')),
        ], menuFor: $user ? $req->path : null);
    }

    // ---------- páginas de exemplo ----------

    /** Índice com os links de todas as páginas de exemplo. */
    public function examples(Request $req): Response
    {
        $user = Auth::user();
        return $this->view('examples', 'Exemplos', ['me' => $user ? $this->profile($user) : null], menuFor: $user ? $req->path : null);
    }

    public function dashboardDemo(Request $req): Response
    {
        $user = Auth::user();
        $demo = DemoCatalog::dashboard();
        return $this->view('home', 'Painel (exemplo)', ['me' => ['username' => 'Jogador Exemplo', 'xp' => $demo['xp']], 'demo' => $demo],
            menuFor: $user ? $req->path : null, charts: true);
    }

    public function browseDemo(Request $req): Response
    {
        $user = Auth::user();
        $real = substr($req->path, strlen('/exemplo'));
        $cfg = self::BROWSE[$real];
        $demo = DemoCatalog::groups($cfg['dim'], (string) Config::get('base_path', ''));
        return $this->view('browse', $cfg['title'] . ' (exemplo)', ['me' => $user ? $this->profile($user) : null, 'cfg' => $cfg, 'demo' => $demo], menuFor: $user ? $req->path : null);
    }

    public function franchisesDemo(Request $req): Response
    {
        $user = Auth::user();
        return $this->view('franchises', 'Franquias (exemplo)', ['me' => $user ? $this->profile($user) : null, 'demo' => DemoCatalog::franchiseList((string) Config::get('base_path', ''))],
            menuFor: $user ? $req->path : null);
    }

    public function franchiseShowDemo(Request $req, array $p): Response
    {
        $user = Auth::user();
        $all = DemoCatalog::franchises((string) Config::get('base_path', ''));
        usort($all, fn($a, $b) => strcmp($a['name'], $b['name']));
        foreach ($all as $i => $f) {
            if ((string) $f['id'] === $p['id']) {
                $pick = fn($r) => $r ? ['id' => $r['id'], 'name' => $r['name']] : null;
                $f['nav'] = ['prev' => $pick($all[$i - 1] ?? null), 'next' => $pick($all[$i + 1] ?? null)];
                $f['queue'] = new \stdClass();
                return $this->view('franchise_show', $f['name'] . ' (exemplo)', ['me' => $user ? $this->profile($user) : null, 'franchise' => $f, 'demo' => true], menuFor: $user ? $req->path : null);
            }
        }
        return Response::redirect(url('/exemplo/franquias'));
    }

    public function browse(Request $req): Response
    {
        $user = Auth::user();
        if ($user === null) {
            return Response::redirect(url('/login'));
        }
        $cfg = self::BROWSE[$req->path];
        return $this->view('browse', $cfg['title'], ['me' => $this->profile($user), 'cfg' => $cfg], menuFor: $req->path);
    }

    /** Tela de entrada "Quem vai jogar?": um ícone para cada usuário deste computador. */
    public function login(Request $req): Response
    {
        if (Auth::user()) {
            return Response::redirect(url('/'));
        }
        $users = (new \App\Services\AvatarService())->users();
        return $users ? $this->view('login', 'Entrar', ['users' => $users]) : Response::redirect(url('/cadastro'));
    }

    public function register(Request $req): Response
    {
        if (Auth::user()) {
            return Response::redirect(url('/'));
        }
        return $this->view('register', 'Novo usuário', [
            'hasUsers' => (int) Database::value('SELECT COUNT(*) FROM users') > 0, 'presets' => \App\Services\AvatarService::PRESETS,
        ]);
    }

    /** Tour opcional de como usar o programa. */
    public function tutorial(Request $req): Response
    {
        $user = Auth::user();
        if ($user === null) {
            return Response::redirect(url('/login'));
        }
        return $this->view('tutorial', 'Como usar', ['me' => $this->profile($user)], menuFor: $req->path);
    }

    private function profile(array $user): array
    {
        return [
            'username' => $user['username'],
            'avatar'   => (new \App\Services\AvatarService())->present($user['avatar'] ?? null),
            'xp'       => (new XpService())->progress($user['total_xp']),
            'layout'   => $user['catalog_layout'] ?? null,
            'calm'     => !empty($user['calm_mode']),
            'dash'     => \App\Services\DashboardService::layoutFor($user['dashboard_layout'] ?? null),
            'usage'    => array_map('intval', array_column(Database::all('SELECT item, hits FROM menu_usage WHERE user_id = ?', [$user['id']]), 'hits', 'item')),
        ];
    }

    /** Páginas do menu (caminho -> item). Cada visita conta para ordenar o menu pelos mais acessados. */
    private const MENU_PATHS = [
        '/' => 'dashboard', '/jogos' => 'library', '/jogos/novo' => 'add', '/backlog' => 'backlog', '/dropados' => 'dropped',
        '/franquias' => 'franchises', '/ranking' => 'ranking', '/sistemas' => 'platform', '/generos' => 'genre', '/dificuldades' => 'difficulty', '/notas' => 'rating',
        '/ano-de-lancamento' => 'year', '/desenvolvedoras' => 'developer', '/publishers' => 'publisher', '/pessoas' => 'people', '/diario' => 'journal',
        '/maquina-do-tempo' => 'time', '/estatisticas' => 'stats', '/timeline' => 'timeline', '/metas' => 'goals', '/calor' => 'heat',
        '/retrospectiva' => 'retro', '/goty' => 'goty', '/livro' => 'book', '/importar' => 'import', '/organizar' => 'organize',
        '/conta' => 'account', '/visual' => 'visual', '/exemplos' => 'examples', '/guia-visual' => 'guide', '/tutorial' => 'tutorial',
    ];

    private function countVisit(array $user, string $path): void
    {
        $item = self::MENU_PATHS[$path] ?? ($path === '/u/' . $user['username'] ? 'profile' : null);
        if ($item !== null) {
            Database::query('INSERT INTO menu_usage (user_id, item, hits, last_at) VALUES (?, ?, 1, NOW()) ON CONFLICT(user_id, item) DO UPDATE SET hits = hits + 1, last_at = NOW()', [$user['id'], $item]);
        }
    }

    /**
     * @param string|null $menuFor caminho atual; quando informado, exibe o menu (só em páginas logadas)
     */
    private function view(string $name, string $title, array $data = [], ?string $menuFor = null, bool $charts = false, ?string $description = null): Response
    {
        $render = static function (string $file, array $vars): string {
            extract($vars, EXTR_SKIP);
            ob_start();
            require $file;
            return (string) ob_get_clean();
        };

        if ($menuFor !== null && ($u = Auth::user())) {
            $this->countVisit($u, $menuFor);
        }
        $menu = $menuFor === null ? null : $this->profile(Auth::user()) + ['path' => $menuFor];
        $dir = dirname(__DIR__, 2) . '/views';
        $base = (string) \App\Core\Config::get('base_path', '');
        $uri = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        $navPath = ($base !== '' && str_starts_with($uri, $base)) ? (substr($uri, strlen($base)) ?: '/') : $uri;
        $content = $render("$dir/$name.php", $data + ['navPath' => $navPath]);

        return Response::html($render("$dir/layout.php", [
            'title'   => $title,
            'content' => $content,
            'menu'    => $menu,
            'charts'  => $charts,
            'description' => $description,
        ]));
    }
}
