<?php
require_once __DIR__ . '/autenticacao.php';
require_once __DIR__ . '/produtos.php';

function obter_carrinho(): array
{
    return is_array($_SESSION['carrinho'] ?? null) ? $_SESSION['carrinho'] : [];
}

function consultar_carrinho(PDO $conexao, array $carrinho, bool $bloquear = false): array
{
    $itens = [];
    $erros = [];
    $total = 0;
    // Ordem fixa de bloqueio reduz a possibilidade de deadlocks entre compras.
    ksort($carrinho, SORT_NUMERIC);
    $consulta = $conexao->prepare('SELECT * FROM produtos WHERE id = ?' . ($bloquear ? ' FOR UPDATE' : ''));
    foreach ($carrinho as $id => $quantidade) {
        if (!inteiro_positivo((string) $id) || !is_int($quantidade) || $quantidade < 1) {
            $erros[] = 'Há uma quantidade inválida no carrinho. Remova o item e adicione novamente.';
            continue;
        }
        $consulta->execute([$id]);
        $produto = $consulta->fetch();
        if (!$produto) {
            $erros[] = 'Um produto do carrinho não está mais disponível. Remova-o para continuar.';
            $itens[] = ['id' => (int) $id, 'modelo' => 'Produto removido', 'marca' => '', 'imagem' => null, 'quantidade' => $quantidade, 'quantidade_estoque' => 0, 'preco' => '0.00', 'subtotal' => 0, 'removido' => true];
            continue;
        }
        $unitario = preco_em_centavos($produto['preco']);
        if ($quantidade > (int) $produto['quantidade_estoque'] || $unitario <= 0) {
            $erros[] = $produto['marca'] . ' ' . $produto['modelo'] . ': quantidade ou preço indisponível. Ajuste ou remova o item.';
        }
        // Evita overflow e valores maiores que DECIMAL(10,2) no pedido.
        $subtotal = 0;
        if ($unitario > 0 && $quantidade <= intdiv(9999999999, $unitario)) {
            $subtotal = $unitario * $quantidade;
        } else {
            $erros[] = 'A quantidade selecionada ultrapassa o limite de valor por pedido.';
        }
        $total += $subtotal;
        $itens[] = array_merge($produto, ['quantidade' => $quantidade, 'subtotal' => $subtotal]);
    }
    if ($total > 9999999999) {
        $erros[] = 'O total ultrapassa o limite de valor por pedido. Reduza as quantidades.';
    }
    return ['itens' => $itens, 'erros' => $erros, 'total' => $total];
}

function assinatura_carrinho(array $resumo): string
{
    $valores = [];
    foreach ($resumo['itens'] as $item) {
        $valores[] = [$item['id'], $item['quantidade'], $item['preco']];
    }
    return hash('sha256', json_encode($valores));
}

function confirmar_pedido(PDO $conexao, int $usuario_id, array $carrinho, string $chave, string $assinatura): int
{
    if (!$carrinho || !preg_match('/\A[a-f0-9]{64}\z/', $chave)) {
        throw new InvalidArgumentException('Seu carrinho está vazio ou a confirmação expirou. Revise sua compra.');
    }
    $conexao->beginTransaction();
    try {
        $resumo = consultar_carrinho($conexao, $carrinho, true);
        // A chave é consultada após os locks: reenvios concorrentes veem o commit anterior.
        $existente = $conexao->prepare('SELECT id, usuario_id FROM pedidos WHERE chave_checkout = ?');
        $existente->execute([$chave]);
        $pedido = $existente->fetch();
        if ($pedido) {
            if ((int) $pedido['usuario_id'] !== $usuario_id) {
                throw new InvalidArgumentException('Confirmação inválida. Revise sua compra.');
            }
            $conexao->commit();
            return (int) $pedido['id'];
        }
        // O perfil atual também é conferido no banco antes de registrar o pedido.
        $cliente = $conexao->prepare("SELECT id FROM usuarios WHERE id = ? AND perfil = 'cliente'");
        $cliente->execute([$usuario_id]);
        if (!$cliente->fetch()) {
            throw new InvalidArgumentException('Somente contas de cliente podem finalizar a compra.');
        }
        if ($resumo['erros']) {
            throw new InvalidArgumentException(implode(' ', $resumo['erros']));
        }
        if (!hash_equals($assinatura, assinatura_carrinho($resumo))) {
            throw new InvalidArgumentException('Os preços ou itens foram alterados. Revise o resumo atualizado antes de confirmar.');
        }
        $inserir = $conexao->prepare("INSERT INTO pedidos (usuario_id, chave_checkout, total, status) VALUES (?, ?, ?, 'confirmado')");
        $inserir->execute([$usuario_id, $chave, centavos_para_decimal($resumo['total'])]);
        $pedido_id = (int) $conexao->lastInsertId();
        $inserir_item = $conexao->prepare('INSERT INTO pedido_itens (pedido_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)');
        $baixar_estoque = $conexao->prepare('UPDATE produtos SET quantidade_estoque = quantidade_estoque - ? WHERE id = ? AND quantidade_estoque >= ?');
        foreach ($resumo['itens'] as $item) {
            $inserir_item->execute([$pedido_id, $item['id'], $item['quantidade'], $item['preco']]);
            $baixar_estoque->execute([$item['quantidade'], $item['id'], $item['quantidade']]);
            if ($baixar_estoque->rowCount() !== 1) {
                throw new InvalidArgumentException('O estoque mudou. Revise seu carrinho antes de confirmar.');
            }
        }
        $conexao->commit();
        return $pedido_id;
    } catch (Throwable $erro) {
        if ($conexao->inTransaction()) {
            $conexao->rollBack();
        }
        throw $erro;
    }
}
