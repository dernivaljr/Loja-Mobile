# Validação — Loja Mobile

## Etapa 01 — Fundação

Executada em 26/10/2026 no Windows, com PHP 8.2.12 e MariaDB 10.4.32 do XAMPP.
Foi utilizada uma instância MariaDB isolada em uma pasta temporária do projeto,
na interface `127.0.0.1:3307`, e o servidor de desenvolvimento PHP em
`127.0.0.1:8080`. Nenhum banco preexistente do XAMPP foi alterado.

### Verificações executadas e aprovadas

- `php -l` nos seis arquivos PHP entregues, sem erros de sintaxe.
- Importação real de `schema.sql` e reimportação sem erro: exatamente quatro
  tabelas no banco criado para teste.
- Importação real e reimportação de `seed.sql`: quatro produtos, sem duplicações
  e sem usuários criados. Acentuação de `Órbita` preservada.
- Consulta real ao MySQL via PDO e resposta HTTP 200 com quatro cards de
  produtos e preços em reais, incluindo `R$ 1.299,90`.
- Banco sem produtos: HTTP 200 e mensagem de catálogo vazio.
- Configuração ausente e conexão recusada: HTTP 503 e mensagem amigável,
  sem stack trace, SQLSTATE ou credenciais no HTML.
- Constraints rejeitaram estoque, preço, total e preço unitário negativos;
  quantidade zero; código e email duplicados; produto repetido no pedido;
  pedido sem usuário existente e exclusão de registros com vínculos de pedidos.
  Os registros usados nesses testes foram revertidos por transação.
- Conteúdo `<script>` inserido temporariamente no modelo do produto foi
  escapado tanto no texto quanto no atributo HTML, e o valor original foi
  restaurado após o teste.
- Caminhos de imagem nulos, externos, inexistentes e com travessia de diretórios
  foram substituídos pela imagem padrão local.
- Todos os caminhos de CSS, favicon e imagens encontrados na página existem
  e responderam HTTP 200. SVGs passaram pela leitura XML.
- Todas as âncoras de navegação correspondem a IDs existentes na página.
- Conferência visual pelo navegador em desktop (1280 px) e celular (390 px),
  incluindo banner e catálogo em duas colunas no celular. Imagens carregadas;
  sem rolagem horizontal. Também foi verificada ausência de overflow a 320 px.
- Link “Explorar celulares” e navegação “Início”/“Celulares” conferidos no navegador.
  Uma falha inicial no filtro de sombra dos SVGs foi corrigida e as imagens
  foram conferidas novamente no desktop e no celular.

### Validação manual ainda necessária no ambiente do aluno

- Copiar o projeto para `htdocs`, iniciar Apache/MySQL e importar os scripts
  pela interface do phpMyAdmin, seguindo o README.
- Criar `config/banco.php` com a configuração do banco local definitivo.
- Conferir as regras `.htaccess` sob o Apache, inclusive bloqueio de acesso
  HTTP a arquivos SQL/Markdown/ocultos e listagem de diretórios. O servidor
  embutido do PHP utilizado nos testes não aplica essas regras.

Os serviços, a configuração de teste e os dados temporários foram removidos ao
final. Nenhum teste de módulos futuros foi executado ou declarado como pronto.
O diretório original não era um repositório Git; não houve commit, remoto ou push.

## Etapa 02 — Autenticação e controle de acesso

Executada em 26/10/2026 com PHP 8.2.12, extensões PDO/MySQL, mbstring e cURL,
MariaDB 10.4.32 e navegador integrado. Nenhum pacote foi instalado. A extensão
cURL foi utilizada somente pelo roteiro temporário de testes, não pela loja.

Como não havia `config/banco.php`, não existia conexão configurada para um
banco definitivo. Os scripts originais foram importados em uma instância
MariaDB isolada em `127.0.0.1:3307`. A inspeção real por `SHOW FULL COLUMNS FROM
usuarios` confirmou `id` unsigned/PK, `nome` (120), `email` (254/único), `senha`
(255), `perfil` ENUM cliente/admin e `criado_em`. Nenhuma migração foi necessária.

O servidor PHP foi executado em `127.0.0.1:8080`; uma cópia temporária também
foi servida em `127.0.0.1:8081/Loja-Mobile/` para reproduzir uma instalação em
subpasta. Dados, cookies e sessões usados nos testes eram fictícios e isolados.

### Testes obrigatórios efetivamente executados

| Teste | Resultado observado |
| --- | --- |
| Cadastro válido | Registro gravado com hash verificável e perfil cliente; HTTP 303 para login e mensagem de sucesso exibida uma vez |
| E-mail duplicado | Mensagem compreensível e nenhum registro adicional |
| Confirmação de senha diferente | Rejeitada no servidor; senha não reapresentada no HTML |
| Login correto | Cliente redirecionado à loja e admin ao painel |
| Senha incorreta | Mesma mensagem genérica usada para e-mail desconhecido |
| Persistência da sessão | Navegação pelo catálogo mantém nome e perfil; ID da sessão muda no login |
| Logout | POST válido destrói sessão, invalida cookie e redireciona; cookie antigo não recupera autenticação |
| Painel sem login | HTTP 303 para o login antes de renderizar o painel |
| Painel como cliente | HTTP 403 ao digitar a URL diretamente |
| Painel como administrador | HTTP 200 com saudação escapada, aviso da etapa 03, retorno e opção Sair |
| Catálogo para visitantes | HTTP 200 com os quatro produtos originais e links de entrar/cadastrar |
| Responsividade | Cadastro e login conferidos visualmente em desktop e celular (390 px), sem rolagem horizontal |

### Verificações complementares executadas

- `php -l` nos 12 arquivos PHP versionados: sem erros de sintaxe.
- `git diff --check`: sem erros de whitespace.
- Cadastro rejeitou nome vazio ou maior que 120 caracteres, e-mail inválido,
  senha curta, senha maior que 72 bytes e campos enviados como arrays.
- O envio de `perfil=admin` no cadastro foi ignorado; o banco recebeu `cliente`.
- POST sem CSRF válido em cadastro, login e logout recebeu HTTP 403. Logout
  por GET recebeu HTTP 405 e `Allow: POST`, preservando a autenticação.
- A sessão anterior ao login foi excluída após regeneração. A sessão autenticada
  não continha senha, hash ou e-mail; o arquivo foi destruído no logout.
- Cookies foram conferidos com `HttpOnly`, `SameSite=Lax` e caminho correto.
  Na subpasta, tanto criação quanto remoção usaram `/Loja-Mobile/`.
- Nome com marcação HTML foi escapado no formulário, cabeçalho e painel.
- Login, cadastro e catálogo sem configuração de banco trataram o erro com
  HTTP 503 e mensagens amigáveis, sem exibir exceções ao usuário.
- Cadastro, login, logout, navegação e bloqueio do cliente também passaram na
  instalação em subpasta; CSS respondeu HTTP 200 e os redirecionamentos
  preservaram `/Loja-Mobile/`. Um problema inicial de separador de diretórios
  no Windows foi corrigido antes da aprovação desses testes.
- Fluxo completo também executado no navegador: cadastro, mensagem de sucesso,
  login de cliente, catálogo autenticado e logout. Depois da promoção manual
  da conta fictícia e de novo login, o painel foi conferido no celular e desktop.

### Arquivos da etapa

Criados: `cadastro.php`, `login.php`, `logout.php`, `admin/index.php`,
`includes/autenticacao.php` e `includes/acesso-negado.php`.

Modificados: `index.php`, `includes/cabecalho.php`, `includes/rodape.php`,
`assets/css/style.css`, `admin/README.md`, `README.md`, `docs/ESPECIFICACAO.md`
e este arquivo. O schema, seed, conexão e imagens da fundação foram preservados.

### Pendências de validação manual

- Instalação definitiva em `htdocs`, importação pela interface do phpMyAdmin e
  configuração real do banco, conforme README.
- Conferência sob Apache das regras `.htaccess`, que não são interpretadas pelo
  servidor embutido do PHP utilizado nos testes.
- Cookie `Secure` em HTTPS não foi testado: as verificações locais usaram HTTP.

Os serviços e arquivos temporários foram removidos ao final, incluindo a
configuração de testes. Nenhum banco preexistente do XAMPP foi alterado.

### Versionamento e limite da entrega

O Git foi inicializado nesta etapa. O primeiro commit local, `9b7dfcc`, registra
a fundação sem alterações. A etapa 02 possui um commit separado com a mensagem
`Implementa etapa 02: autenticação e controle de acesso`. A configuração local e
os arquivos temporários foram confirmados como ignorados pelo Git. Não há remoto
configurado e não houve push.

O CRUD de produtos, upload, carrinho, checkout, pedidos, recuperação de senha e
confirmação de e-mail permanecem fora desta entrega. A etapa 03 não foi iniciada.
