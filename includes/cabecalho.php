<?php
require_once __DIR__ . '/autenticacao.php';
$usuario_navegacao = obter_usuario_logado();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Conheça o catálogo de demonstração da Loja Mobile, um projeto acadêmico de e-commerce de celulares.">
    <meta name="theme-color" content="#f6f5f1">
    <title><?= escapar($titulo_pagina ?? 'Loja Mobile — Seu próximo celular começa aqui') ?></title>
    <link rel="icon" href="<?= escapar(url_loja('assets/img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= escapar(url_loja('assets/css/style.css')) ?>">
</head>
<body>
    <a class="pular-conteudo" href="#conteudo">Pular para o conteúdo</a>
    <header class="cabecalho" id="inicio">
        <div class="container cabecalho-conteudo">
            <a class="logo" href="<?= escapar(url_loja()) ?>" aria-label="Loja Mobile, início">
                <span class="logo-simbolo" aria-hidden="true">m<span>•</span></span>
                <span>loja<span class="logo-leve">mobile</span><span class="ponto">.</span></span>
            </a>
            <nav aria-label="Navegação principal">
                <a href="<?= escapar(url_loja('#inicio')) ?>">Início</a>
                <a href="<?= escapar(url_loja('#produtos')) ?>">Celulares</a>
                <a href="<?= escapar(url_loja('#sobre')) ?>">Sobre o projeto</a>
            </nav>
            <div class="navegacao-conta">
                <?php if ($usuario_navegacao): ?>
                    <span class="nome-usuario" title="<?= escapar($usuario_navegacao['nome']) ?>">Olá, <?= escapar($usuario_navegacao['nome']) ?></span>
                    <?php if ($usuario_navegacao['perfil'] === 'admin'): ?><a href="<?= escapar(url_loja('admin/index.php')) ?>">Painel Administrativo</a><?php endif; ?>
                    <form action="<?= escapar(url_loja('logout.php')) ?>" method="post">
                        <input type="hidden" name="csrf" value="<?= escapar(token_csrf()) ?>">
                        <button class="botao-sair" type="submit">Sair</button>
                    </form>
                <?php else: ?>
                    <a href="<?= escapar(url_loja('login.php')) ?>">Entrar</a>
                    <a class="link-cadastro" href="<?= escapar(url_loja('cadastro.php')) ?>">Cadastrar <span aria-hidden="true">↗</span></a>
                <?php endif; ?>
            </div>
        </div>
    </header>
