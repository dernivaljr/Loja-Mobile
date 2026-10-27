<?php

require_once __DIR__ . '/includes/conexao.php';
require_once __DIR__ . '/includes/funcoes.php';
require_once __DIR__ . '/includes/autenticacao.php';

$produtos = [];
$catalogo_indisponivel = false;

try {
    $conexao = conectar_banco();
    $produtos = buscar_produtos($conexao);
} catch (PDOException | RuntimeException $erro) {
    $catalogo_indisponivel = true;
    http_response_code(503);
    // Registra um diagnóstico genérico, sem credenciais ou mensagens do driver.
    error_log('Loja Mobile: catálogo indisponível. Verifique a configuração, o serviço MySQL e a importação do schema.');
}

require __DIR__ . '/includes/cabecalho.php';
?>
    <main id="conteudo">
        <section class="hero" aria-labelledby="titulo-principal">
            <div class="container hero-conteudo">
                <div class="hero-texto">
                    <span class="sobretitulo"><span class="traco" aria-hidden="true"></span> MAIS CONEXÕES, NOVAS POSSIBILIDADES</span>
                    <h1 id="titulo-principal">Seu próximo<br>celular começa<br><em>aqui.</em></h1>
                    <p>Um novo olhar para o seu dia a dia. Explore nosso catálogo e encontre inspiração para a sua próxima conexão.</p>
                    <a class="botao" href="#produtos">Explorar celulares <span aria-hidden="true">↗</span></a>
                    <span class="hero-nota">Encontre o modelo que combina com você.</span>
                </div>
                <div class="hero-visual">
                    <span class="visual-indice" aria-hidden="true">MOBILE / 01</span>
                    <img src="assets/img/hero-celulares.svg" width="640" height="620" alt="Ilustração de dois celulares, um de costas em verde e outro com tela em tons de laranja.">
                    <span class="visual-legenda">Conecte-se ao que vem a seguir. <span aria-hidden="true">✳</span></span>
                </div>
            </div>
        </section>

        <div class="container destaques" aria-label="Conheça a vitrine">
            <div><span class="destaque-icone" aria-hidden="true">01</span><p><strong>Explore com calma</strong><span>Todos os modelos em um só lugar</span></p></div>
            <div><span class="destaque-icone" aria-hidden="true">02</span><p><strong>Conheça as possibilidades</strong><span>Uma seleção para descobrir</span></p></div>
            <div><span class="destaque-icone" aria-hidden="true">03</span><p><strong>Escolha com confiança</strong><span>Preços e disponibilidade em cada produto</span></p></div>
        </div>

        <section class="catalogo container" id="produtos" aria-labelledby="titulo-catalogo">
            <div class="secao-cabecalho">
                <div><span class="sobretitulo">ENCONTRE SUA PRÓXIMA CONEXÃO</span><h2 id="titulo-catalogo">Na nossa vitrine<span class="ponto">.</span></h2></div>
                <span class="etiqueta">Escolha seu próximo celular</span>
            </div>

            <?php if ($catalogo_indisponivel): ?>
                <div class="estado-catalogo" role="status">
                    <span class="estado-icone" aria-hidden="true">✳</span>
                    <h3>Estamos preparando nossa vitrine</h3>
                    <p>O catálogo está temporariamente indisponível. Tente novamente mais tarde.</p>
                </div>
            <?php elseif (!$produtos): ?>
                <div class="estado-catalogo" role="status">
                    <span class="estado-icone" aria-hidden="true">✳</span>
                    <h3>Novas conexões vêm por aí</h3>
                    <p>Estamos renovando nossa seleção de celulares. Volte em breve para conferir.</p>
                </div>
            <?php else: ?>
                <p class="quantidade-produtos"><?= count($produtos) ?> <?= count($produtos) === 1 ? 'modelo para conhecer' : 'modelos para conhecer' ?></p>
                <div class="produtos-grid">
                    <?php foreach ($produtos as $produto): ?>
                        <article class="produto">
                            <div class="produto-imagem">
                                <span class="produto-marcador">CELULAR</span>
                                <img src="<?= escapar(imagem_produto($produto['imagem'])) ?>" width="360" height="360" loading="lazy" alt="Ilustração de celular para <?= escapar($produto['marca'] . ' ' . $produto['modelo']) ?>">
                            </div>
                            <div class="produto-conteudo">
                                <span class="produto-marca"><?= escapar($produto['marca']) ?></span>
                                <h3><a href="<?= escapar(url_loja('produto.php?id=' . $produto['id'])) ?>"><?= escapar($produto['modelo']) ?></a></h3>
                                <p class="produto-descricao"><?= escapar($produto['descricao']) ?></p>
                                <p class="produto-preco"><?= escapar(formatar_preco($produto['preco'])) ?></p>
                                <span class="produto-nota"><?= (int) $produto['quantidade_estoque'] > 0 ? 'Em estoque' : 'Indisponível · sem estoque' ?></span>
                                <a class="link-produto" href="<?= escapar(url_loja('produto.php?id=' . $produto['id'])) ?>">Conhecer celular <span aria-hidden="true">↗</span></a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <p class="catalogo-nota">Confira os detalhes de cada modelo e escolha a quantidade desejada antes de fazer seu pedido.</p>
        </section>

        <section class="sobre container" id="sobre" aria-labelledby="titulo-sobre">
            <div><span class="sobretitulo">TECNOLOGIA QUE APROXIMA</span><h2 id="titulo-sobre">Um celular para cada momento.<br>Uma escolha que é sua.</h2></div>
            <div class="sobre-descricao"><p>Na Loja Mobile, você encontra celulares para acompanhar seu dia a dia, suas ideias e suas conexões.</p><p>Explore os modelos, confira os detalhes e reúna suas escolhas no carrinho. Tudo o que você precisa para decidir está em cada produto.</p><span class="etapa"><span aria-hidden="true"></span> CONHEÇA. ESCOLHA. CONECTE-SE.</span></div>
        </section>
    </main>
<?php require __DIR__ . '/includes/rodape.php'; ?>
