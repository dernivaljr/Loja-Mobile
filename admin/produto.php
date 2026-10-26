<?php
require_once __DIR__ . '/../includes/autenticacao.php';
require_once __DIR__ . '/../includes/produtos.php';
exigir_admin();

$id = isset($_GET['id']) && is_string($_GET['id']) ? inteiro_positivo($_GET['id']) : null;
$erros = [];
$dados = ['codigo' => '', 'marca' => '', 'modelo' => '', 'descricao' => '', 'quantidade_estoque' => '0', 'preco' => '', 'imagem' => null];
$produto = null;
try {
    $conexao = conectar_banco();
    if (isset($_GET['id'])) {
        $produto = $id ? buscar_produto($conexao, $id) : null;
        if (!$produto) {
            http_response_code(404);
            $erros[] = 'Produto não encontrado.';
        } else {
            $dados = $produto;
        }
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$erros) {
        foreach (['codigo', 'marca', 'modelo', 'descricao', 'quantidade_estoque', 'preco'] as $campo) {
            $dados[$campo] = trim(campo_post($campo));
        }
        $dados['preco'] = str_replace(',', '.', $dados['preco']);
        if (!csrf_valido()) {
            http_response_code(403);
            $erros[] = 'Formulário inválido ou expirado. Recarregue a página.';
        } else {
            $erros = validar_produto($dados);
        }
        if (!$erros) {
            $nova_imagem = null;
            try {
                $nova_imagem = receber_imagem(isset($_FILES['imagem']) && is_array($_FILES['imagem']) ? $_FILES['imagem'] : null, !$produto);
                $valores = [$dados['codigo'], $dados['marca'], $dados['modelo'], $dados['descricao'], $dados['quantidade_estoque'], $dados['preco']];
                if ($produto) {
                    // Sem upload, não sobrescreve a imagem possivelmente alterada por outro admin.
                    $sql = 'UPDATE produtos SET codigo=?, marca=?, modelo=?, descricao=?, quantidade_estoque=?, preco=?';
                    if ($nova_imagem) {
                        $sql .= ', imagem=?';
                        $valores[] = $nova_imagem;
                    }
                    $sql .= ' WHERE id=?';
                    $valores[] = $id;
                } else {
                    $sql = 'INSERT INTO produtos (codigo, marca, modelo, descricao, quantidade_estoque, preco, imagem) VALUES (?, ?, ?, ?, ?, ?, ?)';
                    $valores[] = $nova_imagem;
                }
                $conexao->prepare($sql)->execute($valores);
                if ($nova_imagem && $produto) {
                    remover_imagem_enviada($produto['imagem']);
                }
                $_SESSION['sucesso'] = $produto ? 'Produto atualizado.' : 'Produto cadastrado.';
                redirecionar('admin/index.php');
            } catch (InvalidArgumentException $erro) {
                $erros[] = $erro->getMessage();
            } catch (PDOException | RuntimeException $erro) {
                remover_imagem_enviada($nova_imagem);
                if ($erro instanceof PDOException && ($erro->errorInfo[1] ?? null) === 1062) {
                    $erros[] = 'Este código de produto já está cadastrado.';
                } else {
                    throw $erro;
                }
            }
        }
    }
} catch (PDOException | RuntimeException $erro) {
    http_response_code(503);
    $erros[] = 'Não foi possível salvar ou carregar o produto. Tente novamente.';
    error_log('Loja Mobile: falha na administração de produtos.');
}
$titulo_pagina = ($produto ? 'Editar produto' : 'Cadastrar produto') . ' — Loja Mobile';
require __DIR__ . '/../includes/cabecalho.php';
?>
<main class="container area-conta" id="conteudo">
    <section class="cartao-conta painel-admin">
        <span class="sobretitulo">ADMINISTRAÇÃO · PRODUTOS</span>
        <h1><?= $produto ? 'Editar produto' : 'Cadastrar produto' ?><span class="ponto">.</span></h1>
        <?php if ($erros): ?><div class="mensagem mensagem-erro" role="alert"><ul><?php foreach ($erros as $erro): ?><li><?= escapar($erro) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <?php if (http_response_code() !== 404 && http_response_code() !== 503): ?>
        <form class="formulario-conta" method="post" enctype="multipart/form-data" action="<?= escapar(url_loja('admin/produto.php' . ($id ? '?id=' . $id : ''))) ?>">
            <input type="hidden" name="csrf" value="<?= escapar(token_csrf()) ?>">
            <?php foreach (['codigo' => ['Código', 50], 'marca' => ['Marca', 80], 'modelo' => ['Modelo', 120]] as $campo => [$rotulo, $limite]): ?>
            <div class="campo"><label for="<?= $campo ?>"><?= $rotulo ?></label><input id="<?= $campo ?>" name="<?= $campo ?>" value="<?= escapar($dados[$campo]) ?>" maxlength="<?= $limite ?>" required></div>
            <?php endforeach; ?>
            <div class="campo"><label for="descricao">Descrição</label><textarea id="descricao" name="descricao" rows="4" maxlength="10000" required><?= escapar($dados['descricao']) ?></textarea></div>
            <div class="campos-duplos">
                <div class="campo"><label for="quantidade_estoque">Estoque</label><input id="quantidade_estoque" name="quantidade_estoque" type="number" min="0" max="2147483647" step="1" value="<?= escapar((string) $dados['quantidade_estoque']) ?>" required></div>
                <div class="campo"><label for="preco">Preço (R$)</label><input id="preco" name="preco" inputmode="decimal" value="<?= escapar($dados['preco']) ?>" placeholder="1299,90" aria-describedby="ajuda-preco" required><span class="ajuda-campo" id="ajuda-preco">Use vírgula ou ponto para os centavos, sem separador de milhar.</span></div>
            </div>
            <?php if ($produto): ?><img class="miniatura-edicao" src="<?= escapar(url_loja(imagem_produto($produto['imagem']))) ?>" alt="Imagem atual do produto"><p class="ajuda-campo">Selecione outra imagem somente se quiser substituir a atual.</p><?php endif; ?>
            <div class="campo"><label for="imagem">Imagem<?= $produto ? ' (opcional na edição)' : '' ?></label><input id="imagem" name="imagem" type="file" accept="image/jpeg,image/png,image/webp" <?= !$produto ? 'required' : '' ?> aria-describedby="ajuda-imagem"><span class="ajuda-campo" id="ajuda-imagem">JPEG, PNG ou WebP. Tamanho máximo: 2 MB.</span></div>
            <button class="botao" type="submit">Salvar produto <span aria-hidden="true">↗</span></button>
        </form>
        <?php endif; ?>
        <p class="alternativa-conta"><a href="<?= escapar(url_loja('admin/index.php')) ?>">Voltar ao painel</a></p>
    </section>
</main>
<?php require __DIR__ . '/../includes/rodape.php'; ?>
