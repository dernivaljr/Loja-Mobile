<?php
require_once __DIR__ . '/includes/autenticacao.php';
require_once __DIR__ . '/includes/produtos.php';
exigir_login();
$pedido = null;
$itens = [];
$erro = '';
try {
    $conexao = conectar_banco();
    $id = isset($_GET['id']) && is_string($_GET['id']) ? inteiro_positivo($_GET['id']) : null;
    // O filtro do proprietário impede consultar o pedido de outra conta por ID.
    $consulta = $conexao->prepare('SELECT * FROM pedidos WHERE id = ? AND usuario_id = ?');
    $consulta->execute([$id ?? 0, obter_usuario_logado()['id']]);
    $pedido = $consulta->fetch();
    if (!$pedido) {
        http_response_code(404);
        $erro = 'Pedido não encontrado para sua conta.';
    } else {
        $consulta = $conexao->prepare('SELECT i.quantidade, i.preco_unitario, p.marca, p.modelo FROM pedido_itens i JOIN produtos p ON p.id = i.produto_id WHERE i.pedido_id = ? ORDER BY i.id');
        $consulta->execute([$id]);
        $itens = $consulta->fetchAll();
    }
} catch (PDOException | RuntimeException $falha) {
    http_response_code(503);
    $erro = 'Não foi possível consultar seu pedido agora. Tente novamente mais tarde.';
    error_log('Loja Mobile: confirmação de pedido indisponível.');
}
$titulo_pagina = 'Seu pedido — Loja Mobile';
require __DIR__ . '/includes/cabecalho.php';
?>
<main class="container area-conta" id="conteudo"><section class="cartao-conta painel-admin">
    <?php if ($erro): ?><h1>Seu pedido<span class="ponto">.</span></h1><div class="mensagem mensagem-erro" role="alert"><?= escapar($erro) ?></div>
    <?php else: ?><span class="sobretitulo">PEDIDO #<?= (int) $pedido['id'] ?> · <?= escapar($pedido['status']) ?></span><h1>Nova conexão confirmada<span class="ponto">.</span></h1><p>Seu pedido foi registrado. Esta é uma compra simulada, sem cobrança.</p>
    <ul class="itens-resumo"><?php foreach ($itens as $item): ?><li><span><?= (int) $item['quantidade'] ?> × <?= escapar($item['marca'] . ' ' . $item['modelo']) ?></span><strong><?= escapar(formatar_preco(centavos_para_decimal(preco_em_centavos($item['preco_unitario']) * (int) $item['quantidade']))) ?></strong></li><?php endforeach; ?></ul>
    <div class="linha-total"><span>Total</span><strong><?= escapar(formatar_preco($pedido['total'])) ?></strong></div><p>Registrado em <?= escapar(date('d/m/Y H:i', strtotime($pedido['criado_em']))) ?>.</p>
    <?php endif; ?><a class="botao" href="<?= escapar(url_loja('#produtos')) ?>">Voltar ao catálogo <span aria-hidden="true">↗</span></a>
</section></main>
<?php require __DIR__ . '/includes/rodape.php'; ?>
