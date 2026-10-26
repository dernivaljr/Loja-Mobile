<?php

function escapar(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function formatar_preco(string $preco): string
{
    // A conversão é somente para exibição; cálculos futuros exigem centavos ou DECIMAL.
    return 'R$ ' . number_format((float) $preco, 2, ',', '.');
}

function buscar_produtos(PDO $conexao): array
{
    $consulta = $conexao->prepare(
        'SELECT id, marca, modelo, descricao, preco, imagem
         FROM produtos
         ORDER BY id ASC'
    );
    $consulta->execute();

    return $consulta->fetchAll();
}

function imagem_produto(?string $caminho): string
{
    $padrao = 'assets/img/celular-padrao.svg';

    // Aceita somente imagens locais dentro de assets, sem travessia de diretórios.
    if (!$caminho || !preg_match('~\Aassets/(?:[a-zA-Z0-9_-]+/)*[a-zA-Z0-9_-]+\.(?:svg|png|jpe?g|webp)\z~i', $caminho)) {
        return $padrao;
    }

    return is_file(__DIR__ . '/../' . $caminho) ? $caminho : $padrao;
}
