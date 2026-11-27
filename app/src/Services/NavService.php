<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Mapa de navegação do site: uma lista só, usada pelo menu em tela cheia e pela faixa "onde você está"
 * (partials/crumb.php). Ao criar uma página nova, é aqui que ela entra.
 * Itens sem "href" são ações do menu (temas, sons, sair). "{me}" no href vira o nome do usuário.
 */
final class NavService
{
    public static function groups(): array
    {
        return [
            ['id' => 'main', 'title' => 'Principal', 'items' => [
                ['id' => 'dashboard', 'label' => 'Painel', 'desc' => 'Sua página inicial', 'href' => '/', 'k' => 'inicio home painel dashboard'],
                ['id' => 'library', 'label' => 'Catálogo', 'desc' => 'Todos os jogos zerados', 'href' => '/jogos', 'k' => 'jogos zerados lista colecao meus jogos'],
                ['id' => 'add', 'label' => 'Adicionar jogo', 'desc' => 'Registrar um jogo zerado', 'href' => '/jogos/novo', 'k' => 'novo zerei cadastrar registrar'],
                ['id' => 'backlog', 'label' => 'Backlog', 'desc' => 'Quero jogar e jogando agora', 'href' => '/backlog', 'k' => 'fila jogando quero jogar'],
                ['id' => 'dropped', 'label' => 'Dropados', 'desc' => 'Largados no meio do caminho', 'href' => '/dropados', 'k' => 'abandonados larguei'],
                ['id' => 'franchises', 'label' => 'Franquias', 'desc' => 'Sagas e coleções', 'href' => '/franquias', 'k' => 'series serie colecao saga'],
                ['id' => 'profile', 'label' => 'Meu perfil', 'desc' => 'Como os amigos te veem', 'href' => '/u/{me}', 'k' => 'perfil publico amigos'],
                ['id' => 'ranking', 'label' => 'Ranking', 'desc' => 'Quem está no topo', 'href' => '/ranking', 'k' => 'amigos placar'],
            ]],
            ['id' => 'history', 'title' => 'Análises e história', 'items' => [
                ['id' => 'journal', 'label' => 'Diário', 'desc' => 'Tudo o que aconteceu, dia a dia', 'href' => '/diario', 'k' => 'historico anotacoes notas registro linha do tempo diario de bordo'],
                ['id' => 'stats', 'label' => 'Estatísticas', 'desc' => 'Recordes e curiosidades', 'href' => '/estatisticas', 'k' => 'numeros recordes graficos'],
                ['id' => 'time', 'label' => 'Máquina do tempo', 'desc' => 'Seu catálogo em outra data', 'href' => '/maquina-do-tempo', 'k' => 'passado data historico'],
                ['id' => 'timeline', 'label' => 'Timeline', 'desc' => 'Seus jogos no tempo', 'href' => '/timeline', 'k' => 'linha do tempo'],
                ['id' => 'heat', 'label' => 'Mapa de calor', 'desc' => 'Seus dias de jogo', 'href' => '/calor', 'k' => 'calendario dias'],
                ['id' => 'retro', 'label' => 'Retrospectiva', 'desc' => 'Seu ano em jogos', 'href' => '/retrospectiva', 'k' => 'resumo do ano wrapped'],
                ['id' => 'goty', 'label' => 'GOTY', 'desc' => 'Melhor jogo de cada ano', 'href' => '/goty', 'k' => 'jogo do ano melhor'],
                ['id' => 'goals', 'label' => 'Metas do ano', 'desc' => 'Seu ritmo até a meta', 'href' => '/metas', 'k' => 'objetivo meta'],
                ['id' => 'book', 'label' => 'Livro da vida gamer', 'desc' => 'Para imprimir ou salvar em PDF', 'href' => '/livro', 'k' => 'imprimir pdf historia'],
            ]],
            ['id' => 'browse', 'title' => 'Navegar por', 'items' => [
                ['id' => 'platform', 'label' => 'Sistemas', 'desc' => 'Por sistema', 'href' => '/sistemas', 'k' => 'console plataforma'],
                ['id' => 'genre', 'label' => 'Gêneros', 'desc' => 'Por gênero', 'href' => '/generos', 'k' => 'genero tipo'],
                ['id' => 'year', 'label' => 'Lançamento', 'desc' => 'Por ano de lançamento', 'href' => '/ano-de-lancamento', 'k' => 'ano lancamento'],
                ['id' => 'rating', 'label' => 'Notas', 'desc' => 'Da 11 à 1, com a cota de notas altas', 'href' => '/notas', 'k' => 'nota avaliacao 10 11 lendario'],
                ['id' => 'difficulty', 'label' => 'Dificuldades', 'desc' => 'De C até AAA', 'href' => '/dificuldades', 'k' => 'dificil facil'],
                ['id' => 'developer', 'label' => 'Desenvolvedoras', 'desc' => 'Quem fez os jogos', 'href' => '/desenvolvedoras', 'k' => 'estudio empresa'],
                ['id' => 'publisher', 'label' => 'Publishers', 'desc' => 'Quem publicou', 'href' => '/publishers', 'k' => 'distribuidora empresa'],
                ['id' => 'people', 'label' => 'Pessoas e lugares', 'desc' => 'Com quem e onde jogou', 'href' => '/pessoas', 'k' => 'amigos memorias lugar'],
            ]],
            ['id' => 'settings', 'title' => 'Configurações', 'items' => [
                ['id' => 'themes', 'label' => 'Temas', 'desc' => 'Mude as cores do site', 'k' => 'cores aparencia visual'],
                ['id' => 'visual', 'label' => 'Visual e modo calmo', 'desc' => 'Visual do catálogo e menos números', 'href' => '/visual', 'k' => 'calmo estante terminal layout'],
                ['id' => 'organize', 'label' => 'Organizar nomes', 'desc' => 'Juntar variações de nomes', 'href' => '/organizar', 'k' => 'duplicados renomear juntar'],
                ['id' => 'import', 'label' => 'Importar jogos', 'desc' => 'De uma planilha ou backup', 'href' => '/importar', 'k' => 'planilha csv excel xlsx backup'],
                ['id' => 'account', 'label' => 'Minha conta', 'desc' => 'Imagem, nome e seus dados', 'href' => '/conta', 'k' => 'usuario imagem foto nome exportar excluir'],
                ['id' => 'guide', 'label' => 'Guia visual', 'desc' => 'As peças do site no tema atual', 'href' => '/guia-visual', 'k' => 'design cores botoes componentes estilo'],
                ['id' => 'tutorial', 'label' => 'Como usar', 'desc' => 'Um tour pelo programa', 'href' => '/tutorial', 'k' => 'tutorial ajuda ensinar tour comecar novo'],
                ['id' => 'sound', 'label' => 'Sons do menu', 'desc' => 'Liga e desliga os efeitos', 'k' => 'audio som'],
                ['id' => 'examples', 'label' => 'Exemplos', 'desc' => 'Páginas com dados fictícios', 'href' => '/exemplos', 'k' => 'demonstracao demo'],
                ['id' => 'logout', 'label' => 'Sair', 'desc' => 'Trocar de usuário', 'k' => 'logout deslogar trocar usuario'],
            ]],
        ];
    }

    /** O item do mapa que corresponde ao caminho (já sem o prefixo do site): [grupo, item] ou null. */
    public static function locate(string $path): ?array
    {
        $path = '/' . trim((string) parse_url($path, PHP_URL_PATH), '/');
        $path = preg_replace('#/exemplo$#', '', $path) ?: '/';
        $path = preg_replace('#^/exemplo(?=/|$)#', '', $path) ?: '/';
        $aliases = [
            '#^/jogos/novo$#' => 'add', '#^/jogos(/\d+(/editar)?)?$#' => 'library',
            '#^/backlog(/.*)?$#' => 'backlog', '#^/dropados(/.*)?$#' => 'dropped', '#^/franquias(/.*)?$#' => 'franchises',
            '#^/u/[^/]+$#' => 'profile', '#^/dashboard$#' => 'dashboard',
        ];
        $want = null;
        foreach ($aliases as $re => $id) {
            if (preg_match($re, $path)) {
                $want = $id;
                break;
            }
        }
        foreach (self::groups() as $g) {
            foreach ($g['items'] as $it) {
                if (!isset($it['href'])) {
                    continue;
                }
                if ($want !== null ? $it['id'] === $want : $it['href'] === $path) {
                    return [$g, $it];
                }
            }
        }
        return null;
    }
}
