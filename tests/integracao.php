<?php
// Executar somente contra uma instalação descartável cujo banco termine em _teste.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../includes/conexao.php';
require __DIR__ . '/../includes/produtos.php';
$base = getenv('LOJA_TESTE_URL');
if (!$base || !preg_match('~\Ahttp://(?:127\.0\.0\.1|localhost):[0-9]+/(?:Loja-Mobile/)?\z~', $base)) {
    exit('Defina LOJA_TESTE_URL com a URL local do ambiente descartável.' . PHP_EOL);
}
$pdo = conectar_banco();
if (!str_ends_with($pdo->query('SELECT DATABASE()')->fetchColumn(), '_teste')) {
    exit('O banco configurado deve terminar em _teste. Nenhum teste executado.' . PHP_EOL);
}
$prefixo = 'TESTE-' . bin2hex(random_bytes(5));
$senha = bin2hex(random_bytes(16));
$usuarios = [];
$produtos = [];
$pasta = __DIR__ . '/fixtures';
$temporarios = [];

function conferir(bool $resultado, string $descricao): void
{
    if (!$resultado) { throw new RuntimeException('FALHOU: ' . $descricao); }
    echo 'OK: ' . $descricao . PHP_EOL;
}
function navegador(): CurlHandle
{
    $curl = curl_init();
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_COOKIEFILE => '', CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 15]);
    return $curl;
}
function abrir(CurlHandle $curl, string $pagina, ?array $dados = null, bool $multipart = false): array
{
    global $base;
    curl_setopt($curl, CURLOPT_URL, $base . $pagina);
    if ($dados === null) { curl_setopt($curl, CURLOPT_HTTPGET, true); }
    else { curl_setopt($curl, CURLOPT_POSTFIELDS, $multipart ? $dados : http_build_query($dados)); }
    $resposta = curl_exec($curl);
    if ($resposta === false) { throw new RuntimeException(curl_error($curl)); }
    $cabecalho = substr($resposta, 0, curl_getinfo($curl, CURLINFO_HEADER_SIZE));
    $html = substr($resposta, curl_getinfo($curl, CURLINFO_HEADER_SIZE));
    preg_match('/^Location: (.+)$/mi', $cabecalho, $destino);
    preg_match('/name="csrf" value="([a-f0-9]+)"/', $html, $csrf);
    preg_match('/name="checkout_token" value="([a-f0-9]+)"/', $html, $chave);
    return ['status' => curl_getinfo($curl, CURLINFO_HTTP_CODE), 'html' => $html, 'headers' => $cabecalho, 'destino' => trim($destino[1] ?? ''), 'csrf' => $csrf[1] ?? '', 'chave' => $chave[1] ?? ''];
}
function cadastrar(CurlHandle $curl, string $email, string $perfil = 'cliente'): int
{
    global $senha, $pdo;
    $pagina = abrir($curl, 'cadastro.php');
    $pagina = abrir($curl, 'cadastro.php', ['csrf' => $pagina['csrf'], 'nome' => '<b>Cliente de Teste</b>', 'email' => $email, 'senha' => $senha, 'confirmacao_senha' => $senha, 'perfil' => $perfil]);
    conferir($pagina['status'] === 303, 'cadastro válido redireciona ao login');
    $consulta = $pdo->prepare('SELECT id, senha, perfil FROM usuarios WHERE email=?');
    $consulta->execute([$email]);
    $usuario = $consulta->fetch();
    conferir($usuario['perfil'] === $perfil && password_verify($senha, $usuario['senha']) && $senha !== $usuario['senha'], 'cadastro grava o perfil permitido e armazena hash');
    return (int) $usuario['id'];
}
function entrar(CurlHandle $curl, string $email): array
{
    global $senha;
    $pagina = abrir($curl, 'login.php');
    return abrir($curl, 'login.php', ['csrf' => $pagina['csrf'], 'email' => $email, 'senha' => $senha]);
}
function adicionar(CurlHandle $curl, int $id, int $quantidade): array
{
    $pagina = abrir($curl, 'produto.php?id=' . $id);
    return abrir($curl, 'carrinho.php', ['csrf' => $pagina['csrf'], 'acao' => 'adicionar', 'produto_id' => $id, 'quantidade' => $quantidade, 'preco' => '0.01']);
}

try {
    $visitante = navegador();
    conferir(abrir($visitante, '')['status'] === 200, 'catálogo público responde no Apache');
    conferir(abrir($visitante, 'produto.php?id=' . rawurlencode("1' OR 1=1"))['status'] === 404, 'ID com SQL Injection não acessa produto');
    conferir(abrir($visitante, 'admin/index.php')['status'] === 303 && abrir($visitante, 'admin/produto.php')['status'] === 303, 'visitante não acessa administração');
    conferir(abrir($visitante, 'checkout.php')['status'] === 303, 'checkout exige login');
    foreach (['.git/HEAD', '.validation/preparar.php', 'database/schema.sql', 'includes/conexao.php', 'config/banco.php', 'tests/integracao.php', 'assets/uploads/'] as $restrito) {
        conferir(abrir($visitante, $restrito)['status'] === 403, 'Apache bloqueia acesso interno: ' . $restrito);
    }
    $admin = navegador();
    $email_admin = strtolower($prefixo) . '-admin@example.invalid';
    $admin_id = cadastrar($admin, $email_admin, 'admin'); $usuarios[] = $admin_id;
    conferir(str_ends_with(entrar($admin, $email_admin)['destino'], '/admin/index.php'), 'login de admin direciona ao painel');
    conferir(abrir($admin, 'checkout.php')['status'] === 403, 'admin não finaliza como cliente');
    $pagina = abrir($admin, 'admin/produto.php');
    $dados = ['csrf' => $pagina['csrf'], 'codigo' => $prefixo, 'marca' => 'Teste', 'modelo' => '<script>alert(1)</script>', 'descricao' => 'Descrição de teste <b>segura</b>', 'quantidade_estoque' => '5', 'preco' => '19,90'];
    $falsa = tempnam(sys_get_temp_dir(), 'loja-falsa-'); $temporarios[] = $falsa;
    $grande = tempnam(sys_get_temp_dir(), 'loja-grande-'); $temporarios[] = $grande;
    file_put_contents($falsa, '<?php echo "EXECUTADO";');
    file_put_contents($grande, file_get_contents($pasta . '/imagem.png') . str_repeat('x', 2 * 1024 * 1024));
    $pagina = abrir($admin, 'admin/produto.php', $dados);
    conferir(str_contains($pagina['html'], 'Selecione uma imagem'), 'imagem obrigatória no cadastro');
    foreach (['falsa.png' => $falsa, 'grande.png' => $grande] as $arquivo => $caminho) {
        $pagina = abrir($admin, 'admin/produto.php', $dados + ['imagem' => new CURLFile($caminho, 'image/png', $arquivo)], true);
        conferir($pagina['status'] !== 303 && str_contains($pagina['html'], 'mensagem-erro'), 'upload inválido rejeitado: ' . $arquivo);
    }
    $pagina = abrir($admin, 'admin/produto.php', $dados + ['imagem' => new CURLFile($pasta . '/imagem.png', 'image/png', 'imagem.php')], true);
    conferir($pagina['status'] !== 303, 'extensão executável rejeitada mesmo com MIME de imagem');
    $pagina = abrir($admin, 'admin/produto.php', array_replace($dados, ['csrf' => 'invalido', 'imagem' => new CURLFile($pasta . '/imagem.png')]), true);
    conferir($pagina['status'] === 403, 'CRUD exige CSRF');
    foreach ([['preco' => '0'], ['preco' => '-1'], ['quantidade_estoque' => '-1'], ['codigo' => '']] as $alteracao) {
        $pagina = abrir($admin, 'admin/produto.php', array_replace($dados, $alteracao, ['imagem' => new CURLFile($pasta . '/imagem.png')]), true);
        conferir(str_contains($pagina['html'], 'mensagem-erro') && $pagina['status'] !== 303, 'validação de produto: ' . implode(',', array_keys($alteracao)));
    }
    $pagina = abrir($admin, 'admin/produto.php', $dados + ['imagem' => new CURLFile($pasta . '/imagem.png')], true);
    conferir($pagina['status'] === 303, 'cadastro de produto com PNG real');
    $consulta = $pdo->prepare('SELECT * FROM produtos WHERE codigo=?'); $consulta->execute([$prefixo]); $produto = $consulta->fetch();
    $id = (int) $produto['id']; $produtos[] = $id;
    conferir((bool) preg_match('~assets/uploads/[a-f0-9]{32}\.png\z~', $produto['imagem']), 'upload recebe nome aleatório seguro');
    conferir(abrir($visitante, $produto['imagem'])['status'] === 200, 'imagem enviada é servida pelo Apache');
    $pagina = abrir($visitante, 'produto.php?id=' . $id);
    conferir(!str_contains($pagina['html'], '<script>') && str_contains($pagina['html'], '&lt;script&gt;'), 'produto escapa XSS em título e descrição');
    $pagina = abrir($admin, 'admin/produto.php', $dados + ['imagem' => new CURLFile($pasta . '/imagem.png')], true);
    conferir(str_contains($pagina['html'], 'código de produto já está cadastrado'), 'código duplicado rejeitado');
    $imagem_anterior = $produto['imagem'];
    $dados['modelo'] = 'Celular de Teste';
    foreach (['jpg', 'webp'] as $extensao) {
        $pagina = abrir($admin, 'admin/produto.php?id=' . $id, $dados + ['imagem' => new CURLFile($pasta . '/imagem.' . $extensao)], true);
        conferir($pagina['status'] === 303 && !is_file(__DIR__ . '/../' . $imagem_anterior), 'substituição de imagem por ' . $extensao . ' remove a anterior');
        $consulta->execute([$prefixo]); $imagem_anterior = $consulta->fetch()['imagem'];
    }
    $pagina = abrir($admin, 'admin/produto.php?id=' . $id, array_replace($dados, ['preco' => '20.10']));
    conferir($pagina['status'] === 303 && is_file(__DIR__ . '/../' . $imagem_anterior), 'edição sem upload preserva a imagem');
    $cliente = navegador(); $email = strtolower($prefixo) . '@example.invalid';
    $cliente_id = cadastrar($cliente, $email); $usuarios[] = $cliente_id;
    $pagina = abrir($cliente, 'cadastro.php');
    $pagina = abrir($cliente, 'cadastro.php', ['csrf' => $pagina['csrf'], 'nome' => 'Teste', 'email' => $email, 'senha' => $senha, 'confirmacao_senha' => 'diferente']);
    conferir(str_contains($pagina['html'], 'confirmação de senha deve ser igual') && !str_contains($pagina['html'], $senha), 'confirmação divergente é rejeitada sem reapresentar senha');
    $pagina = abrir($cliente, 'cadastro.php', ['csrf' => $pagina['csrf'], 'nome' => 'Teste', 'email' => $email, 'senha' => $senha, 'confirmacao_senha' => $senha]);
    conferir(str_contains($pagina['html'], 'e-mail já está cadastrado'), 'e-mail duplicado rejeitado');
    $pagina = abrir($cliente, 'login.php');
    $pagina = abrir($cliente, 'login.php', ['csrf' => $pagina['csrf'], 'email' => "x' OR 1=1 --", 'senha' => $senha]);
    conferir(str_contains($pagina['html'], 'E-mail ou senha incorretos.'), 'SQL Injection no login não autentica');
    $pagina = abrir($cliente, 'login.php', ['csrf' => $pagina['csrf'], 'email' => $email, 'senha' => 'incorreta']);
    conferir(str_contains($pagina['html'], 'E-mail ou senha incorretos.'), 'senha incorreta usa mensagem genérica');
    conferir(adicionar($cliente, $id, 2)['status'] === 303, 'visitante adiciona produto ao carrinho');
    conferir(entrar($cliente, $email)['status'] === 303 && str_contains(abrir($cliente, 'carrinho.php')['html'], 'R$ 40,20'), 'login preserva carrinho e usa preço atual do banco, ignorando preço enviado');
    conferir(abrir($cliente, 'admin/index.php')['status'] === 403 && abrir($cliente, 'admin/produto.php')['status'] === 403 && abrir($cliente, 'admin/excluir-produto.php', ['id' => $id])['status'] === 403, 'cliente não acessa nem altera produtos');
    $pagina = abrir($cliente, 'carrinho.php');
    conferir(abrir($cliente, 'carrinho.php', ['csrf' => 'invalido', 'acao' => 'remover', 'produto_id' => $id])['status'] === 403, 'carrinho rejeita CSRF');
    $csrf = $pagina['csrf'];
    $pagina = abrir($cliente, 'carrinho.php', ['csrf' => $csrf, 'acao' => 'atualizar', 'produto_id' => $id, 'quantidade' => 3]);
    conferir($pagina['status'] === 303 && str_contains(abrir($cliente, 'carrinho.php')['html'], 'R$ 60,30'), 'atualização de quantidade recalcula subtotal e total em centavos');
    $pagina = abrir($cliente, 'carrinho.php', ['csrf' => $csrf, 'acao' => 'atualizar', 'produto_id' => $id, 'quantidade' => 6]);
    conferir(str_contains($pagina['html'], 'superior ao estoque') && str_contains($pagina['html'], 'R$ 60,30'), 'quantidade superior ao estoque rejeitada sem alterar carrinho');
    $pagina = abrir($cliente, 'checkout.php'); $confirmacao = ['csrf' => $pagina['csrf'], 'checkout_token' => $pagina['chave']];
    conferir(abrir($cliente, 'checkout.php', array_replace($confirmacao, ['csrf' => 'invalido']))['status'] === 403, 'checkout rejeita CSRF');
    $pdo->prepare('UPDATE produtos SET preco=21.10 WHERE id=?')->execute([$id]);
    $pagina = abrir($cliente, 'checkout.php', $confirmacao);
    conferir($pagina['status'] === 409 && str_contains($pagina['html'], 'preços ou itens foram alterados'), 'preço alterado exige nova revisão e não cria pedido');
    $confirmacao = ['csrf' => $pagina['csrf'], 'checkout_token' => $pagina['chave']];
    $pagina = abrir($cliente, 'checkout.php', $confirmacao);
    conferir($pagina['status'] === 303 && str_contains($pagina['destino'], 'pedido.php?id='), 'checkout completo confirma pedido');
    preg_match('/id=([0-9]+)/', $pagina['destino'], $numero); $pedido_id = (int) $numero[1];
    $consulta_pedido = $pdo->prepare('SELECT * FROM pedidos WHERE id=?'); $consulta_pedido->execute([$pedido_id]); $pedido = $consulta_pedido->fetch();
    conferir($pedido['total'] === '63.30' && $pedido['status'] === 'confirmado', 'pedido persiste total correto e status confirmado');
    $consulta_item = $pdo->prepare('SELECT * FROM pedido_itens WHERE pedido_id=?'); $consulta_item->execute([$pedido_id]); $item = $consulta_item->fetch();
    conferir((int) $item['quantidade'] === 3 && $item['preco_unitario'] === '21.10', 'item guarda quantidade e preço do momento da compra');
    conferir((int) buscar_produto($pdo, $id)['quantidade_estoque'] === 2, 'estoque reduz exatamente a quantidade comprada');
    conferir(str_contains(abrir($cliente, 'carrinho.php')['html'], 'carrinho ainda está vazio'), 'carrinho limpo somente após o commit');
    conferir(abrir($cliente, 'checkout.php', $confirmacao)['destino'] === $pagina['destino'], 'reenvio da confirmação retorna o mesmo pedido');
    $consulta = $pdo->prepare('SELECT COUNT(*) FROM pedidos WHERE usuario_id=?'); $consulta->execute([$cliente_id]);
    conferir((int) $consulta->fetchColumn() === 1 && (int) buscar_produto($pdo, $id)['quantidade_estoque'] === 2, 'reenvio não duplica pedido nem baixa estoque novamente');
    conferir(abrir($cliente, 'pedido.php?id=' . $pedido_id)['status'] === 200 && abrir($admin, 'pedido.php?id=' . $pedido_id)['status'] === 404, 'confirmação acessível somente ao proprietário');
    $pagina = abrir($admin, 'admin/index.php');
    conferir(str_contains($pagina['html'], '#'.$pedido_id) && str_contains($pagina['html'], 'R$ 63,30'), 'painel lista pedido real com cliente, valor e status');
    conferir(abrir($admin, 'admin/excluir-produto.php', ['csrf' => $pagina['csrf'], 'id' => $id])['status'] === 409 && buscar_produto($pdo, $id) !== null, 'exclusão de produto vendido bloqueada por integridade referencial');
    adicionar($cliente, $id, 1);
    $pagina = abrir($cliente, 'checkout.php'); $confirmacao = ['csrf' => $pagina['csrf'], 'checkout_token' => $pagina['chave']];
    $pdo->prepare('UPDATE produtos SET quantidade_estoque=0 WHERE id=?')->execute([$id]);
    $pagina = abrir($cliente, 'checkout.php', $confirmacao);
    conferir($pagina['status'] === 409 && (int) buscar_produto($pdo, $id)['quantidade_estoque'] === 0, 'estoque esgotado entre revisão e compra causa rollback');
    $consulta->execute([$cliente_id]); conferir((int) $consulta->fetchColumn() === 1, 'falha de estoque não deixa pedido parcial');
    conferir(!str_contains(abrir($cliente, 'produto.php?id=' . $id)['html'], '>Adicionar ao carrinho') && str_contains(abrir($cliente, 'produto.php?id=' . $id)['html'], 'sem estoque'), 'produto sem estoque não oferece compra');
    $pagina = abrir($cliente, 'carrinho.php');
    conferir(abrir($cliente, 'carrinho.php', ['csrf' => $pagina['csrf'], 'acao' => 'remover', 'produto_id' => $id])['status'] === 303, 'item indisponível pode ser removido');
    $pagina = abrir($cliente, '');
    conferir(abrir($cliente, 'logout.php')['status'] === 405 && abrir($cliente, 'logout.php', ['csrf' => 'invalido'])['status'] === 403, 'logout exige POST e CSRF');
    conferir(abrir($cliente, 'logout.php', ['csrf' => $pagina['csrf']])['status'] === 303 && abrir($cliente, 'checkout.php')['status'] === 303, 'logout encerra acesso autenticado');

    // Produto sem vendas pode ser removido junto à imagem.
    $dados['codigo'] = $prefixo . '-EXCLUIR';
    $pagina = abrir($admin, 'admin/produto.php', $dados + ['imagem' => new CURLFile($pasta . '/imagem.png')], true);
    $consulta = $pdo->prepare('SELECT * FROM produtos WHERE codigo=?'); $consulta->execute([$dados['codigo']]); $descartavel = $consulta->fetch(); $produtos[] = (int) $descartavel['id'];
    $pagina = abrir($admin, 'admin/index.php');
    conferir(abrir($admin, 'admin/excluir-produto.php', ['csrf' => $pagina['csrf'], 'id' => $descartavel['id']])['status'] === 303 && !is_file(__DIR__ . '/../' . $descartavel['imagem']) && buscar_produto($pdo, (int) $descartavel['id']) === null, 'exclusão de produto sem pedidos remove registro e arquivo');
    echo 'TODOS OS TESTES HTTP PASSARAM' . PHP_EOL;
} finally {
    foreach ([$admin ?? null, $cliente ?? null, $visitante ?? null] as $navegador_teste) {
        if ($navegador_teste instanceof CurlHandle) { curl_reset($navegador_teste); }
    }
    // Remove exclusivamente os registros e arquivos criados por esta execução.
    foreach ($usuarios as $usuario) {
        $pdo->prepare('DELETE i FROM pedido_itens i JOIN pedidos p ON p.id=i.pedido_id WHERE p.usuario_id=?')->execute([$usuario]);
        $pdo->prepare('DELETE FROM pedidos WHERE usuario_id=?')->execute([$usuario]);
    }
    foreach ($produtos as $produto_id) {
        $produto = buscar_produto($pdo, $produto_id);
        if ($produto) { remover_imagem_enviada($produto['imagem']); $pdo->prepare('DELETE FROM produtos WHERE id=?')->execute([$produto_id]); }
    }
    foreach ($usuarios as $usuario) { $pdo->prepare('DELETE FROM usuarios WHERE id=?')->execute([$usuario]); }
    foreach ($temporarios as $arquivo) { unlink($arquivo); }
}
