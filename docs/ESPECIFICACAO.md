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
| 02 | Cadastro, autenticação, sessão e perfis de acesso | Implementada |
| 03 | Administração de produtos e upload validado | Prevista; aguarda autorização |
| 04 | Detalhes dos produtos e carrinho em sessão | Prevista |
| 05 | Checkout simulado, pedidos e baixa de estoque | Prevista |
| 06 | Revisão integrada e documentação de entrega | Prevista |

A ordem futura poderá ser ajustada conforme a disciplina. A existência das
tabelas de pedidos e usuários não significa que seus módulos estejam prontos.

A previsão da etapa 03 foi ajustada conforme o prompt de autenticação: o
gerenciamento de produtos será a próxima entrega, somente após nova autorização.

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

## Etapa 02 — requisitos e critérios de aceite

- `cadastro.php`: nome, e-mail, senha e confirmação obrigatórios; validação no
  servidor; e-mail válido e único; senha com mínimo de 8 caracteres e limite
  técnico de 72 bytes para evitar truncamento do bcrypt; confirmação igual.
- Nome limitado a 120 caracteres e e-mail a 254, de acordo com o schema. Campos
  enviados como arrays são tratados como inválidos. Senhas nunca são repopuladas.
- Gravação com PDO preparado, `password_hash()` e perfil `cliente` literal no
  servidor. A constraint única do banco também protege contra cadastros
  simultâneos com o mesmo e-mail. Sucesso usa Post/Redirect/Get para o login.
- `login.php`: consulta preparada e `password_verify()`, erro genérico para
  credenciais inválidas e regeneração do ID com `session_regenerate_id(true)`.
- Sessão autenticada contém somente ID, nome e perfil; o token CSRF é separado.
  Clientes vão ao catálogo; administradores vão a `admin/index.php`.
- `includes/autenticacao.php`: inicialização única de sessão, helpers de sessão,
  CSRF, cadastro, consulta e autorização. `exigir_login()` redireciona visitantes;
  `exigir_admin()` bloqueia clientes com HTTP 403 antes de renderizar o painel.
- Cookies de sessão com `HttpOnly`, `SameSite=Lax`, caminho do projeto e `Secure`
  sob HTTPS; modo estrito e uso exclusivo de cookies. Páginas usam `no-store`.
- `logout.php`: somente POST e CSRF válido, limpeza da sessão, invalidação do
  cookie, `session_destroy()` e redirecionamento ao início. GET devolve HTTP 405.
- `admin/index.php`: título, saudação escapada, previsão de CRUD na etapa 03,
  retorno à loja e opção de sair. Sem gerenciamento de produtos nesta etapa.
- Navegação para visitantes com entrar/cadastrar; autenticados com nome/sair e
  link do painel somente para admin. Links e assets funcionam na raiz e dentro
  de uma subpasta do XAMPP. O catálogo permanece público.
- Formulários reutilizam o CSS local e são responsivos, com labels e mensagens
  acessíveis. Nenhuma dependência nova é instalada.
- Promoção de cliente a admin é manual por SQL documentado no README. Exige
  novo login para refletir o novo perfil; nenhuma senha fixa é distribuída.
- Sem carrinho, checkout, pedidos, CRUD, uploads, recuperação de senha ou
  confirmação de e-mail. O schema permanece o mesmo da fundação.

## Segurança por etapa

Na fundação: PDO, conexão `utf8mb4`, escaping com `htmlspecialchars`, mensagens
genéricas de erro, configuração ignorada no Git, caminhos locais de imagens e
constraints de integridade. O projeto não coleta dados pessoais nesta etapa.

Na etapa 02: `password_hash()` e `password_verify()`, validação no servidor,
`session_start()`, regeneração de ID após login, autorização por perfil e
proteção CSRF em cadastro, login e logout. Detalhes internos de erros de banco
não são apresentados ao usuário.

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
desktop e celular. Não avançar para a etapa 03 sem nova autorização.
