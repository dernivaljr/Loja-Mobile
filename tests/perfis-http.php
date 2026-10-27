<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../includes/conexao.php';
$pdo = conectar_banco();
if (!str_ends_with($pdo->query('SELECT DATABASE()')->fetchColumn(), '_teste')) {
    exit('Use um banco descartável terminado em _teste.' . PHP_EOL);
}
$ip = getenv('LOJA_TESTE_IP_REMOTO');
if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || str_starts_with($ip, '127.')) {
    exit('Defina LOJA_TESTE_IP_REMOTO com um IPv4 do próprio computador fora de 127/8.' . PHP_EOL);
}
$casos = [
    ['http://127.0.0.1/Loja-Mobile/', 'Host: loja.example.invalid', 'admin'],
    ['http://[::1]/Loja-Mobile/', 'Host: loja.example.invalid', 'admin'],
    ['http://' . $ip . '/Loja-Mobile/', 'Host: localhost', 'cliente'],
];
$emails = [];
try {
    foreach ($casos as [$base, $host, $esperado]) {
        $curl = curl_init($base . 'cadastro.php');
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEFILE => '', CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 10, CURLOPT_PROXY => '', CURLOPT_HTTPHEADER => [$host, 'X-Forwarded-For: 127.0.0.1', 'X-Real-IP: ::1', 'Forwarded: for=127.0.0.1']]);
        $html = curl_exec($curl);
        if ($html === false) { throw new RuntimeException(curl_error($curl)); }
        preg_match('/name="csrf" value="([a-f0-9]+)"/', $html, $token);
        if (!isset($token[1])) { throw new RuntimeException('Formulário inacessível no endereço de teste.'); }
        $email = 'perfil-' . bin2hex(random_bytes(8)) . '@example.invalid'; $emails[] = $email;
        $senha = bin2hex(random_bytes(16));
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(['csrf' => $token[1], 'nome' => 'Verificação de origem', 'email' => $email, 'senha' => $senha, 'confirmacao_senha' => $senha, 'perfil' => 'admin']));
        curl_exec($curl);
        if (curl_getinfo($curl, CURLINFO_HTTP_CODE) !== 303) { throw new RuntimeException('Cadastro não concluído.'); }
        $consulta = $pdo->prepare('SELECT perfil, senha FROM usuarios WHERE email=?'); $consulta->execute([$email]); $usuario = $consulta->fetch();
        if (!$usuario || $usuario['perfil'] !== $esperado || !password_verify($senha, $usuario['senha'])) { throw new RuntimeException('Perfil gravado não corresponde à conexão.'); }
        echo 'OK: requisição HTTP real por ' . $base . ' grava ' . $esperado . ' mesmo com Host/proxy falsificados.' . PHP_EOL;
    }
} finally {
    foreach ($emails as $email) { $pdo->prepare('DELETE FROM usuarios WHERE email=?')->execute([$email]); }
}
