<?php
// Worker CLI usado exclusivamente pelo teste de concorrência.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../includes/carrinho.php';
$conexao = conectar_banco();
if (!str_ends_with($conexao->query('SELECT DATABASE()')->fetchColumn(), '_teste')) {
    exit(1);
}
$trabalho = json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR);
while (microtime(true) < $trabalho['inicio']) { usleep(10000); }
try {
    $id = confirmar_pedido($conexao, $trabalho['usuario'], $trabalho['carrinho'], $trabalho['chave'], $trabalho['assinatura']);
    echo json_encode(['ok' => true, 'id' => $id]);
} catch (InvalidArgumentException $erro) {
    echo json_encode(['ok' => false, 'estoque' => str_contains($erro->getMessage(), 'indisponível')]);
} catch (Throwable $erro) {
    echo json_encode(['ok' => false, 'falha' => true]);
    exit(1);
}
