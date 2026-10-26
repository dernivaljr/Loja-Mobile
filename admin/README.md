# Administração de produtos e pedidos

Todas as páginas exigem `exigir_admin()` antes de qualquer saída HTML.
Visitantes são redirecionados ao login; clientes recebem HTTP 403.

- `index.php`: lista produtos e pedidos persistidos no banco.
- `produto.php`: cadastro e edição, com upload obrigatório no cadastro e
  substituição opcional na edição. Preço positivo e estoque inteiro não negativo.
- `excluir-produto.php`: aceita somente POST com CSRF. Produtos já referenciados
  por pedidos não são removidos; as chaves estrangeiras preservam o histórico.

Uploads aceitam somente JPEG, PNG e WebP até 2 MB, validando extensão, MIME real,
estrutura de imagem e origem do upload. Nomes são aleatórios; a pasta contém
regras Apache para impedir execução. Ao substituir ou excluir, somente arquivos
gerados pelo módulo em `assets/uploads/` podem ser removidos.

O procedimento de promoção manual de um cliente está no README principal.
