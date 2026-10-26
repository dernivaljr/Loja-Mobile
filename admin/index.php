<?php
require_once __DIR__ . '/../includes/autenticacao.php';
exigir_admin();

$usuario = obter_usuario_logado();
$titulo_pagina = 'Painel Administrativo — Loja Mobile';
require __DIR__ . '/../includes/cabecalho.php';
?>
    <main class="container area-conta" id="conteudo">
        <section class="cartao-conta painel-admin" aria-labelledby="titulo-painel">
            <span class="sobretitulo">LOJA MOBILE · ADMINISTRAÇÃO</span>
            <h1 id="titulo-painel">Painel Administrativo<span class="ponto">.</span></h1>
            <p>Olá, <strong><?= escapar($usuario['nome']) ?></strong>. Boas-vindas ao seu painel!</p>
            <div class="aviso-painel"><span class="etapa">PRÓXIMA ETAPA</span><h2>Um passo de cada vez.</h2><p>O gerenciamento de produtos será implementado na Etapa 03.</p></div>
            <a class="botao" href="<?= escapar(url_loja()) ?>">Retornar à loja <span aria-hidden="true">↗</span></a>
        </section>
    </main>
<?php require __DIR__ . '/../includes/rodape.php'; ?>
