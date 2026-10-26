# Loja Mobile

Projeto acadêmico de **Desenvolvimento Web II**, do curso de Desenvolvimento de
Software Multiplataforma (DSM) da Fatec. O objetivo é desenvolver um e-commerce
de celulares de forma incremental, com código simples e legível.

**Etapa atual: 01 — Fundação.** Inclui modelagem do banco, conexão PDO, catálogo
consultado no MySQL e landing page responsiva. Login, carrinho, checkout, pedidos
e administração ainda não estão implementados. Não há pagamento real.

## Tecnologias

- PHP 8.x procedural, com as extensões PDO e `pdo_mysql` habilitadas.
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
├── README.md
├── admin/
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
    ├── cabecalho.php
    ├── conexao.php
    ├── funcoes.php
    └── rodape.php
```

`admin/` contém somente uma nota sobre a etapa futura. Arquivos PHP são criados
apenas quando utilizados. Não é necessário instalar dependências.

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
- A configuração de sessão, os hashes de senha, o controle de acesso, os
  uploads validados e as transações serão aplicados quando seus módulos
  forem implementados. Consulte `docs/ESPECIFICACAO.md`.

O projeto não foi inicializado como repositório Git durante esta entrega porque
o diretório original não possuía `.git`. Não foi criado remoto nem feito push.
Para iniciar o versionamento quando desejar, revise os arquivos e execute
`git init`, `git add .` e `git commit -m "Implementa etapa 01: fundação da Loja Mobile"`.
