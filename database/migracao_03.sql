-- Somente para bancos criados nas etapas 01/02. Execute uma única vez.
-- Instalações novas já recebem esta coluna em schema.sql.
USE loja_mobile;
ALTER TABLE pedidos
    ADD COLUMN chave_checkout CHAR(64) DEFAULT NULL AFTER usuario_id,
    ADD CONSTRAINT uq_pedidos_checkout UNIQUE (chave_checkout);
