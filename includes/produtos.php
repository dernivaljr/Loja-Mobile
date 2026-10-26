<?php
require_once __DIR__ . '/funcoes.php';

function inteiro_positivo(string $valor): ?int
{
    $numero = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
    return $numero === false ? null : $numero;
}

function buscar_produto(PDO $conexao, int $id): ?array
{
    $consulta = $conexao->prepare('SELECT * FROM produtos WHERE id = ?');
    $consulta->execute([$id]);
    return $consulta->fetch() ?: null;
}

function preco_em_centavos(string $preco): int
{
    [$inteiro, $decimal] = array_pad(explode('.', $preco, 2), 2, '0');
    return (int) $inteiro * 100 + (int) str_pad($decimal, 2, '0');
}

function centavos_para_decimal(int $centavos): string
{
    return intdiv($centavos, 100) . '.' . str_pad((string) ($centavos % 100), 2, '0', STR_PAD_LEFT);
}

function validar_produto(array $dados): array
{
    $erros = [];
    foreach (['codigo' => 50, 'marca' => 80, 'modelo' => 120, 'descricao' => 10000] as $campo => $limite) {
        if ($dados[$campo] === '' || mb_strlen($dados[$campo], 'UTF-8') > $limite) {
            $erros[] = 'Preencha ' . $campo . ' com até ' . $limite . ' caracteres.';
        }
    }
    if (filter_var($dados['quantidade_estoque'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 2147483647]]) === false) {
        $erros[] = 'O estoque deve ser um número inteiro não negativo.';
    }
    if (!preg_match('/\A(?:0|[1-9][0-9]{0,7})(?:\.[0-9]{1,2})?\z/', $dados['preco']) || preco_em_centavos($dados['preco']) <= 0) {
        $erros[] = 'Informe um preço positivo, com até duas casas decimais e sem separador de milhar.';
    }
    return $erros;
}

function receber_imagem(?array $arquivo, bool $obrigatoria): ?string
{
    if (!$arquivo || ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        if ($obrigatoria) {
            throw new InvalidArgumentException('Selecione uma imagem JPEG, PNG ou WebP de até 2 MB.');
        }
        return null;
    }
    if (!is_int($arquivo['error'] ?? null) || $arquivo['error'] !== UPLOAD_ERR_OK
        || !is_string($arquivo['tmp_name'] ?? null) || !is_string($arquivo['name'] ?? null)
        || !is_uploaded_file($arquivo['tmp_name'])) {
        throw new InvalidArgumentException('Não foi possível receber a imagem. Verifique o arquivo e seu tamanho.');
    }
    $tamanho = filesize($arquivo['tmp_name']);
    if ($tamanho === false || $tamanho < 1 || $tamanho > 2 * 1024 * 1024) {
        throw new InvalidArgumentException('A imagem deve ter no máximo 2 MB.');
    }
    $tipos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    if (!isset($tipos[$mime]) || !in_array($extensao, ['jpg', 'jpeg', 'png', 'webp'], true)
        || ($mime === 'image/jpeg' ? !in_array($extensao, ['jpg', 'jpeg'], true) : $extensao !== $tipos[$mime])
        || @getimagesize($arquivo['tmp_name']) === false) {
        throw new InvalidArgumentException('Envie uma imagem JPEG, PNG ou WebP válida, com extensão correspondente ao conteúdo.');
    }
    $caminho = 'assets/uploads/' . bin2hex(random_bytes(16)) . '.' . $tipos[$mime];
    if (!move_uploaded_file($arquivo['tmp_name'], __DIR__ . '/../' . $caminho)) {
        throw new RuntimeException('Não foi possível salvar a imagem.');
    }
    return $caminho;
}

function remover_imagem_enviada(?string $caminho): void
{
    // Somente os arquivos aleatórios deste módulo podem ser removidos.
    if ($caminho && preg_match('~\Aassets/uploads/[a-f0-9]{32}\.(?:jpg|png|webp)\z~', $caminho)) {
        $arquivo = __DIR__ . '/../' . $caminho;
        if (is_file($arquivo) && !unlink($arquivo)) {
            error_log('Loja Mobile: não foi possível remover uma imagem antiga.');
        }
    }
}
