<?php

require_once __DIR__ . '/funcoes.php';
require_once __DIR__ . '/conexao.php';

function url_loja(string $caminho = ''): string
{
    // Atende tanto à raiz quanto à instalação em /Loja-Mobile no htdocs.
    $diretorio = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    if (str_ends_with($diretorio, '/admin')) {
        $diretorio = str_replace('\\', '/', dirname($diretorio));
    }

    return rtrim($diretorio, '/.') . '/' . ltrim($caminho, '/');
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('loja_mobile_sessao');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => url_loja(),
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Páginas com sessão não devem reaparecer autenticadas a partir do cache.
header('Cache-Control: no-store');

function redirecionar(string $caminho): void
{
    header('Location: ' . url_loja($caminho), true, 303);
    exit;
}

function obter_usuario_logado(): ?array
{
    $usuario = $_SESSION['usuario'] ?? null;
    if (!is_array($usuario) || !is_int($usuario['id'] ?? null)
        || !is_string($usuario['nome'] ?? null)
        || !in_array($usuario['perfil'] ?? null, ['cliente', 'admin'], true)) {
        return null;
    }

    return $usuario;
}

function usuario_logado(): bool
{
    return obter_usuario_logado() !== null;
}

function exigir_login(): void
{
    if (!usuario_logado()) {
        redirecionar('login.php');
    }
}

function exigir_admin(): void
{
    exigir_login();
    if (obter_usuario_logado()['perfil'] !== 'admin') {
        http_response_code(403);
        require __DIR__ . '/acesso-negado.php';
        exit;
    }
}

function token_csrf(): string
{
    if (!isset($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_valido(): bool
{
    $token = $_POST['csrf'] ?? null;
    return is_string($token) && isset($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $token);
}

function campo_post(string $nome): string
{
    return isset($_POST[$nome]) && is_string($_POST[$nome]) ? $_POST[$nome] : '';
}

function buscar_usuario_por_email(PDO $conexao, string $email): ?array
{
    $consulta = $conexao->prepare('SELECT id, nome, email, senha, perfil FROM usuarios WHERE email = :email LIMIT 1');
    $consulta->execute(['email' => $email]);
    return $consulta->fetch() ?: null;
}

function cadastrar_cliente(PDO $conexao, string $nome, string $email, string $senha): void
{
    // O perfil vem exclusivamente do servidor, mesmo que o POST contenha outro.
    $consulta = $conexao->prepare("INSERT INTO usuarios (nome, email, senha, perfil) VALUES (:nome, :email, :senha, 'cliente')");
    $consulta->execute([
        'nome' => $nome,
        'email' => $email,
        'senha' => password_hash($senha, PASSWORD_DEFAULT),
    ]);
}

function autenticar_usuario(array $usuario): void
{
    $carrinho = is_array($_SESSION['carrinho'] ?? null) ? $_SESSION['carrinho'] : [];
    session_regenerate_id(true);
    $_SESSION = [
        'usuario' => [
            'id' => (int) $usuario['id'],
            'nome' => $usuario['nome'],
            'perfil' => $usuario['perfil'],
        ],
        'carrinho' => $carrinho,
    ];
    token_csrf();
}

function encerrar_sessao(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $parametros = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $parametros['path'],
            'domain' => $parametros['domain'],
            'secure' => $parametros['secure'],
            'httponly' => $parametros['httponly'],
            'samesite' => $parametros['samesite'],
        ]);
    }
    session_destroy();
}
