# Especificação — Loja Mobile

## Resumo e método

E-commerce acadêmico de celulares da disciplina Desenvolvimento Web II, DSM,
Fatec. Desenvolvimento incremental com uma abordagem leve de Spec-Driven
Development: definir os requisitos da etapa, implementar somente esse recorte,
validar os critérios de aceite e documentar o resultado antes de avançar.

## Tecnologias e limites

PHP 8.x procedural e funções; MySQL/MariaDB via PDO; HTML5; CSS3; JavaScript
puro apenas quando necessário; XAMPP e Git. Sem frameworks, ORM, Composer,
APIs externas ou dependências adicionais. A etapa 01 não exige JavaScript.

## Requisitos completos previstos

1. Landing page e catálogo de celulares.
2. Cadastro de clientes, login e logout.
3. Perfis de acesso `cliente` e `admin`.
4. Detalhes do produto.
5. Carrinho armazenado na sessão PHP.
6. Checkout simulado, sem pagamento real.
7. Registro de pedidos e itens com preço da compra.
8. Painel administrativo autenticado e autorizado.
9. Cadastro, consulta, edição e exclusão de produtos.
10. Upload de imagem por produto.
11. Controle de estoque e baixa após confirmar pedido.

## Etapas previstas

| Etapa | Entrega | Situação |
| --- | --- | --- |
| 01 | Estrutura, banco, PDO, landing page e documentação | Implementada |
| 02 | Cadastro, autenticação, sessão e perfis de acesso | Prevista; aguarda autorização |
| 03 | Detalhes dos produtos e carrinho em sessão | Prevista |
| 04 | Checkout simulado, pedidos e baixa de estoque | Prevista |
| 05 | Administração de produtos e upload validado | Prevista |
| 06 | Revisão integrada e documentação de entrega | Prevista |

A ordem futura poderá ser ajustada conforme a disciplina. A existência das
tabelas de pedidos e usuários não significa que seus módulos estejam prontos.

## Etapa 01 — requisitos e critérios de aceite

- Estrutura com `admin`, `config`, `includes`, `assets`, `database` e `docs`.
- Apenas arquivos PHP usados nesta etapa; nenhuma rota futura simulada.
- `schema.sql` cria o banco e exatamente quatro tabelas com InnoDB e `utf8mb4`.
- `seed.sql` separado cria poucos produtos fictícios e nenhum usuário.
- Exemplo de conexão versionável e configuração real ignorada pelo Git.
- `includes/conexao.php` usa PDO com prepared statements nativos e exceções.
- `index.php` consulta produtos por função e renderiza marca, modelo, imagem e
  preço; cabeçalho e rodapé são includes e CSS é separado.
- Existem mensagens distintas para banco indisponível e tabela sem produtos.
- O layout adapta-se a desktop e celular, e os links levam a seções existentes.
- Textos do banco são escapados e caminhos de imagem têm validação e fallback.
- README explica configuração, importação e execução no XAMPP.
- Nenhum login, carrinho, checkout, upload ou painel é implementado nesta fase.

## Modelo de dados

| Tabela | Campos e responsabilidades |
| --- | --- |
| `usuarios` | `id`, `nome`, `email` único, `senha` para hash, `perfil`, `criado_em` |
| `produtos` | `id`, `codigo` único, `marca`, `modelo`, `descricao`, `quantidade_estoque`, `preco`, `imagem`, `criado_em` |
| `pedidos` | `id`, `usuario_id` (FK), `total`, `status`, `criado_em` |
| `pedido_itens` | `id`, `pedido_id` (FK), `produto_id` (FK), `quantidade`, `preco_unitario` |

Um usuário possui vários pedidos. Um pedido possui vários itens. Cada item
referencia um produto. O par pedido/produto é único; a quantidade representa as
unidades do mesmo produto. Exclusão de registros referenciados é restrita.

Valores monetários usam `DECIMAL(10,2)`. Estoque e preços não podem ser negativos,
e a quantidade de um item deve ser positiva. Perfil e status usam `ENUM`;
os estados previstos do pedido são `pendente`, `confirmado` e `cancelado`.
`imagem` pode ser nula; a aplicação resolve a imagem padrão local.

## Segurança por etapa

Na fundação: PDO, conexão `utf8mb4`, escaping com `htmlspecialchars`, mensagens
genéricas de erro, configuração ignorada no Git, caminhos locais de imagens e
constraints de integridade. O projeto não coleta dados pessoais nesta etapa.

Ao criar autenticação: `password_hash()` e `password_verify()`, validação no
servidor, `session_start()`, regeneração de ID de sessão após login, autorização
por perfil e proteção CSRF em operações que alterem dados.

Ao criar upload: validar extensão, tipo MIME com `fileinfo` e tamanho; gerar
nomes próprios, rejeitar conteúdo executável e restringir execução na pasta de
uploads. SVGs locais desta etapa são arquivos confiáveis do projeto e não
representam um formato liberado para futuros uploads de usuários.

Ao criar checkout: transação SQL, validação de estoque no servidor com bloqueio
adequado, total recalculado a partir do banco e baixa de estoque somente na
confirmação. Não confiar em preços ou quantidades enviados pelo navegador.

## Validação

Usar o checklist do README e registrar somente verificações efetivamente
executadas. A aceitação no ambiente do aluno inclui Apache/MySQL iniciados no
XAMPP, importação pelo phpMyAdmin, configuração local e verificação visual em
desktop e celular. Não avançar para a etapa 02 sem nova autorização.
