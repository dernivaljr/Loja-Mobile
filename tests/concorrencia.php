<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../includes/carrinho.php';
$pdo = conectar_banco();
if (!str_ends_with($pdo->query('SELECT DATABASE()')->fetchColumn(), '_teste')) {
    exit('Use um banco descartável terminado em _teste.' . PHP_EOL);
}
$pasta = str_replace('\\', '/', sys_get_temp_dir()) . '/loja-concorrencia-' . bin2hex(random_bytes(5));
mkdir($pasta);
$codigo = 'CONCORRENCIA-' . bin2hex(random_bytes(6));
$usuarios = [];
$produto_id = null;
function conferir(bool $ok, string $texto): void
{
    if (!$ok) { throw new RuntimeException('FALHOU: ' . $texto); }
    echo 'OK: ' . $texto . PHP_EOL;
}
function executar_compras(array $trabalhos): array
{
    global $pasta;
    $processos = [];
    $inicio = microtime(true) + .5;
    foreach ($trabalhos as $indice => $trabalho) {
        $arquivo = $pasta . '/job-' . $indice . '.json';
        file_put_contents($arquivo, json_encode($trabalho + ['inicio' => $inicio]));
        $processo = proc_open([PHP_BINARY, __DIR__ . '/compra-concorrente.php', $arquivo], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($processo)) { throw new RuntimeException('Não foi possível iniciar worker.'); }
        fclose($pipes[0]);
        $processos[] = [$processo, $pipes];
    }
    $resultados = [];
    foreach ($processos as [$processo, $pipes]) {
        $saida = stream_get_contents($pipes[1]); fclose($pipes[1]);
        $erros = stream_get_contents($pipes[2]); fclose($pipes[2]);
        $status = proc_close($processo);
        conferir($status === 0 && $erros === '', 'worker concorrente finaliza sem erro interno');
        $resultados[] = json_decode($saida, true, 512, JSON_THROW_ON_ERROR);
    }
    return $resultados;
}
try {
    for ($i = 0; $i < 2; $i++) {
        $pdo->prepare("INSERT INTO usuarios (nome, email, senha, perfil) VALUES ('Teste concorrente', ?, ?, 'cliente')")->execute([$codigo . '-' . $i . '@example.invalid', password_hash(bin2hex(random_bytes(12)), PASSWORD_DEFAULT)]);
        $usuarios[] = (int) $pdo->lastInsertId();
    }
    $pdo->prepare("INSERT INTO produtos (codigo, marca, modelo, descricao, quantidade_estoque, preco, imagem) VALUES (?, 'Teste', 'Última unidade', 'Teste isolado', 1, 10.01, 'assets/img/celular-padrao.svg')")->execute([$codigo]);
    $produto_id = (int) $pdo->lastInsertId();
    $carrinho = [$produto_id => 1];
    $assinatura = assinatura_carrinho(consultar_carrinho($pdo, $carrinho));
    $trabalhos = [];
    foreach ($usuarios as $usuario) { $trabalhos[] = ['usuario' => $usuario, 'carrinho' => $carrinho, 'chave' => bin2hex(random_bytes(32)), 'assinatura' => $assinatura]; }
    $resultados = executar_compras($trabalhos);
    conferir(count(array_filter($resultados, fn($r) => $r['ok'])) === 1, 'somente uma de duas compras da última unidade é confirmada');
    conferir((int) buscar_produto($pdo, $produto_id)['quantidade_estoque'] === 0, 'estoque concorrente termina em zero, nunca negativo');
    $consulta = $pdo->prepare('SELECT COUNT(*) FROM pedido_itens WHERE produto_id=?'); $consulta->execute([$produto_id]);
    conferir((int) $consulta->fetchColumn() === 1, 'somente um item de pedido persiste na disputa');

    $pdo->prepare('UPDATE produtos SET quantidade_estoque=3 WHERE id=?')->execute([$produto_id]);
    $chave = bin2hex(random_bytes(32));
    $trabalho = ['usuario' => $usuarios[0], 'carrinho' => $carrinho, 'chave' => $chave, 'assinatura' => $assinatura];
    $resultados = executar_compras([$trabalho, $trabalho]);
    conferir($resultados[0]['ok'] && $resultados[1]['ok'] && $resultados[0]['id'] === $resultados[1]['id'], 'confirmações simultâneas com a mesma chave retornam o mesmo pedido');
    conferir((int) buscar_produto($pdo, $produto_id)['quantidade_estoque'] === 2, 'confirmação duplicada reduz estoque somente uma vez');

    $pedido_id = $resultados[0]['id'];
    // Força uma falha depois do INSERT de pedido para comprovar rollback integral.
    $pdo->exec("CREATE TRIGGER teste_falha_item BEFORE INSERT ON pedido_itens FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Falha induzida de teste'");
    try {
        $falhou = false;
        try { confirmar_pedido($pdo, $usuarios[0], $carrinho, bin2hex(random_bytes(32)), $assinatura); }
        catch (PDOException $erro) { $falhou = true; }
        conferir($falhou && !$pdo->inTransaction(), 'falha SQL induzida encerra a transação por rollback');
        $consulta = $pdo->prepare('SELECT COUNT(*) FROM pedido_itens WHERE produto_id=?'); $consulta->execute([$produto_id]);
        conferir((int) $consulta->fetchColumn() === 2 && (int) buscar_produto($pdo, $produto_id)['quantidade_estoque'] === 2, 'rollback não deixa pedido parcial nem altera estoque');
        $consulta = $pdo->prepare('SELECT COUNT(*) FROM pedidos WHERE usuario_id IN (?, ?)'); $consulta->execute($usuarios);
        conferir((int) $consulta->fetchColumn() === 2, 'pedido criado antes da falha foi revertido');
    } finally { $pdo->exec('DROP TRIGGER IF EXISTS teste_falha_item'); }
    echo 'TESTES DE CONCORRÊNCIA E ROLLBACK PASSARAM' . PHP_EOL;
} finally {
    foreach ($usuarios as $usuario) {
        $pdo->prepare('DELETE i FROM pedido_itens i JOIN pedidos p ON p.id=i.pedido_id WHERE p.usuario_id=?')->execute([$usuario]);
        $pdo->prepare('DELETE FROM pedidos WHERE usuario_id=?')->execute([$usuario]);
    }
    if ($produto_id) { $pdo->prepare('DELETE FROM produtos WHERE id=?')->execute([$produto_id]); }
    foreach ($usuarios as $usuario) { $pdo->prepare('DELETE FROM usuarios WHERE id=?')->execute([$usuario]); }
    foreach (glob($pasta . '/*') as $arquivo) { unlink($arquivo); }
    rmdir($pasta);
}
