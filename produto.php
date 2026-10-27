<?php
require_once __DIR__ . '/includes/autenticacao.php';
require_once __DIR__ . '/includes/produtos.php';
$produto = null;
$erro = '';
try {
    $id = isset($_GET['id']) && is_string($_GET['id']) ? inteiro_positivo($_GET['id']) : null;
    $produto = $id ? buscar_produto(conectar_banco(), $id) : null;
    if (!$produto) {
        http_response_code(404);
        $erro = 'Não encontramos este celular. Explore os modelos disponíveis no catálogo.';
    }
} catch (PDOException | RuntimeException $falha) {
    http_response_code(503);
    $erro = 'Não foi possível carregar o produto agora. Tente novamente mais tarde.';
    error_log('Loja Mobile: detalhe de produto indisponível.');
}
$titulo_pagina = ($produto ? $produto['marca'] . ' ' . $produto['modelo'] : 'Produto indisponível') . ' — Loja Mobile';
require __DIR__ . '/includes/cabecalho.php';
?>
<main class="container pagina-loja" id="conteudo">
    <a class="link-voltar" href="<?= escapar(url_loja('#produtos')) ?>">← Voltar ao catálogo</a>
    <?php if ($erro): ?><div class="estado-catalogo" role="status"><h1>Produto indisponível<span class="ponto">.</span></h1><p><?= escapar($erro) ?></p></div>
    <?php else: ?>
    <section class="detalhe-produto" aria-labelledby="titulo-produto">
        <div class="detalhe-imagem"><img src="<?= escapar(url_loja(imagem_produto($produto['imagem']))) ?>" alt="<?= escapar($produto['marca'] . ' ' . $produto['modelo']) ?>" width="500" height="500"></div>
        <div class="detalhe-texto"><span class="sobretitulo"><?= escapar($produto['marca']) ?> · <?= escapar($produto['codigo']) ?></span><h1 id="titulo-produto"><?= escapar($produto['modelo']) ?><span class="ponto">.</span></h1><p class="descricao-completa"><?= nl2br(escapar($produto['descricao'])) ?></p><p class="preco-detalhe"><?= escapar(formatar_preco($produto['preco'])) ?></p>
            <?php if ((int) $produto['quantidade_estoque'] > 0 && preco_em_centavos($produto['preco']) > 0): ?>
                <p class="disponibilidade">Em estoque · <?= (int) $produto['quantidade_estoque'] ?> unidades disponíveis</p>
                <form class="formulario-compra" method="post" action="<?= escapar(url_loja('carrinho.php')) ?>">
                    <input type="hidden" name="csrf" value="<?= escapar(token_csrf()) ?>"><input type="hidden" name="acao" value="adicionar"><input type="hidden" name="produto_id" value="<?= (int) $produto['id'] ?>">
                    <div class="campo"><label for="quantidade">Quantidade</label><input id="quantidade" name="quantidade" type="number" min="1" max="<?= (int) $produto['quantidade_estoque'] ?>" value="1" required></div>
                    <button class="botao" type="submit">Adicionar ao carrinho <span aria-hidden="true">↗</span></button>
                </form>
            <?php else: ?><p class="indisponivel">Indisponível no momento · sem estoque para compra.</p><?php endif; ?>
            <p class="ajuda-campo">Confira o valor total e revise os itens antes de confirmar seu pedido.</p>
        </div>
    </section>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/rodape.php'; ?>
