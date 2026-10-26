<?php
require_once __DIR__ . '/../includes/autenticacao.php';
exigir_admin();

$produtos = [];
$pedidos = [];
$erro = '';
$sucesso = $_SESSION['sucesso'] ?? null;
unset($_SESSION['sucesso']);
try {
    $conexao = conectar_banco();
    $produtos = buscar_produtos($conexao);
    $pedidos = $conexao->query('SELECT p.id, u.nome AS cliente, p.total, p.status, p.criado_em FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id ORDER BY p.id DESC')->fetchAll();
} catch (PDOException | RuntimeException $falha) {
    http_response_code(503);
    $erro = 'Não foi possível carregar os dados do painel. Tente novamente mais tarde.';
    error_log('Loja Mobile: painel indisponível.');
}
$titulo_pagina = 'Painel Administrativo — Loja Mobile';
require __DIR__ . '/../includes/cabecalho.php';
?>
<main class="container pagina-loja" id="conteudo">
    <span class="sobretitulo">LOJA MOBILE · ADMINISTRAÇÃO</span>
    <h1 class="titulo-pagina">Painel Administrativo<span class="ponto">.</span></h1>
    <p>Olá, <?= escapar(obter_usuario_logado()['nome']) ?>. Gerencie os produtos e acompanhe os pedidos.</p>
    <?php if ($sucesso): ?><div class="mensagem mensagem-sucesso" role="status"><?= escapar($sucesso) ?></div><?php endif; ?>
    <?php if ($erro): ?><div class="mensagem mensagem-erro" role="alert"><?= escapar($erro) ?></div><?php endif; ?>
    <section class="secao-admin" aria-labelledby="titulo-produtos">
        <div class="secao-cabecalho"><h2 id="titulo-produtos">Produtos</h2><a class="botao" href="<?= escapar(url_loja('admin/produto.php')) ?>">Cadastrar produto <span aria-hidden="true">+</span></a></div>
        <?php if (!$produtos): ?><p>Nenhum produto cadastrado.</p><?php else: ?>
        <div class="tabela-responsiva" tabindex="0" role="region" aria-label="Produtos cadastrados"><table><thead><tr><th scope="col">Imagem</th><th scope="col">Código</th><th scope="col">Celular</th><th scope="col">Preço</th><th scope="col">Estoque</th><th scope="col">Ações</th></tr></thead><tbody>
        <?php foreach ($produtos as $produto): ?><tr>
            <td><img class="miniatura-tabela" src="<?= escapar(url_loja(imagem_produto($produto['imagem']))) ?>" alt="<?= escapar($produto['modelo']) ?>" width="60" height="60"></td>
            <td><?= escapar($produto['codigo']) ?></td><td><strong><?= escapar($produto['marca']) ?></strong><br><?= escapar($produto['modelo']) ?></td><td><?= escapar(formatar_preco($produto['preco'])) ?></td><td><?= (int) $produto['quantidade_estoque'] ?></td>
            <td><a class="botao-secundario" href="<?= escapar(url_loja('admin/produto.php?id=' . $produto['id'])) ?>">Editar</a>
                <details class="confirmar-exclusao"><summary>Excluir</summary><p>Excluir este produto definitivamente?</p><form method="post" action="<?= escapar(url_loja('admin/excluir-produto.php')) ?>"><input type="hidden" name="csrf" value="<?= escapar(token_csrf()) ?>"><input type="hidden" name="id" value="<?= (int) $produto['id'] ?>"><button class="botao-texto" type="submit">Confirmar exclusão</button></form></details>
            </td>
        </tr><?php endforeach; ?>
        </tbody></table></div><?php endif; ?>
    </section>
    <section class="secao-admin" aria-labelledby="titulo-pedidos"><h2 id="titulo-pedidos">Pedidos realizados</h2>
        <?php if (!$pedidos): ?><p>Nenhum pedido registrado.</p><?php else: ?><div class="tabela-responsiva" tabindex="0" role="region" aria-label="Pedidos realizados"><table><thead><tr><th scope="col">Número</th><th scope="col">Cliente</th><th scope="col">Total</th><th scope="col">Data</th><th scope="col">Status</th></tr></thead><tbody>
        <?php foreach ($pedidos as $pedido): ?><tr><td>#<?= (int) $pedido['id'] ?></td><td><?= escapar($pedido['cliente']) ?></td><td><?= escapar(formatar_preco($pedido['total'])) ?></td><td><?= escapar(date('d/m/Y H:i', strtotime($pedido['criado_em']))) ?></td><td><?= escapar($pedido['status']) ?></td></tr><?php endforeach; ?>
        </tbody></table></div><?php endif; ?>
    </section>
    <a class="link-voltar" href="<?= escapar(url_loja()) ?>">← Retornar à loja</a>
</main>
<?php require __DIR__ . '/../includes/rodape.php'; ?>
