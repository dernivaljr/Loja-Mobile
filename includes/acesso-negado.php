<?php
$titulo_pagina = 'Acesso restrito — Loja Mobile';
require __DIR__ . '/cabecalho.php';
?>
    <main class="container area-conta" id="conteudo">
        <section class="cartao-conta" aria-labelledby="titulo-acesso">
            <span class="sobretitulo">ACESSO RESTRITO</span>
            <h1 id="titulo-acesso">Acesso restrito<span class="ponto">.</span></h1>
            <p>Seu perfil não tem permissão para acessar esta área.</p>
            <a class="botao" href="<?= escapar(url_loja()) ?>">Retornar à loja <span aria-hidden="true">↗</span></a>
        </section>
    </main>
<?php require __DIR__ . '/rodape.php'; ?>
