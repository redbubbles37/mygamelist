<?php
declare(strict_types=1);

use App\Controllers\{AccountController, AuthController, BacklogController, BestOfController, CommunityController, DroppedController, FranchiseController, GameController, GoalController, GotyController, ImportController, MeController, NamesController, NotesController, PageController, JournalController, TimeController, BookController, ShotController, RetrospectiveController};
use App\Core\Router;

/** @var Router $router */

// Páginas
$router->add('GET', '/',         [PageController::class, 'home']);
$router->add('GET', '/jogos/novo', [PageController::class, 'gameForm']);
$router->add('GET', '/jogos',      [PageController::class, 'catalog']);
$router->add('GET', '/jogos/exemplo', [PageController::class, 'catalogDemo']); // catálogo com jogos fictícios
$router->add('GET', '/jogos/{id}', [PageController::class, 'gameShow']);
$router->add('GET', '/jogos/{id}/editar', [PageController::class, 'gameEdit']);
// Retrospectiva
$router->add('GET', '/retrospectiva',         [PageController::class, 'retrospective']);
$router->add('GET', '/retrospectiva/exemplo', [PageController::class, 'retrospectiveDemo']); // dados fictícios
// Backlog
$router->add('GET', '/backlog',         [PageController::class, 'backlog']);
$router->add('GET', '/backlog/novo',    [PageController::class, 'backlogForm']);
$router->add('GET', '/backlog/exemplo', [PageController::class, 'backlogDemo']); // dados fictícios
$router->add('GET', '/backlog/{id}/editar', [PageController::class, 'backlogEdit']);
// Metas
$router->add('GET', '/metas',         [PageController::class, 'goals']);
$router->add('GET', '/metas/exemplo', [PageController::class, 'goalsDemo']); // dados fictícios
// Conta
$router->add('GET', '/conta', [PageController::class, 'account']);
// Máquina do tempo, visual e modo calmo
$router->add('GET', '/maquina-do-tempo',         [PageController::class, 'time']);
$router->add('GET', '/maquina-do-tempo/exemplo', [PageController::class, 'timeDemo']); // dados fictícios
$router->add('GET', '/visual',                   [PageController::class, 'visual']);
$router->add('GET', '/guia-visual',              [PageController::class, 'styleGuide']);
// Livro da vida gamer
$router->add('GET', '/livro',         [PageController::class, 'book']);
$router->add('GET', '/livro/exemplo', [PageController::class, 'bookDemo']); // dados fictícios
// Pessoas e lugares
$router->add('GET', '/diario',         [PageController::class, 'journal']);
$router->add('GET', '/diario/exemplo', [PageController::class, 'journalDemo']); // dados fictícios
$router->add('GET', '/pessoas',         [PageController::class, 'people']);
$router->add('GET', '/pessoas/exemplo', [PageController::class, 'peopleDemo']); // dados fictícios
// Organizar nomes e estatísticas
$router->add('GET', '/organizar',         [PageController::class, 'organize']);
$router->add('GET', '/organizar/exemplo', [PageController::class, 'organizeDemo']); // dados fictícios
$router->add('GET', '/estatisticas',         [PageController::class, 'stats']);
$router->add('GET', '/estatisticas/exemplo', [PageController::class, 'statsDemo']); // dados fictícios
// Dropados
$router->add('GET', '/importar',         [PageController::class, 'import']);
$router->add('GET', '/importar/exemplo', [PageController::class, 'importDemo']); // dados fictícios
$router->add('GET', '/dropados',         [PageController::class, 'dropped']);
$router->add('GET', '/dropados/novo',    [PageController::class, 'droppedForm']);
$router->add('GET', '/dropados/exemplo', [PageController::class, 'droppedDemo']); // dados fictícios
$router->add('GET', '/dropados/{id}/editar', [PageController::class, 'droppedEdit']);
// Mapa de calor
$router->add('GET', '/calor',         [PageController::class, 'heatmap']);
$router->add('GET', '/calor/exemplo', [PageController::class, 'heatmapDemo']); // dados fictícios
// Timeline
$router->add('GET', '/timeline',         [PageController::class, 'timeline']);
$router->add('GET', '/timeline/exemplo', [PageController::class, 'timelineDemo']); // dados fictícios
// Páginas de exemplo (dados fictícios, sem login)
$router->add('GET', '/exemplos',                     [PageController::class, 'examples']);
$router->add('GET', '/exemplo/dashboard',            [PageController::class, 'dashboardDemo']);
$router->add('GET', '/exemplo/franquias',            [PageController::class, 'franchisesDemo']);
$router->add('GET', '/exemplo/franquias/{id}',       [PageController::class, 'franchiseShowDemo']);
foreach (array_keys(PageController::BROWSE) as $path) {
    $router->add('GET', '/exemplo' . $path, [PageController::class, 'browseDemo']);
}
// GOTY
$router->add('GET', '/goty',         [PageController::class, 'goty']);
$router->add('GET', '/goty/exemplo', [PageController::class, 'gotyDemo']); // página de demonstração, dados fictícios
// Comunidade
$router->add('GET', '/ranking',        [PageController::class, 'ranking']);
$router->add('GET', '/u/{username}',   [PageController::class, 'userProfile']);
// Franquias
$router->add('GET', '/franquias',             [PageController::class, 'franchises']);
$router->add('GET', '/franquias/nova',        [PageController::class, 'franchiseForm']);
$router->add('GET', '/franquias/{id}',        [PageController::class, 'franchiseShow']);
$router->add('GET', '/franquias/{id}/editar', [PageController::class, 'franchiseEdit']);
// Páginas de agrupamento (navegar por sistema, gênero, dificuldade...)
foreach (array_keys(PageController::BROWSE) as $path) {
    $router->add('GET', $path, [PageController::class, 'browse']);
}
$router->add('GET', '/login',    [PageController::class, 'login']);
$router->add('GET', '/cadastro', [PageController::class, 'register']);
$router->add('GET', '/tutorial', [PageController::class, 'tutorial']);

// Auth
$router->add('GET',  '/api/csrf',          [AuthController::class, 'csrf']);
$router->add('POST', '/api/auth/register', [AuthController::class, 'register']);
$router->add('POST', '/api/auth/login',    [AuthController::class, 'login']);
$router->add('POST', '/api/auth/logout',   [AuthController::class, 'logout']);
$router->add('GET',  '/api/me',            [AuthController::class, 'me'], auth: true);

// Perfil / XP / estatísticas
$router->add('GET', '/api/me/stats',     [MeController::class, 'stats'], auth: true);
$router->add('GET', '/api/me/xp-events', [MeController::class, 'xpEvents'], auth: true);
$router->add('GET', '/api/lookups/{type}', [MeController::class, 'lookup'], auth: true);
$router->add('GET', '/api/xp-rules',     [MeController::class, 'xpRules'], auth: true);
$router->add('GET', '/api/me/filters',   [MeController::class, 'filters'], auth: true);
$router->add('GET', '/api/me/groups/{dim}', [MeController::class, 'groups'], auth: true);

// Best of (melhor jogo de cada sistema, gênero, desenvolvedora, publisher e franquia)
$router->add('GET', '/api/best/candidates', [BestOfController::class, 'candidates'], auth: true);
$router->add('PUT', '/api/best',            [BestOfController::class, 'choose'], auth: true);
$router->add('GET', '/api/me/game-index', [MeController::class, 'gameIndex'], auth: true);
$router->add('GET', '/api/me/title-check', [MeController::class, 'titleCheck'], auth: true); // formulários: o que já existe com este título
$router->add('PUT', '/api/me/theme',     [MeController::class, 'setTheme'], auth: true);
$router->add('PUT', '/api/me/layout',    [MeController::class, 'setLayout'], auth: true);
$router->add('PUT', '/api/me/calm',      [MeController::class, 'setCalm'], auth: true);
$router->add('PUT', '/api/me/dashboard', [MeController::class, 'setDashboard'], auth: true);
$router->add('GET', '/api/dashboard',    [MeController::class, 'dashboard'], auth: true);
$router->add('GET', '/api/me/rating-quota', [MeController::class, 'ratingQuota'], auth: true);
$router->add('GET', '/api/demo/dashboard', [MeController::class, 'dashboardDemo']);
$router->add('GET', '/api/dashboard/constellation',      [MeController::class, 'constellation'], auth: true);
$router->add('GET', '/api/dashboard/soundtrack',         [MeController::class, 'dashboardSoundtrack'], auth: true);
$router->add('GET', '/api/demo/dashboard/soundtrack',    [MeController::class, 'dashboardSoundtrackDemo']);
$router->add('GET', '/api/demo/dashboard/constellation', [MeController::class, 'constellationDemo']);

$router->add('GET', '/api/timeline', [MeController::class, 'timeline'], auth: true);
$router->add('GET', '/api/heatmap',  [MeController::class, 'heatmap'], auth: true);

// Backlog (API)
$router->add('GET',    '/api/backlog',               [BacklogController::class, 'index'], auth: true);
$router->add('POST',   '/api/backlog',               [BacklogController::class, 'store'], auth: true);
$router->add('GET',    '/api/backlog/{id}',          [BacklogController::class, 'show'], auth: true);
$router->add('PUT',    '/api/backlog/{id}',          [BacklogController::class, 'update'], auth: true);
$router->add('POST',   '/api/backlog/{id}/start',    [BacklogController::class, 'start'], auth: true);
$router->add('POST',   '/api/backlog/{id}/queue',    [BacklogController::class, 'queue'], auth: true);
$router->add('PUT',    '/api/backlog/{id}/priority', [BacklogController::class, 'priority'], auth: true);
$router->add('DELETE', '/api/backlog/{id}',          [BacklogController::class, 'destroy'], auth: true);
$router->add('POST',   '/api/backlog/{id}/cover',    [BacklogController::class, 'setCover'], auth: true);
$router->add('DELETE', '/api/backlog/{id}/cover',    [BacklogController::class, 'removeCover'], auth: true);
$router->add('POST',   '/api/dropped/{id}/resume',   [DroppedController::class, 'resume'], auth: true);

// Metas (API)
$router->add('GET', '/api/goals',        [GoalController::class, 'show'], auth: true);
$router->add('PUT', '/api/goals/{year}', [GoalController::class, 'set'], auth: true);

// Conta (API)
$router->add('GET',  '/api/account',               [AccountController::class, 'show'], auth: true);
$router->add('PUT',  '/api/account/username',      [AccountController::class, 'username'], auth: true);
$router->add('POST', '/api/account/avatar',        [AccountController::class, 'avatar'], auth: true);
$router->add('GET',  '/api/account/export',        [AccountController::class, 'export'], auth: true);
$router->add('POST', '/api/account/delete',        [AccountController::class, 'delete'], auth: true);

// Dropados (API)
$router->add('POST',   '/api/import/parse',   [ImportController::class, 'parse'], auth: true);
$router->add('POST',   '/api/import/preview', [ImportController::class, 'preview'], auth: true);
$router->add('POST',   '/api/import/commit',  [ImportController::class, 'commit'], auth: true);
$router->add('GET',    '/api/import/template', [ImportController::class, 'template']);
$router->add('GET',    '/api/dropped',                [DroppedController::class, 'index'], auth: true);
$router->add('POST',   '/api/dropped',                [DroppedController::class, 'store'], auth: true);
$router->add('GET',    '/api/dropped/{id}',           [DroppedController::class, 'show'], auth: true);
$router->add('PUT',    '/api/dropped/{id}',           [DroppedController::class, 'update'], auth: true);
$router->add('PUT',    '/api/dropped/{id}/chance',    [DroppedController::class, 'chance'], auth: true);
$router->add('DELETE', '/api/dropped/{id}',           [DroppedController::class, 'destroy'], auth: true);
$router->add('POST',   '/api/dropped/{id}/cover',     [DroppedController::class, 'setCover'], auth: true);
$router->add('DELETE', '/api/dropped/{id}/cover',     [DroppedController::class, 'removeCover'], auth: true);

// Retrospectiva (API)
$router->add('GET', '/api/retrospective',              [RetrospectiveController::class, 'years'], auth: true);
$router->add('GET', '/api/retrospective/{year}',       [RetrospectiveController::class, 'show'], auth: true);
$router->add('PUT', '/api/retrospective/{year}/top',   [RetrospectiveController::class, 'setTop'], auth: true);

// GOTY (API)
$router->add('GET',    '/api/goty',        [GotyController::class, 'index'], auth: true);
$router->add('PUT',    '/api/goty/{year}', [GotyController::class, 'set'], auth: true);
$router->add('DELETE', '/api/goty/{year}', [GotyController::class, 'destroy'], auth: true);

// Comunidade (API)
$router->add('GET', '/api/ranking',           [CommunityController::class, 'ranking'], auth: true);
$router->add('GET', '/api/users/{username}',  [CommunityController::class, 'profile'], auth: true);
$router->add('PUT', '/api/me/privacy',        [CommunityController::class, 'privacy'], auth: true);

// Franquias (API)
$router->add('GET',    '/api/franchises',      [FranchiseController::class, 'index'], auth: true);
$router->add('POST',   '/api/franchises',      [FranchiseController::class, 'store'], auth: true);
$router->add('GET',    '/api/franchises/{id}', [FranchiseController::class, 'show'], auth: true);
$router->add('PUT',    '/api/franchises/{id}', [FranchiseController::class, 'update'], auth: true);
$router->add('DELETE', '/api/franchises/{id}', [FranchiseController::class, 'destroy'], auth: true);
$router->add('POST',   '/api/franchises/{id}/cover', [FranchiseController::class, 'setCover'], auth: true);
$router->add('DELETE', '/api/franchises/{id}/cover', [FranchiseController::class, 'removeCover'], auth: true);
$router->add('POST',   '/api/franchises/{id}/hero',  [FranchiseController::class, 'setHero'], auth: true);
$router->add('DELETE', '/api/franchises/{id}/hero',  [FranchiseController::class, 'removeHero'], auth: true);

// Jogos
$router->add('GET',    '/api/games',              [GameController::class, 'index'], auth: true);
$router->add('POST',   '/api/games',              [GameController::class, 'store'], auth: true);
$router->add('GET',    '/api/games/{id}/notes',   [NotesController::class, 'index'], auth: true);
$router->add('POST',   '/api/games/{id}/notes',   [NotesController::class, 'store'], auth: true);
$router->add('GET',    '/api/backlog/{id}/notes', [NotesController::class, 'index'], auth: true);
$router->add('POST',   '/api/backlog/{id}/notes', [NotesController::class, 'store'], auth: true);
$router->add('GET',    '/api/games/{id}/shots',   [ShotController::class, 'index'], auth: true);
$router->add('POST',   '/api/games/{id}/shots',   [ShotController::class, 'store'], auth: true);
$router->add('GET',    '/api/backlog/{id}/shots', [ShotController::class, 'index'], auth: true);
$router->add('POST',   '/api/backlog/{id}/shots', [ShotController::class, 'store'], auth: true);
$router->add('GET',    '/api/dropped/{id}/notes', [NotesController::class, 'index'], auth: true);
$router->add('POST',   '/api/dropped/{id}/notes', [NotesController::class, 'store'], auth: true);
$router->add('GET',    '/api/dropped/{id}/shots', [ShotController::class, 'index'], auth: true);
$router->add('POST',   '/api/dropped/{id}/shots', [ShotController::class, 'store'], auth: true);
$router->add('PUT',    '/api/shots/{id}',         [ShotController::class, 'update'], auth: true);
$router->add('DELETE', '/api/shots/{id}',         [ShotController::class, 'destroy'], auth: true);
$router->add('PUT',    '/api/notes/{id}',         [NotesController::class, 'update'], auth: true);
$router->add('DELETE', '/api/notes/{id}',         [NotesController::class, 'destroy'], auth: true);
$router->add('GET',    '/api/time/snapshot',      [TimeController::class, 'snapshot'], auth: true);
$router->add('GET',    '/api/time/on-this-day',   [TimeController::class, 'onThisDay'], auth: true);
$router->add('GET',    '/api/demo/time',          [TimeController::class, 'demo']);
$router->add('GET',    '/api/book',               [BookController::class, 'show'], auth: true);
$router->add('GET',    '/api/demo/book',          [BookController::class, 'demo']);
$router->add('GET',    '/api/circles',            [NotesController::class, 'circles'], auth: true);
$router->add('GET',    '/api/journal',              [JournalController::class, 'index'], auth: true);
$router->add('GET',    '/api/demo/journal',         [JournalController::class, 'demo']);
$router->add('POST',   '/api/journal/entries',      [JournalController::class, 'store'], auth: true);
$router->add('PUT',    '/api/journal/entries/{id}', [JournalController::class, 'update'], auth: true);
$router->add('DELETE', '/api/journal/entries/{id}', [JournalController::class, 'destroy'], auth: true);
$router->add('POST',   '/api/games/bulk',         [GameController::class, 'bulk'], auth: true);
$router->add('GET',    '/api/names/{kind}',       [NamesController::class, 'index'], auth: true);
$router->add('POST',   '/api/names/{kind}/merge', [NamesController::class, 'merge'], auth: true);
$router->add('GET',    '/api/games/{id}',         [GameController::class, 'show'], auth: true);
$router->add('PUT',    '/api/games/{id}',         [GameController::class, 'update'], auth: true);
$router->add('DELETE', '/api/games/{id}',         [GameController::class, 'destroy'], auth: true);
$router->add('POST',   '/api/games/{id}/cover',   [GameController::class, 'setCover'], auth: true);
$router->add('DELETE', '/api/games/{id}/cover',   [GameController::class, 'removeCover'], auth: true);
