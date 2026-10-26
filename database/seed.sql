USE loja_mobile;
SET NAMES utf8mb4;

-- Marcas, modelos e preços fictícios. Não cria usuários ou senhas públicas.
-- Pode ser reimportado: preserva produtos com os mesmos códigos.
INSERT INTO produtos (codigo, marca, modelo, descricao, quantidade_estoque, preco, imagem)
SELECT 'DEMO-001', 'Aurora', 'A1 · 128 GB', 'Leveza e espaço para as ideias do dia a dia.', 12, 1299.90, 'assets/img/celular-padrao.svg'
WHERE NOT EXISTS (SELECT 1 FROM produtos WHERE codigo = 'DEMO-001');

INSERT INTO produtos (codigo, marca, modelo, descricao, quantidade_estoque, preco, imagem)
SELECT 'DEMO-002', 'Nexo', 'N20 · 256 GB', 'Uma nova perspectiva para cada momento.', 8, 1899.90, 'assets/img/celular-padrao.svg'
WHERE NOT EXISTS (SELECT 1 FROM produtos WHERE codigo = 'DEMO-002');

INSERT INTO produtos (codigo, marca, modelo, descricao, quantidade_estoque, preco, imagem)
SELECT 'DEMO-003', 'Prisma', 'One · 128 GB', 'O essencial para acompanhar o seu ritmo.', 15, 999.00, 'assets/img/celular-padrao.svg'
WHERE NOT EXISTS (SELECT 1 FROM produtos WHERE codigo = 'DEMO-003');

INSERT INTO produtos (codigo, marca, modelo, descricao, quantidade_estoque, preco, imagem)
SELECT 'DEMO-004', 'Órbita', 'Pro · 256 GB', 'Mais possibilidades na palma da sua mão.', 5, 2499.90, 'assets/img/celular-padrao.svg'
WHERE NOT EXISTS (SELECT 1 FROM produtos WHERE codigo = 'DEMO-004');
