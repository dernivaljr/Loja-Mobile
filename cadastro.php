<?php
require_once __DIR__ . '/includes/autenticacao.php';

if (usuario_logado()) {
    redirecionar('');
}

$erros = [];
$nome = '';
$email = '';
$perfil = 'cliente';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim(campo_post('nome'));
    $email = strtolower(trim(campo_post('email')));
    $senha = campo_post('senha');
    $confirmacao = campo_post('confirmacao_senha');
    $perfil = perfil_permitido_cadastro(campo_post('perfil'));

    if (!csrf_valido()) {
        http_response_code(403);
        $erros[] = 'Não foi possível validar o formulário. Recarregue a página e tente novamente.';
    } else {
        if ($nome === '' || mb_strlen($nome, 'UTF-8') > 120) {
            $erros[] = 'Informe seu nome completo, com até 120 caracteres.';
        }
        if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = 'Informe um e-mail válido, com até 254 caracteres.';
        }
        if (mb_strlen($senha, 'UTF-8') < 8) {
            $erros[] = 'A senha deve ter pelo menos 8 caracteres.';
        } elseif (strlen($senha) > 72 || str_contains($senha, "\0")) {
            // Evita truncamento silencioso do bcrypt utilizado pelo PHP atual.
            $erros[] = 'A senha é muito longa ou contém caracteres inválidos. Escolha outra senha.';
        }
        if ($confirmacao === '' || $senha !== $confirmacao) {
            $erros[] = 'A confirmação de senha deve ser igual à senha informada.';
        }

        if (!$erros) {
            try {
                $conexao = conectar_banco();
                if (buscar_usuario_por_email($conexao, $email)) {
                    $erros[] = 'Este e-mail já está cadastrado. Entre com sua conta.';
                } else {
                    cadastrar_usuario($conexao, $nome, $email, $senha, $perfil);
                    $_SESSION['sucesso'] = 'Cadastro realizado! Agora você já pode entrar com sua conta.';
                    redirecionar('login.php');
                }
            } catch (PDOException | RuntimeException $erro) {
                // Trata também a disputa entre dois cadastros simultâneos do mesmo e-mail.
                if ($erro instanceof PDOException && ($erro->errorInfo[1] ?? null) === 1062) {
                    $erros[] = 'Este e-mail já está cadastrado. Entre com sua conta.';
                } else {
                    http_response_code(503);
                    $erros[] = 'Não foi possível concluir o cadastro agora. Tente novamente mais tarde.';
                    error_log('Loja Mobile: cadastro indisponível. Verifique o banco local.');
                }
            }
        }
    }
}

$titulo_pagina = 'Criar conta — Loja Mobile';
require __DIR__ . '/includes/cabecalho.php';
?>
    <main class="container area-conta" id="conteudo">
        <section class="cartao-conta" aria-labelledby="titulo-cadastro">
            <span class="sobretitulo">SUA CONTA NA LOJA MOBILE</span>
            <h1 id="titulo-cadastro">Crie sua conta<span class="ponto">.</span></h1>
            <p>Cadastre-se para escolher seu próximo celular e finalizar seus pedidos.</p>
            <?php if ($erros): ?>
                <div class="mensagem mensagem-erro" role="alert"><ul><?php foreach ($erros as $mensagem): ?><li><?= escapar($mensagem) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
            <form class="formulario-conta" action="<?= escapar(url_loja('cadastro.php')) ?>" method="post">
                <input type="hidden" name="csrf" value="<?= escapar(token_csrf()) ?>">
                <div class="campo"><label for="nome">Nome completo</label><input id="nome" name="nome" type="text" value="<?= escapar($nome) ?>" autocomplete="name" maxlength="120" required></div>
                <div class="campo"><label for="email">E-mail</label><input id="email" name="email" type="email" value="<?= escapar($email) ?>" autocomplete="email" maxlength="254" required></div>
                <div class="campo"><label for="perfil">Tipo de conta</label><select id="perfil" name="perfil" required><option value="cliente" <?= $perfil === 'cliente' ? 'selected' : '' ?>>Cliente</option><option value="admin" <?= $perfil === 'admin' ? 'selected' : '' ?> <?= !requisicao_local() ? 'disabled' : '' ?>>Administrador</option></select><?php if (requisicao_local()): ?><span class="ajuda-campo">O cadastro de administrador está disponível apenas no acesso por este computador.</span><?php endif; ?></div>
                <div class="campo"><label for="senha">Senha</label><input id="senha" name="senha" type="password" autocomplete="new-password" minlength="8" aria-describedby="ajuda-senha" required><span class="ajuda-campo" id="ajuda-senha">Use pelo menos 8 caracteres e prefira uma senha exclusiva.</span></div>
                <div class="campo"><label for="confirmacao-senha">Confirme sua senha</label><input id="confirmacao-senha" name="confirmacao_senha" type="password" autocomplete="new-password" minlength="8" required></div>
                <button class="botao" type="submit">Criar minha conta <span aria-hidden="true">↗</span></button>
            </form>
            <p class="alternativa-conta">Já tem uma conta? <a href="<?= escapar(url_loja('login.php')) ?>">Entrar</a></p>
        </section>
        <p class="nota-conta">Já encontrou seu próximo celular? Crie sua conta para concluir o pedido.</p>
    </main>
<?php require __DIR__ . '/includes/rodape.php'; ?>
