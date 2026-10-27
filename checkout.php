<?php
require_once __DIR__ . '/includes/carrinho.php';
exigir_login();
if (obter_usuario_logado()['perfil'] !== 'cliente') {
    http_response_code(403);
    require __DIR__ . '/includes/acesso-negado.php';
    exit;
}
$erros = [];
$resumo = ['itens' => [], 'erros' => [], 'total' => 0];
try {
    $conexao = conectar_banco();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $chave = campo_post('checkout_token');
        if (!csrf_valido()) {
            http_response_code(403);
            $erros[] = 'Formulário inválido ou expirado. Recarregue a página.';
        } elseif (isset($_SESSION['ultimo_checkout']) && hash_equals($_SESSION['ultimo_checkout']['chave'], $chave)) {
            redirecionar('pedido.php?id=' . $_SESSION['ultimo_checkout']['id']);
        } elseif (!isset($_SESSION['checkout_token'], $_SESSION['checkout_assinatura']) || !hash_equals($_SESSION['checkout_token'], $chave)) {
            http_response_code(409);
            $erros[] = 'Esta confirmação expirou. Revise o resumo e tente novamente.';
        } else {
            try {
                $id = confirmar_pedido($conexao, obter_usuario_logado()['id'], obter_carrinho(), $chave, $_SESSION['checkout_assinatura']);
                // A sessão só é alterada depois de confirmar o commit do pedido.
                $_SESSION['ultimo_checkout'] = ['chave' => $chave, 'id' => $id];
                unset($_SESSION['carrinho'], $_SESSION['checkout_token'], $_SESSION['checkout_assinatura']);
                redirecionar('pedido.php?id=' . $id);
            } catch (InvalidArgumentException $falha) {
                http_response_code(409);
                $erros[] = $falha->getMessage();
            }
        }
    }
    $resumo = consultar_carrinho($conexao, obter_carrinho());
    $erros = array_merge($erros, $resumo['erros']);
    if ($resumo['itens'] && !$resumo['erros']) {
        $assinatura = assinatura_carrinho($resumo);
        if (!isset($_SESSION['checkout_token']) || ($_SESSION['checkout_assinatura'] ?? '') !== $assinatura) {
            $_SESSION['checkout_token'] = bin2hex(random_bytes(32));
        }
        $_SESSION['checkout_assinatura'] = $assinatura;
    }
} catch (PDOException | RuntimeException $falha) {
    http_response_code(503);
    $erros[] = 'Não foi possível confirmar sua compra agora. O carrinho foi preservado; tente novamente.';
    error_log('Loja Mobile: falha no checkout.');
}
$titulo_pagina = 'Revisar compra — Loja Mobile';
require __DIR__ . '/includes/cabecalho.php';
?>
<main class="container pagina-loja" id="conteudo">
    <span class="sobretitulo">FINALIZAR PEDIDO</span><h1 class="titulo-pagina">Confira sua escolha<span class="ponto">.</span></h1>
    <?php if ($erros): ?><div class="mensagem mensagem-erro" role="alert"><ul><?php foreach ($erros as $erro): ?><li><?= escapar($erro) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <section class="cartao-conta painel-admin resumo-checkout">
        <?php if (!$resumo['itens']): ?><h2>Seu carrinho está vazio.</h2><?php else: ?>
        <h2>Resumo do pedido</h2>
        <ul class="itens-resumo"><?php foreach ($resumo['itens'] as $item): ?><li><span><?= $item['quantidade'] ?> × <?= escapar($item['marca'] . ' ' . $item['modelo']) ?></span><strong><?= escapar(formatar_preco(centavos_para_decimal($item['subtotal']))) ?></strong></li><?php endforeach; ?></ul>
        <div class="linha-total"><span>Total</span><strong><?= escapar(formatar_preco(centavos_para_decimal($resumo['total']))) ?></strong></div>
        <p>Confira os produtos e o valor total antes de confirmar. Nenhum pagamento online será realizado nesta etapa.</p>
        <?php if (!$resumo['erros'] && http_response_code() !== 503): ?>
        <form method="post" action="<?= escapar(url_loja('checkout.php')) ?>"><input type="hidden" name="csrf" value="<?= escapar(token_csrf()) ?>"><input type="hidden" name="checkout_token" value="<?= escapar($_SESSION['checkout_token']) ?>"><button class="botao" type="submit">Confirmar pedido <span aria-hidden="true">↗</span></button></form>
        <?php endif; endif; ?>
        <p class="alternativa-conta"><a href="<?= escapar(url_loja('carrinho.php')) ?>">Voltar ao carrinho</a></p>
    </section>
</main>
<?php require __DIR__ . '/includes/rodape.php'; ?>
