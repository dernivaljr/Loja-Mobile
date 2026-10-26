-- Etapa 01: execute no phpMyAdmin antes de importar seed.sql.
-- Não remove nem recria tabelas existentes. Alterações futuras exigem migrações.
CREATE DATABASE IF NOT EXISTS loja_mobile
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE loja_mobile;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(254) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    perfil ENUM('cliente', 'admin') NOT NULL DEFAULT 'cliente',
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_usuarios_email UNIQUE (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS produtos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL,
    marca VARCHAR(80) NOT NULL,
    modelo VARCHAR(120) NOT NULL,
    descricao TEXT NOT NULL,
    quantidade_estoque INT NOT NULL DEFAULT 0,
    preco DECIMAL(10,2) NOT NULL,
    imagem VARCHAR(255) DEFAULT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_produtos_codigo UNIQUE (codigo),
    CONSTRAINT chk_produtos_estoque CHECK (quantidade_estoque >= 0),
    CONSTRAINT chk_produtos_preco CHECK (preco >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pedidos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    chave_checkout CHAR(64) DEFAULT NULL,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pendente', 'confirmado', 'cancelado') NOT NULL DEFAULT 'pendente',
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_pedidos_checkout UNIQUE (chave_checkout),
    CONSTRAINT chk_pedidos_total CHECK (total >= 0),
    CONSTRAINT fk_pedidos_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pedido_itens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT UNSIGNED NOT NULL,
    produto_id INT UNSIGNED NOT NULL,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    CONSTRAINT uq_pedido_itens_produto UNIQUE (pedido_id, produto_id),
    CONSTRAINT chk_pedido_itens_quantidade CHECK (quantidade > 0),
    CONSTRAINT chk_pedido_itens_preco CHECK (preco_unitario >= 0),
    CONSTRAINT fk_itens_pedido FOREIGN KEY (pedido_id)
        REFERENCES pedidos (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_itens_produto FOREIGN KEY (produto_id)
        REFERENCES produtos (id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
