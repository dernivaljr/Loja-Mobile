<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../includes/autenticacao.php';
$pdo = conectar_banco();
if (!str_ends_with($pdo->query('SELECT DATABASE()')->fetchColumn(), '_teste')) {
    exit('Use um banco descartável terminado em _teste.' . PHP_EOL);
}
$endereco_original = $_SERVER['REMOTE_ADDR'] ?? null;
$host_original = $_SERVER['HTTP_HOST'] ?? null;
$encaminhado_original = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;
$prefixo = bin2hex(random_bytes(6));
$pdo->beginTransaction();
try {
    foreach (['127.0.0.1' => 'admin', '::1' => 'admin', '192.0.2.10' => 'cliente', '127.0.0.2' => 'cliente', '::ffff:127.0.0.1' => 'cliente', '' => 'cliente'] as $endereco => $esperado) {
        $_SERVER['REMOTE_ADDR'] = $endereco;
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '127.0.0.1';
        $_SERVER['HTTP_X_REAL_IP'] = '::1';
        $email = $prefixo . '-' . bin2hex(random_bytes(4)) . '@example.invalid';
        cadastrar_usuario($pdo, 'Verificação de perfil', $email, bin2hex(random_bytes(16)), 'admin');
        $usuario = buscar_usuario_por_email($pdo, $email);
        if ($usuario['perfil'] !== $esperado) { throw new RuntimeException('Falha na política de perfil por endereço de conexão.'); }
        echo 'OK: REMOTE_ADDR=' . ($endereco ?: '(ausente)') . ' => ' . $esperado . ', ignorando Host e cabeçalhos de proxy.' . PHP_EOL;
    }
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    if (perfil_permitido_cadastro('invalido') !== 'cliente' || perfil_permitido_cadastro('cliente') !== 'cliente') {
        throw new RuntimeException('Perfil inválido não normalizado.');
    }
    echo 'OK: cliente e perfil inválido permanecem cliente no acesso local.' . PHP_EOL;
} finally {
    $pdo->rollBack();
    $_SERVER['REMOTE_ADDR'] = $endereco_original;
    $_SERVER['HTTP_HOST'] = $host_original;
    $_SERVER['HTTP_X_FORWARDED_FOR'] = $encaminhado_original;
    unset($_SERVER['HTTP_X_REAL_IP']);
}
