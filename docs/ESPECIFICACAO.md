# Especificação final — Loja Mobile

## Objetivo e tecnologias

E-commerce acadêmico de celulares para Desenvolvimento Web II, DSM/Fatec.
PHP 8.x procedural/funções, MySQL/MariaDB via PDO, HTML5, CSS3, XAMPP e Git.
Sem frameworks, ORM, Composer, APIs externas ou pagamentos reais.
O prompt 03 autorizou concluir conjuntamente as funcionalidades restantes.

## Entregas

| Entrega | Estado |
| --- | --- |
| Fundação, banco, PDO e catálogo responsivo | Concluída |
| Cadastro, login, logout, sessões e perfis | Concluída |
| CRUD de produtos, estoque e imagens | Concluída |
| Detalhes e carrinho em sessão | Concluída |
| Checkout, pedidos, baixa de estoque e confirmação | Concluída |
| Listagem administrativa de pedidos, testes e documentação | Concluída |

## Critérios de aceite

### Contas e permissões

Cadastro exige nome até 120 caracteres, e-mail válido/único até 254, senha com
mínimo de 8 caracteres/máximo de 72 bytes e confirmação igual. Perfil público
é sempre cliente. Senha usa password_hash e não é repopulada após falha.

Login consulta por prepared statement, usa password_verify e regenera o ID de
sessão. Guarda ID, nome, perfil e os dados necessários de carrinho/CSRF. O
carrinho do visitante é preservado. Credenciais inválidas recebem erro genérico.

Visitantes acessam catálogo/detalhes/carrinho. Painel exige admin; cliente recebe
403 e visitante vai ao login. Checkout exige cliente, conferindo o perfil também
no banco. Logout é POST/CSRF e limpa sessão/cookie. Promoção manual por SQL fica
documentada no README; sem senha fixa ou cadastro público de administrador.

### Produtos

Código único, marca, modelo, descrição, estoque, preço e imagem obrigatórios
no cadastro. Descrição até 10.000 caracteres e demais limites conforme schema.
Estoque inteiro não negativo; preço positivo com até duas casas decimais.
Na edição, não enviar imagem preserva a existente.

Uploads JPEG/PNG/WebP até 2 MB: extensão coerente, MIME real via fileinfo,
estrutura via getimagesize e origem via is_uploaded_file. Nome aleatório em
diretório fixo, proteção Apache contra execução e listagem. SVG somente nos
arquivos confiáveis locais; não em uploads. Substituição/exclusão removem apenas
os arquivos gerados pelo módulo. Produto vinculado a pedido não pode ser excluído.
Toda alteração administrativa exige admin, POST e token CSRF.

### Carrinho e checkout

Sessão guarda mapa produto/quantidade; preços nunca vêm do navegador. Adicionar,
atualizar e remover usam POST/CSRF. Quantidades devem ser positivas e disponíveis.
Itens removidos do banco podem ser retirados do carrinho. Sem reserva de estoque.

Detalhes e catálogo exibem disponibilidade. Produto esgotado não pode ser comprado.
Preços/subtotais/total são recalculados em centavos inteiros, limitados ao valor
suportado pelo DECIMAL(10,2). A revisão exige nova confirmação se preços/itens mudarem.

Checkout em transação bloqueia produtos em ordem fixa por ID com FOR UPDATE,
valida novamente preços/estoque, grava pedido/itens, reduz estoque e faz commit.
Falhas fazem rollback integral. Carrinho só é limpo após commit.

Chave aleatória única no pedido impede duplicação persistente; sessão reconhece
reenvios e redireciona ao mesmo pedido. Itens preservam preço unitário da compra.
Confirmação filtra ID/proprietário e não expõe pedido de outra conta. O painel
lista número, cliente, total, data e status, sem relatórios avançados.

## Modelo de dados

Exatamente quatro tabelas InnoDB e utf8mb4:

| Tabela | Campos |
| --- | --- |
| usuarios | id, nome, email único, senha hash, perfil, criado_em |
| produtos | id, codigo único, marca, modelo, descricao, quantidade_estoque, preco DECIMAL, imagem, criado_em |
| pedidos | id, usuario_id FK, chave_checkout única/nullable, total DECIMAL, status, criado_em |
| pedido_itens | id, pedido_id FK, produto_id FK, quantidade, preco_unitario DECIMAL |

Um usuário tem vários pedidos, com vários itens referenciando produtos. Par
pedido/produto único. Quantidade positiva, valores/estoque não negativos e
exclusão de registros vinculados restrita. Nomes/modelos atuais vêm do produto;
os preços históricos ficam no item.

A evolução do schema adiciona somente pedidos.chave_checkout, necessária para
evitar duplicação sem criar tabela extra. A migracao_03.sql preserva registros
anteriores; instalações novas usam somente schema.sql. O seed contém quatro
produtos e nenhum usuário ou senha.

## Organização e segurança

Includes compartilham PDO, autenticação/CSRF, produtos/imagens, carrinho/checkout,
cabeçalho e rodapé. CSS local mantém a identidade visual e a responsividade.
Validação no servidor, prepared statements e htmlspecialchars nas saídas.
Sessão em modo estrito, cookies HttpOnly/SameSite e Secure em HTTPS; no-store.
Configuração, sessões, uploads e temporários fora do Git. Apache bloqueia
diretórios internos e execução na pasta de uploads. Mensagens de erro são genéricas.

## Validação e limites

Scripts PHP sem frameworks verificam fluxos HTTP, uploads reais, permissões,
persistência, estoque, reenvios concorrentes e rollback após falha SQL induzida.
Só executam com banco terminado em _teste. O roteiro HTTP também exige URL local
com porta explícita. Resultados e limitações em VALIDACAO.md.

Limites intencionais: ambiente acadêmico, sem frete, pagamento, cupons,
relatórios avançados, recuperação de senha ou confirmação de e-mail.
