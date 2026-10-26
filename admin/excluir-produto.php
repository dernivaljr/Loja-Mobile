<?php
require_once __DIR__ . '/../includes/autenticacao.php';
require_once __DIR__ . '/../includes/produtos.php';
exigir_admin();

$erro = '';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    $erro = 'Utilize o formulário de exclusão no painel.';
} elseif (!csrf_valido()) {
    http_response_code(403);
    $erro = 'Formulário inválido ou expirado. Volte ao painel e tente novamente.';
} else {
    try {
        $conexao = conectar_banco();
        $id = inteiro_positivo(campo_post('id'));
        $produto = $id ? buscar_produto($conexao, $id) : null;
        if (!$produto) {
            http_response_code(404);
            $erro = 'Produto não encontrado.';
        } else {
            $conexao->prepare('DELETE FROM produtos WHERE id = ?')->execute([$id]);
            remover_imagem_enviada($produto['imagem']);
            $_SESSION['sucesso'] = 'Produto excluído.';
            redirecionar('admin/index.php');
        }
    } catch (PDOException | RuntimeException $falha) {
        if ($falha instanceof PDOException && ($falha->errorInfo[1] ?? null) === 1451) {
            http_response_code(409);
            $erro = 'Este produto pertence a um pedido e não pode ser excluído. Você pode ajustar seu estoque no painel.';
        } else {
            http_response_code(503);
            $erro = 'Não foi possível excluir o produto agora.';
            error_log('Loja Mobile: falha na exclusão de produto.');
        }
    }
}
$titulo_pagina = 'Exclusão de produto — Loja Mobile';
require __DIR__ . '/../includes/cabecalho.php';
?>
<main class="container area-conta" id="conteudo"><section class="cartao-conta"><h1>Excluir produto<span class="ponto">.</span></h1><div class="mensagem mensagem-erro" role="alert"><?= escapar($erro) ?></div><p class="alternativa-conta"><a href="<?= escapar(url_loja('admin/index.php')) ?>">Voltar ao painel</a></p></section></main>
<?php require __DIR__ . '/../includes/rodape.php'; ?>
