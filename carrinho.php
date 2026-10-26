<?php
require_once __DIR__ . '/includes/carrinho.php';
$erros = [];
$resumo = ['itens' => [], 'erros' => [], 'total' => 0];
$sucesso = $_SESSION['sucesso'] ?? null;
unset($_SESSION['sucesso']);
try {
    $conexao = conectar_banco();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!csrf_valido()) {
            http_response_code(403);
            $erros[] = 'Formulário inválido ou expirado. Recarregue a página.';
        } else {
            $carrinho = obter_carrinho();
            $id = inteiro_positivo(campo_post('produto_id'));
            $acao = campo_post('acao');
            if ($id && $acao === 'remover') {
                unset($carrinho[$id]);
            } elseif ($id && in_array($acao, ['adicionar', 'atualizar'], true)) {
                $produto = buscar_produto($conexao, $id);
                $quantidade = inteiro_positivo(campo_post('quantidade'));
                if ($quantidade && $acao === 'adicionar') {
                    $quantidade += $carrinho[$id] ?? 0;
                }
                if (!$produto || !$quantidade || $quantidade > (int) $produto['quantidade_estoque'] || preco_em_centavos($produto['preco']) <= 0) {
                    $erros[] = 'Quantidade inválida ou superior ao estoque disponível. Seu carrinho foi preservado.';
                } else {
                    $carrinho[$id] = $quantidade;
                }
            } else {
                $erros[] = 'Solicitação inválida. Selecione um produto do catálogo.';
            }
            if (!$erros) {
                $_SESSION['carrinho'] = $carrinho;
                unset($_SESSION['checkout_token'], $_SESSION['checkout_assinatura']);
                $_SESSION['sucesso'] = $acao === 'remover' ? 'Produto removido do carrinho.' : 'Carrinho atualizado.';
                redirecionar('carrinho.php');
            }
        }
    }
    $resumo = consultar_carrinho($conexao, obter_carrinho());
    $erros = array_merge($erros, $resumo['erros']);
} catch (PDOException | RuntimeException $falha) {
    http_response_code(503);
    $erros[] = 'Não foi possível consultar seu carrinho agora. Tente novamente mais tarde.';
    error_log('Loja Mobile: carrinho indisponível.');
}
$titulo_pagina = 'Seu carrinho — Loja Mobile';
require __DIR__ . '/includes/cabecalho.php';
?>
<main class="container pagina-loja" id="conteudo">
    <span class="sobretitulo">SUA PRÓXIMA CONEXÃO</span><h1 class="titulo-pagina">Seu carrinho<span class="ponto">.</span></h1>
    <?php if ($sucesso): ?><div class="mensagem mensagem-sucesso" role="status"><?= escapar($sucesso) ?></div><?php endif; ?>
    <?php if ($erros): ?><div class="mensagem mensagem-erro" role="alert"><ul><?php foreach ($erros as $erro): ?><li><?= escapar($erro) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <?php if (!$resumo['itens']): ?>
        <div class="estado-catalogo"><h2>Seu carrinho ainda está vazio.</h2><p>Encontre um celular para começar sua compra de demonstração.</p><a class="botao" href="<?= escapar(url_loja('#produtos')) ?>">Explorar celulares <span aria-hidden="true">↗</span></a></div>
    <?php else: ?>
        <div class="layout-compra"><div class="lista-carrinho">
            <?php foreach ($resumo['itens'] as $item): ?>
            <article class="item-carrinho">
                <img src="<?= escapar(url_loja(imagem_produto($item['imagem']))) ?>" alt="<?= escapar($item['modelo']) ?>" width="100" height="100">
                <div><span class="produto-marca"><?= escapar($item['marca']) ?></span><h2><?= escapar($item['modelo']) ?></h2><p><?= escapar(formatar_preco($item['preco'])) ?> por unidade</p>
                    <form class="controles-carrinho" method="post" action="<?= escapar(url_loja('carrinho.php')) ?>">
                        <input type="hidden" name="csrf" value="<?= escapar(token_csrf()) ?>"><input type="hidden" name="produto_id" value="<?= (int) $item['id'] ?>">
                        <label for="quantidade-<?= (int) $item['id'] ?>">Quantidade</label><input id="quantidade-<?= (int) $item['id'] ?>" name="quantidade" type="number" min="1" max="<?= max(1, (int) $item['quantidade_estoque']) ?>" value="<?= $item['quantidade'] ?>" required>
                        <?php if (empty($item['removido'])): ?><button class="botao-secundario" name="acao" value="atualizar">Atualizar</button><?php endif; ?><button class="botao-texto" name="acao" value="remover" formnovalidate>Remover</button>
                    </form>
                </div><strong><?= escapar(formatar_preco(centavos_para_decimal($item['subtotal']))) ?></strong>
            </article>
            <?php endforeach; ?>
        </div><aside class="resumo-compra"><h2>Resumo</h2><div class="linha-total"><span>Total</span><strong><?= escapar(formatar_preco(centavos_para_decimal($resumo['total']))) ?></strong></div><p>Compra simulada, sem cobrança ou frete.</p>
            <?php if (!$resumo['erros'] && http_response_code() !== 503): ?><a class="botao" href="<?= escapar(url_loja('checkout.php')) ?>">Revisar compra <span aria-hidden="true">↗</span></a><?php endif; ?>
            <a class="link-voltar" href="<?= escapar(url_loja('#produtos')) ?>">Continuar explorando</a>
        </aside></div>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/rodape.php'; ?>
