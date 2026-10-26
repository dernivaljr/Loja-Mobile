<?php
require_once __DIR__ . '/includes/autenticacao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    $titulo_pagina = 'Sair da conta — Loja Mobile';
    $mensagem = 'Para sair da sua conta, utilize o botão Sair na navegação.';
} elseif (!csrf_valido()) {
    http_response_code(403);
    $titulo_pagina = 'Formulário expirado — Loja Mobile';
    $mensagem = 'Não foi possível validar a solicitação. Volte à loja e tente novamente.';
} else {
    encerrar_sessao();
    redirecionar('');
}

require __DIR__ . '/includes/cabecalho.php';
?>
    <main class="container area-conta" id="conteudo">
        <section class="cartao-conta">
            <h1>Sair da conta<span class="ponto">.</span></h1>
            <p><?= escapar($mensagem) ?></p>
            <a class="botao" href="<?= escapar(url_loja()) ?>">Retornar à loja <span aria-hidden="true">↗</span></a>
        </section>
    </main>
<?php require __DIR__ . '/includes/rodape.php'; ?>
