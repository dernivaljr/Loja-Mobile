# Loja Mobile

Projeto acadêmico de **Desenvolvimento Web II**, do curso de Desenvolvimento de
Software Multiplataforma (DSM) da Fatec. O objetivo é desenvolver um e-commerce
de celulares de forma incremental, com código simples e legível.

**Etapa atual: 02 — Autenticação e controle de acesso.** Inclui a fundação,
cadastro de clientes, login, logout, sessões e painel administrativo inicial
protegido. Carrinho, checkout, pedidos, CRUD e upload ainda não estão
implementados. Não há pagamento real.

## Tecnologias

- PHP 8.x procedural, com as extensões PDO, `pdo_mysql` e `mbstring` habilitadas
  (disponíveis no XAMPP).
- MySQL 8.0.16+ ou MariaDB 10.4+ (disponível no XAMPP). As versões mínimas
  indicadas garantem a aplicação das constraints `CHECK` utilizadas.
- HTML5, CSS3, imagens SVG locais e Git. Sem frameworks, Composer ou pacotes.
- XAMPP, com Apache e MySQL/MariaDB, para executar localmente.

## Estrutura

```text
Loja-Mobile/
├── .gitignore
├── .htaccess
├── index.php
├── cadastro.php
├── login.php
├── logout.php
├── README.md
├── admin/
│   ├── index.php
│   └── README.md
├── assets/
│   ├── css/
│   │   └── style.css
│   └── img/
│       ├── celular-padrao.svg
│       ├── favicon.svg
│       └── hero-celulares.svg
├── config/
│   ├── banco.exemplo.php
│   └── banco.php            # criado localmente; ignorado pelo Git
├── database/
│   ├── schema.sql
│   └── seed.sql
├── docs/
│   ├── ESPECIFICACAO.md
│   └── VALIDACAO.md
└── includes/
    ├── acesso-negado.php
    ├── autenticacao.php
    ├── cabecalho.php
    ├── conexao.php
    ├── funcoes.php
    └── rodape.php
```

`admin/index.php` contém somente o painel inicial protegido; não há CRUD nesta
etapa. Não é necessário instalar dependências. O schema da etapa 01 já atende
à autenticação e não precisou de migração.

## Instalação local no XAMPP

1. Copie a pasta do projeto para `C:\xampp\htdocs\Loja-Mobile`.
2. No painel do XAMPP, inicie **Apache** e **MySQL**.
3. Abra `http://localhost/phpmyadmin`. Na aba **Importar**, importe
   `database/schema.sql`. Ele cria e seleciona o banco `loja_mobile` e cria as
   quatro tabelas. Se esse nome já estiver em uso por outro projeto, escolha
   outro nome nos dois scripts SQL e na configuração antes de importar.
4. Importe `database/seed.sql` para adicionar quatro celulares fictícios.
   O seed é opcional: sem ele, o site mostra a mensagem de catálogo vazio.
5. Copie `config/banco.exemplo.php` para `config/banco.php`. Ajuste `host`,
   `porta`, `banco`, `usuario` e `senha` conforme seu MySQL local. O exemplo usa
   a configuração habitual do XAMPP: `127.0.0.1`, porta `3306`, usuário `root`
   e senha vazia. Use isso somente se corresponder ao seu ambiente local.
6. Acesse **`http://localhost/Loja-Mobile/`**.

Não abra `index.php` diretamente como arquivo: o PHP precisa de um servidor.
Não versione `config/banco.php`. Não foi criado usuário administrador, nem
qualquer conta com senha pública. Em um ambiente compartilhado, utilize um
usuário MySQL específico com as permissões necessárias ao projeto.

O schema pode ser importado novamente sem apagar tabelas, mas **não atualiza
estruturas preexistentes**. O seed preserva produtos que já tenham os códigos
`DEMO-001` a `DEMO-004`. Uma futura mudança de estrutura deverá ter migração
própria; não apague um banco com dados para aplicar alterações.

## Verificação local

Os testes realizados nesta entrega e seus limites estão em `docs/VALIDACAO.md`.

### Cadastro, login e logout

1. Na navegação, clique em **Cadastrar**. Informe nome, e-mail, senha com pelo
   menos 8 caracteres e a mesma senha na confirmação. O servidor limita o nome
   a 120 caracteres e o e-mail a 254; a senha aceita no máximo 72 bytes para
   impedir truncamento pelo bcrypt. Caracteres acentuados e emojis ocupam mais
   de um byte. As senhas não são reapresentadas após erros de validação.
2. Após o cadastro, a página redireciona para **Entrar** e mostra uma confirmação.
   Todo cadastro público cria exclusivamente um `cliente`, mesmo se alguém
   tentar enviar outro perfil no formulário.
3. Entre com e-mail e senha. Um cliente retorna ao catálogo; um administrador
   vai ao painel. Nome e opção **Sair** aparecem na navegação autenticada.
4. Clique em **Sair**. O formulário envia POST com token CSRF, os dados da sessão
   são apagados, o cookie é invalidado e a navegação retorna à página inicial.
   Acessar `logout.php` por GET devolve HTTP 405 sem encerrar a sessão.

Não use senhas de contas reais nos testes acadêmicos. Não há recuperação de
senha nem confirmação de e-mail nesta etapa.

### Promover um cliente a administrador no phpMyAdmin

Cadastre uma conta pelo site com uma senha escolhida por você. No phpMyAdmin,
selecione `loja_mobile`, abra **SQL** e localize o ID da conta:

```sql
SELECT id, nome, email, perfil FROM usuarios ORDER BY id;
```

Depois de conferir o ID e o e-mail, substitua os dois exemplos abaixo pelos
valores da conta que deseja promover. O exemplo não cadastra nenhum usuário:

```sql
UPDATE usuarios
SET perfil = 'admin'
WHERE id = 123 AND email = 'seu-email@exemplo.com' AND perfil = 'cliente';
```

Confira se exatamente uma linha foi alterada. Saia da conta e faça login
novamente: o perfil é carregado na sessão durante o login. Acesse
`http://localhost/Loja-Mobile/admin/index.php`. Para voltar ao perfil cliente,
use o mesmo filtro com `SET perfil = 'cliente'` e `AND perfil = 'admin'`, e faça
um novo login. Não há senha administrativa fixa ou cadastro público de admin.

### Proteção das rotas

- Em uma janela anônima, abra `admin/index.php`: deve redirecionar para o login.
- Entre como cliente e digite a mesma URL: deve receber HTTP 403 e uma mensagem
  de acesso restrito. Ocultar o link não é o mecanismo de proteção; a função
  `exigir_admin()` é executada antes de qualquer conteúdo do painel.
- Entre como administrador: deve ver saudação, aviso sobre a etapa 03, retorno
  à loja e logout na navegação. CSS e links devem funcionar dentro de `admin/`.
- Depois do logout, o painel deve exigir login novamente. Um POST sem token
  CSRF válido em cadastro, login ou logout deve receber HTTP 403.

### Catálogo e ambiente

- Confira os quatro produtos, imagens e preços em reais após importar o seed.
- Teste uma janela de desktop e uma tela de celular; use os links de navegação
  e navegue com Tab. Todos os links apontam para áreas existentes da página.
- Em um banco de testes sem seed, confira a mensagem de catálogo vazio.
- Com MySQL parado ou configuração ausente/incorreta, a página continua
  renderizando e exibe uma mensagem amigável com HTTP 503. Detalhes internos
  e credenciais não são exibidos. Um aviso genérico é registrado no log do PHP.
- Teste o projeto servido pelo Apache: `.htaccess` bloqueia listagem de pastas
  e acesso HTTP a SQL, Markdown, logs e arquivos ocultos. Essas regras dependem
  de `AllowOverride` e do módulo de autorização do Apache; o servidor embutido
  do PHP não interpreta `.htaccess`. `includes/` e `config/` são código PHP de
  uso interno, sem saída própria.

Para verificar a sintaxe, no PowerShell, a partir da pasta do projeto:

```powershell
Get-ChildItem -Recurse -Filter *.php |
    Where-Object { $_.FullName -notmatch '[\\/]\.validation[\\/]' } |
    ForEach-Object { & 'C:\xampp\php\php.exe' -l $_.FullName }
```

Para uma visualização rápida alternativa ao Apache, com o MySQL já iniciado e
`config/banco.php` configurado:

```powershell
& 'C:\xampp\php\php.exe' -S 127.0.0.1:8080 -t .
```

Abra `http://127.0.0.1:8080`. Esse servidor é somente para desenvolvimento.

## Decisões da fundação

- SQL de consulta fica em uma função, separado do HTML; PDO usa exceções,
  consultas preparadas nativas e conexão `utf8mb4`.
- Saídas de texto e atributos passam por `htmlspecialchars()`. Os caminhos
  de imagens ficam restritos a arquivos locais em `assets/`, com imagem padrão
  quando o caminho estiver ausente, inválido ou não existir.
- Preços são armazenados como `DECIMAL(10,2)` e formatados em reais somente
  na apresentação. Checkout e cálculos de pedidos ficam para etapas futuras.
- Chaves estrangeiras impedem a remoção de registros vinculados a pedidos,
  preservando seu histórico. Quantidades e valores possuem constraints.
- SVGs foram criados para a demonstração; não há fontes, imagens ou APIs externas.
- Sessões são iniciadas uma única vez, antes do HTML, com cookies `HttpOnly`,
  `SameSite=Lax`, caminho limitado ao projeto e `Secure` quando servido por HTTPS.
  O ID muda após login; somente ID, nome, perfil e token CSRF ficam na sessão.
- Senhas usam `password_hash()`/`password_verify()`. Cadastro e login usam PDO
  preparado; o perfil do cadastro é fixado no servidor. Tokens CSRF protegem
  cadastro, login e logout. Redirecionamentos usam HTTP 303; páginas com sessão
  recebem `Cache-Control: no-store`.
- Uploads validados e transações de checkout permanecem para etapas futuras.
  Consulte `docs/ESPECIFICACAO.md`.

## Git local

Na etapa 02, o Git foi inicializado e a fundação preservada em um primeiro
commit (`9b7dfcc`, `Registra etapa 01: fundação da Loja Mobile`). A autenticação
possui um commit separado, `Implementa etapa 02: autenticação e controle de acesso`.
Confira com `git log --oneline`. Não foi configurado remoto nem executado push.
`config/banco.php` e os arquivos temporários de validação são ignorados pelo Git.
