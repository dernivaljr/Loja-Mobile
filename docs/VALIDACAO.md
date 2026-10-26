# Validação da etapa 01

Executada em 26/10/2026 no Windows, com PHP 8.2.12 e MariaDB 10.4.32 do XAMPP.
Foi utilizada uma instância MariaDB isolada em uma pasta temporária do projeto,
na interface `127.0.0.1:3307`, e o servidor de desenvolvimento PHP em
`127.0.0.1:8080`. Nenhum banco preexistente do XAMPP foi alterado.

## Verificações executadas e aprovadas

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

## Validação manual ainda necessária no ambiente do aluno

- Copiar o projeto para `htdocs`, iniciar Apache/MySQL e importar os scripts
  pela interface do phpMyAdmin, seguindo o README.
- Criar `config/banco.php` com a configuração do banco local definitivo.
- Conferir as regras `.htaccess` sob o Apache, inclusive bloqueio de acesso
  HTTP a arquivos SQL/Markdown/ocultos e listagem de diretórios. O servidor
  embutido do PHP utilizado nos testes não aplica essas regras.

Os serviços, a configuração de teste e os dados temporários foram removidos ao
final. Nenhum teste de módulos futuros foi executado ou declarado como pronto.
O diretório original não era um repositório Git; não houve commit, remoto ou push.
