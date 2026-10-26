<?php

function conectar_banco(): PDO
{
    $caminho_configuracao = __DIR__ . '/../config/banco.php';

    if (!is_file($caminho_configuracao)) {
        throw new RuntimeException('Configuração local do banco ausente.');
    }

    $configuracao = require $caminho_configuracao;

    if (!is_array($configuracao)) {
        throw new RuntimeException('Configuração local do banco inválida.');
    }

    foreach (['host', 'porta', 'banco', 'usuario', 'senha'] as $campo) {
        if (!array_key_exists($campo, $configuracao)) {
            throw new RuntimeException('Configuração local do banco incompleta.');
        }
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $configuracao['host'],
        $configuracao['porta'],
        $configuracao['banco']
    );

    try {
        return new PDO($dsn, $configuracao['usuario'], $configuracao['senha'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $erro) {
        // Não propaga a mensagem do driver, que pode revelar dados da conexão.
        throw new RuntimeException('Não foi possível conectar ao banco de dados.');
    }
}
