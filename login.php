<?php
require_once __DIR__ . '/includes/autenticacao.php';

if (usuario_logado()) {
    redirecionar(obter_usuario_logado()['perfil'] === 'admin' ? 'admin/index.php' : '');
}

$erros = [];
$email = '';
$sucesso = $_SESSION['sucesso'] ?? null;
unset($_SESSION['sucesso']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim(campo_post('email')));
    $senha = campo_post('senha');

    if (!csrf_valido()) {
        http_response_code(403);
        $erros[] = 'Não foi possível validar o formulário. Recarregue a página e tente novamente.';
    } elseif (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || $senha === '' || strlen($senha) > 72 || str_contains($senha, "\0")) {
        $erros[] = 'E-mail ou senha incorretos.';
    } else {
        try {
            $usuario = buscar_usuario_por_email(conectar_banco(), $email);
            if ($usuario && password_verify($senha, $usuario['senha'])
                && in_array($usuario['perfil'], ['cliente', 'admin'], true)) {
                autenticar_usuario($usuario);
                redirecionar($usuario['perfil'] === 'admin' ? 'admin/index.php' : '');
            }
            $erros[] = 'E-mail ou senha incorretos.';
        } catch (PDOException | RuntimeException $erro) {
            http_response_code(503);
            $erros[] = 'Não foi possível entrar agora. Tente novamente mais tarde.';
            error_log('Loja Mobile: login indisponível. Verifique o banco local.');
        }
    }
}

$titulo_pagina = 'Entrar — Loja Mobile';
require __DIR__ . '/includes/cabecalho.php';
?>
    <main class="container area-conta" id="conteudo">
        <section class="cartao-conta" aria-labelledby="titulo-login">
            <span class="sobretitulo">BOM TER VOCÊ POR AQUI</span>
            <h1 id="titulo-login">Sua próxima conexão<span class="ponto">.</span></h1>
            <p>Entre com sua conta e continue explorando a Loja Mobile.</p>
            <?php if ($sucesso): ?><div class="mensagem mensagem-sucesso" role="status"><?= escapar($sucesso) ?></div><?php endif; ?>
            <?php if ($erros): ?>
                <div class="mensagem mensagem-erro" role="alert"><?php foreach ($erros as $mensagem): ?><p><?= escapar($mensagem) ?></p><?php endforeach; ?></div>
            <?php endif; ?>
            <form class="formulario-conta" action="<?= escapar(url_loja('login.php')) ?>" method="post">
                <input type="hidden" name="csrf" value="<?= escapar(token_csrf()) ?>">
                <div class="campo"><label for="email">E-mail</label><input id="email" name="email" type="email" value="<?= escapar($email) ?>" autocomplete="username" maxlength="254" required></div>
                <div class="campo"><label for="senha">Senha</label><input id="senha" name="senha" type="password" autocomplete="current-password" required></div>
                <button class="botao" type="submit">Entrar na minha conta <span aria-hidden="true">↗</span></button>
            </form>
            <p class="alternativa-conta">Ainda não tem uma conta? <a href="<?= escapar(url_loja('cadastro.php')) ?>">Cadastrar</a></p>
        </section>
        <p class="nota-conta">Uma loja em construção. Um aprendizado de verdade.</p>
    </main>
<?php require __DIR__ . '/includes/rodape.php'; ?>
