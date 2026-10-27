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

## Etapa 03 — Finalização do e-commerce

Concluída em 26/10/2026, preservando o histórico das validações anteriores acima.
O prompt de finalização autorizou CRUD, carrinho, checkout e pedidos nesta entrega.

### Ambiente utilizado

Windows, PHP 8.2.12, Apache 2.4.58 e MariaDB 10.4.32 do XAMPP instalado. Foi
criada uma configuração Apache isolada em 127.0.0.1:8080 e uma instância MariaDB
isolada em 127.0.0.1:3307, com banco loja_mobile_teste. Nenhum banco ou serviço
preexistente foi modificado. O Apache usou os .htaccess reais do projeto.

### Testes executados e aprovados

| Área | Resultado |
| --- | --- |
| Sintaxe | php -l nos 23 arquivos PHP entregues, sem erros |
| Apache | Configuração temporária aprovada por httpd -t; aplicação servida com o módulo PHP do XAMPP |
| Instalação | Schema/seed importados; seed reimportado sem duplicar produtos |
| Migração | Schema antigo do commit 079c6cf migrado em banco isolado; pedido preexistente e total preservados, com chave nula |
| Cadastro/login | Cadastro válido, hash, perfil cliente forçado, duplicidade, confirmação divergente e senha incorreta testados por HTTP |
| Permissões | Visitantes redirecionados; clientes bloqueados no painel, cadastro/edição e exclusão; admin impedido de finalizar como cliente |
| Produtos | Cadastro, código duplicado, edição sem upload, preço positivo, estoque não negativo, exclusão e bloqueio de exclusão de produto vendido |
| Imagens | PNG, JPEG e WebP reais aceitos; arquivo falso, extensão executável e tamanho maior que 2 MB rejeitados; substituição remove imagem anterior |
| Proteção Apache | Bloqueio de .git, configuração, includes, SQL, testes e diretório temporário; pasta de uploads sem listagem |
| Execução em uploads | Arquivo PHP de prova colocado localmente em uploads recebeu HTTP 403 e não foi executado |
| Carrinho | Adição, alteração 2→3, remoção, recusa de quantidade superior ao estoque e manutenção do carrinho após login |
| Preços | Valor enviado pelo navegador ignorado; preço atual do banco usado; mudança durante revisão exige nova confirmação |
| Checkout | Pedido confirmado, itens persistidos, preço unitário preservado, baixa exata do estoque e carrinho limpo após commit |
| Reenvio | Mesma confirmação retorna o mesmo pedido sem nova baixa de estoque |
| Falta de estoque | Produto esgotado não oferece compra; esgotamento após revisão recusa checkout e não deixa pedido parcial |
| Confirmação | Proprietário acessa; outra conta recebe HTTP 404 |
| Painel | Lista pedido persistido com número, cliente, valor, data e status |
| Segurança | Payloads SQL Injection em ID/login recusados; XSS em modelo/descrição escapado; CSRF de CRUD/carrinho/checkout/logout recusado |
| Múltiplos itens | Pedido com dois produtos/quantidades 2 e 3 confirmou total 40.50 e baixas corretas; falha no segundo não altera o primeiro |
| Concorrência | Dois processos PHP disputaram uma unidade: um pedido confirmado, um recusado e estoque final zero |
| Idempotência concorrente | Dois processos com a mesma chave retornaram o mesmo ID e reduziram estoque uma única vez |
| Rollback induzido | Trigger de teste provocou falha SQL após iniciar a gravação; nenhum pedido/item parcial persistiu e estoque permaneceu intacto |
| Caminhos | Roteiro HTTP completo passou na raiz e em /Loja-Mobile/ sob Apache |
| Navegador | Catálogo → detalhe → 2 unidades no carrinho → cadastro → login → revisão → confirmação real do pedido no banco |
| Responsividade | Detalhe, carrinho, painel e formulário de produtos conferidos em celular de 390 px, sem overflow horizontal da página; tabelas têm rolagem interna |

O pedido criado pelo navegador totalizou R$ 2.599,80; a confirmação foi aberta
com os dados persistidos e o painel mostrou estoque de 12 para 10. Não houve
pagamento real. A prévia teve uma interrupção de comunicação, recuperada em uma
nova aba; o pedido foi conferido por consulta PDO e pela página de confirmação.

### Scripts entregues

- tests/integracao.php: fluxo HTTP real com cookies, multipart, uploads e
  verificações no banco. Aceita apenas URL local com porta explícita e banco _teste.
- tests/concorrencia.php e tests/compra-concorrente.php: dois processos independentes
  para disputa de estoque, chave duplicada e rollback com falha SQL induzida.
- tests/fixtures: imagens mínimas locais JPEG, PNG e WebP; não dependem de GD
  para executar os testes. Nenhum framework ou pacote foi instalado.

Os roteiros removem somente os registros/arquivos de sua própria execução.
Uma dificuldade de limpeza de diretórios temporários no Windows foi resolvida
usando fixtures fixas e arquivos temporários individuais. A execução final passou
sem avisos ou erros de limpeza.

### Correções e decisões verificadas

- O login agora preserva o carrinho do visitante ao regenerar a sessão.
- Links do catálogo levam aos detalhes; navegação oferece carrinho funcional.
- Uploads são validados no servidor e servidos como arquivos estáticos pelo Apache.
- Diretórios internos e metadados Git têm bloqueio HTTP explícito.
- Cálculos usam centavos inteiros; checkout tem locks, transação e chave única.
- Mudança de preço exige nova revisão, evitando confirmação silenciosa de outro total.
- Pedidos preservam o preço unitário; FK impede apagar produtos vendidos.
- Tabelas administrativas têm largura mínima e rolagem interna para leitura no celular.

### Limitações reais e encerramento

Não foi testada uma conexão HTTPS: o atributo Secure é condicionado a HTTPS;
os testes usaram HTTP local. A interface gráfica do phpMyAdmin não foi usada
para importação; os mesmos SQLs foram executados pelo PDO no MariaDB do XAMPP.
Não foram testados outros sistemas operacionais/versões de MySQL.

Não há pendência funcional conhecida para o escopo solicitado. Frete, pagamentos,
recuperação de senha e confirmação de e-mail não fazem parte do projeto.
A instalação e o uso estão documentados no README; nenhum teste foi transferido
ao usuário. Configuração, serviços e dados temporários foram removidos ao final.
Commits separados registram administração, compra e testes/documentação, além
dos dois commits das etapas anteriores. O remoto de entrega é privado e não
inclui credenciais, configuração local, sessões ou uploads.

Repositório verificado: https://github.com/dernivaljr/Loja-Mobile (privado).

## Etapa 04 — Apresentação, perfis locais e execução persistente no XAMPP

Validação executada em 26/10/2026. Os registros históricos acima foram
preservados; as alterações de política e apresentação desta etapa estão abaixo.

### Ambiente e URL confirmados

- XAMPP existente em C:\xampp; Apache 2.4.58, PHP 8.2.12 e MariaDB 10.4.32.
- Inspeção inicial confirmou que Apache e MySQL não estavam ativos. Ambos
  foram iniciados em segundo plano, sem interromper serviços existentes.
- Apache normal nas portas 80/443 e MySQL normal na 3306. Configurações globais
  desses serviços não foram alteradas.
- Vínculo C:\xampp\htdocs\Loja-Mobile para o checkout existente; o destino não
  existia e nenhum arquivo de outra aplicação foi substituído.
- Banco novo e isolado, terminado em _teste, importado com schema/seed atuais.
  Não houve alteração de estrutura SQL nesta etapa nem substituição de bancos.
- Conta MySQL específica, com senha aleatória e permissões somente no banco
  da loja. Conexão gravada em config/banco.php, ignorado pelo Git.
- URL real aberta no navegador e confirmada por HTTP: http://localhost/Loja-Mobile/.
- Apache, MySQL, vínculo, banco e conexão local são mantidos funcionando após
  a entrega, conforme solicitado. Não foram usados servidores PHP temporários.

### Cadastro e segurança por origem

- Formulário oferece Cliente e Administrador; escolha preservada após validação.
- A política é aplicada no controlador e novamente na gravação por prepared
  statement: admin somente com REMOTE_ADDR igual a 127.0.0.1 ou ::1.
- Host, X-Forwarded-For, X-Real-IP e Forwarded não autorizam perfil admin.
- tests/perfis.php verificou gravações reais com endereços IPv4/IPv6 locais,
  endereço remoto, 127.0.0.2, IPv4 mapeado em IPv6 e ausência de endereço.
  Somente os dois endereços explicitamente permitidos gravaram admin.
- tests/perfis-http.php executou requisições HTTP reais por 127.0.0.1 e ::1
  com Host externo: ambas criaram admin. Pelo IPv4 de rede do próprio computador,
  enviando Host localhost e cabeçalhos de proxy locais falsificados, gravou cliente.
- Hash de senha, validações, CSRF, sessões e redirecionamento ao login preservados.
- tests/integracao.php foi adaptado à nova política: criação de admin local pelo
  formulário, sem promoção SQL. Seus demais cenários e verificações foram preservados.

### Testes executados e resultados

| Verificação | Resultado |
| --- | --- |
| Sintaxe | php -l nos 25 arquivos PHP versionados, sem erros |
| Integração HTTP existente | Todos os cenários aprovados no Apache normal em 127.0.0.1:80/Loja-Mobile/ |
| Concorrência existente | Última unidade vendida uma vez; confirmação duplicada retorna mesmo pedido; rollback induzido preserva estoque e integridade |
| Política de perfis | Matriz de endereços e requisições reais IPv4/IPv6/rede aprovadas, incluindo falsificação de cabeçalhos |
| Catálogo/imagens | HTTP 200 e todas as imagens carregadas no navegador |
| Cadastro de admin local | Selecionado Administrador, cadastro concluído, login direcionado ao painel |
| Cadastro de cliente | Selecionado Cliente, cadastro concluído e login direcionado à loja |
| Produto com upload | Cadastrado pelo navegador com PNG real e persistido no banco |
| Edição | Preço alterado de 1599.90 para 1499.90 e estoque de 4 para 6, preservando a imagem |
| Carrinho | Produto adicionado e quantidade alterada de 1 para 2 pelo navegador; total atualizado para 2999.80 |
| Checkout | Botão Confirmar pedido concluiu pedido com duas unidades, total 2999.80 e status confirmado |
| Banco e estoque | Consulta PDO confirmou quantidade 2, preço unitário 1499.90 e estoque de 6 para 4 |
| Painel de pedidos | Pedido criado pelo navegador exibido com cliente, valor, data e status |
| Permissões | Cliente acessando a URL do painel recebeu acesso restrito; bloqueios HTTP existentes também passaram |
| Exclusão | Produto descartável excluído pelo formulário administrativo após limpar somente seu pedido descartável |
| Logout | Realizado para cliente e admin; navegação retornou ao estado visitante |
| Segurança existente | SQL Injection, XSS, CSRF, upload inválido, código duplicado, excesso de estoque e pedido de outra conta aprovados pelo roteiro existente |
| Layout | Desktop 1280 px e celular 390 px conferidos, incluindo seleção de perfil, checkout e navegação; sem overflow horizontal da página |
| Textos | Páginas públicas sem referências acadêmicas, protótipo, teste ou demonstração; checkout informa ausência de pagamento online |

As contas, o produto e o pedido usados no navegador eram descartáveis, com
identificadores aleatórios próprios. A limpeza removeu somente esses registros
e a imagem enviada, sem redefinir estoque nem excluir dados de outras contas.

### Apresentação, documentação e limitações

Cabeçalho, rodapé, landing page, catálogo, cadastro, login, detalhes, carrinho,
checkout e confirmação receberam linguagem de e-commerce. Nenhuma promessa de
pagamento integrado, entrega ou e-mail automático foi adicionada. A apresentação
administrativa foi revisada e sua gestão existente preservada.

README atualizado somente com descrição, funcionalidades, tecnologias,
requisitos, configuração/importação, inicialização, contas e uso da loja/painel,
além da estrutura resumida. Seção e comandos de testes removidos do README.
Scripts anteriores mantidos em tests; novos testes de origem também em tests.

Limitação: navegação validada em HTTP local. HTTPS com certificado confiável
não foi validado; o certificado padrão do XAMPP não foi usado para contornar
avisos do navegador. Nenhuma intervenção manual foi solicitada para os testes.

Configuração e dados locais continuam fora do Git. O commit desta etapa é enviado
ao remoto já configurado, preservando a visibilidade privada do repositório.
