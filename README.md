# Loja Mobile

E-commerce acadêmico de celulares da disciplina **Desenvolvimento Web II**,
curso de Desenvolvimento de Software Multiplataforma (DSM), Fatec.

Permite navegar pelo catálogo, criar conta, montar carrinho e confirmar uma
compra simulada com pedido persistido e baixa real de estoque no MySQL.
Administradores gerenciam produtos, imagens e consultam os pedidos.
Sem pagamento real, frete, cupons ou APIs externas.

## Tecnologias e requisitos

- PHP 8.x procedural com PDO, pdo_mysql, mbstring e fileinfo habilitados.
- MySQL 8.0.16+ ou MariaDB 10.4+, InnoDB e utf8mb4.
- Apache 2.4 do XAMPP, HTML5, CSS3 e Git. Sem frameworks, ORM ou Composer.
- Pasta assets/uploads gravável pelo Apache; upload_max_filesize de pelo menos
  2 MB e post_max_size superior a 2 MB. O padrão do XAMPP atende.
- AllowOverride All e módulos Apache mod_alias, mod_mime e mod_authz_core para
  os arquivos .htaccess. Nenhum JavaScript é necessário nesta versão.

## Funcionalidades

- Catálogo público responsivo e detalhes com descrição, imagem, preço e estoque.
- Cadastro de clientes, login, logout e acesso separado para cliente/admin.
- Carrinho em sessão com adição, atualização, remoção, subtotal e total.
- Carrinho do visitante preservado no login; valores sempre consultados no banco.
- Produto sem estoque não permite compra.
- Checkout exclusivo para cliente, revisão de preços e estoque, transação e
  bloqueio de linhas. Reenvios não duplicam pedidos ou baixa de estoque.
- Confirmação de pedido acessível somente ao cliente proprietário.
- Painel com CRUD, estoque, upload/substituição de imagens e listagem simples
  de pedidos com número, cliente, valor, data e status.
- Exclusão de produto vendido bloqueada pelas chaves estrangeiras.
- PDO preparado, senha com hash, escaping HTML, sessões e CSRF.

## Instalação no XAMPP

1. Copie ou clone o projeto para **C:\xampp\htdocs\Loja-Mobile**.
2. Inicie **Apache** e **MySQL** no painel do XAMPP.
3. Abra http://localhost/phpmyadmin e importe **database/schema.sql** em UTF-8.
   Ele cria loja_mobile e quatro tabelas: usuarios, produtos, pedidos, pedido_itens.
4. Importe **database/seed.sql** para adicionar quatro produtos fictícios com
   imagens locais. O seed é opcional, reimportável e não cria contas ou senhas.
5. Copie **config/banco.exemplo.php** para **config/banco.php** e ajuste host,
   porta, banco, usuário e senha. O exemplo usa 127.0.0.1:3306, root e senha vazia,
   somente se isso corresponder ao seu MySQL local. Nunca versione banco.php.
6. Acesse **http://localhost/Loja-Mobile/**. Não abra PHP diretamente como arquivo.

Se o nome do banco já pertencer a outro projeto, altere-o nos SQLs e na
configuração antes de importar. O schema não apaga nem migra tabelas existentes.

### Atualizar bancos das etapas 01/02

Preserve uma cópia de segurança e importe **database/migracao_03.sql uma única
vez**. Ajuste o USE se mudou o nome do banco. A migração adiciona a chave única
de checkout sem remover pedidos anteriores. Instalações novas com o schema
atual não devem executar a migração novamente. Não foi criada outra tabela.

## Cadastro, login e administração

Clique em **Cadastrar** e informe nome, e-mail, senha e confirmação. Nome aceita
até 120 caracteres, e-mail até 254; senha exige no mínimo 8 caracteres e aceita
até 72 bytes, para impedir truncamento pelo bcrypt. Senhas não reaparecem após
erros. O perfil de todo cadastro público é cliente, definido no servidor.

Após cadastrar, entre com e-mail e senha. Clientes vão para a loja; admins, para
o painel. **Sair** envia POST com CSRF e encerra sessão/cookie. O logout também
descarta o carrinho. O login preserva o carrinho do visitante.

Para habilitar um administrador, cadastre a conta e localize seu ID no phpMyAdmin:

```sql
SELECT id, nome, email, perfil FROM usuarios ORDER BY id;
```

Substitua os exemplos pelo ID/e-mail exatos da conta desejada:

```sql
UPDATE usuarios SET perfil = 'admin'
WHERE id = 123 AND email = 'seu-email@exemplo.com' AND perfil = 'cliente';
```

Confira se uma linha foi alterada e faça logout/login para atualizar o perfil.
Para reverter, use SET perfil = 'cliente' e AND perfil = 'admin', com o mesmo
ID/e-mail. Não há senha administrativa fixa nem cadastro público de admin.

## Produtos e imagens

1. Como admin, abra **Painel Administrativo → Cadastrar produto**.
2. Preencha código único, marca, modelo, descrição, estoque inteiro não negativo
   e preço positivo (1299,90 ou 1299.90, sem separador de milhar).
3. Selecione uma imagem **JPEG, PNG ou WebP de até 2 MB** e salve.
4. Em **Editar**, ajuste estoque e demais campos. Nova imagem substitui a
   anterior; campo de upload vazio preserva a imagem atual.
5. Para remover, abra **Excluir** e confirme. Produto pertencente a pedido não
   é excluído. Ajustar seu estoque para zero impede novas compras.

O upload confere extensão, MIME real, estrutura e origem; gera nome aleatório
e salva em assets/uploads. O Apache bloqueia scripts nessa pasta. SVG não é
aceito em uploads; os SVGs iniciais são arquivos confiáveis do projeto.
Uploads locais não entram no Git.

## Compra de teste

1. Abra um celular no catálogo, selecione uma quantidade disponível e clique
   em **Adicionar ao carrinho**. O carrinho não reserva estoque.
2. Use **Atualizar** ou **Remover** para ajustar os itens.
3. Entre como **cliente**. Administradores não finalizam pedidos.
4. Clique em **Revisar compra**, confira o resumo e confirme o pedido simulado.
5. O banco recebe pedido/itens e baixa o estoque; somente depois do commit o
   carrinho é limpo. A confirmação mostra número, status, total e itens.
6. O pedido aparece também na listagem administrativa.

Alteração de preço durante a revisão exige nova confirmação. Estoque esgotado
recusa a compra e preserva o carrinho. Reenviar a mesma confirmação retorna o
mesmo pedido, sem nova baixa. Não há cobrança, entrega ou integração de pagamento.

## Estrutura

```text
Loja-Mobile/
├── index.php, produto.php, carrinho.php, checkout.php, pedido.php
├── cadastro.php, login.php, logout.php
├── admin/                    # index.php, produto.php, excluir-produto.php
├── assets/css/style.css
├── assets/img/               # imagens locais da demonstração
├── assets/uploads/           # uploads ignorados; .htaccess versionado
├── config/banco.exemplo.php  # banco.php local é ignorado
├── database/                 # schema.sql, seed.sql, migracao_03.sql
├── includes/                 # conexão, autenticação, produtos, carrinho e layout
├── tests/                    # integração, concorrência, worker e fixtures
├── docs/                     # ESPECIFICACAO.md e VALIDACAO.md
├── .gitignore, .htaccess
└── README.md
```

## Testes automatizados

Resultados efetivamente executados em **docs/VALIDACAO.md**. Testado com
Apache/PHP/MariaDB do XAMPP isolados, na raiz e em /Loja-Mobile/.

Para reproduzir, use uma instalação **descartável**, cujo banco termine em
**_teste**, com schema/seed importados. A cópia da loja e os scripts devem usar
esse mesmo banco. O roteiro HTTP exige localhost/127.0.0.1 com porta explícita.
Os testes usam contas aleatórias e removem exclusivamente seus próprios registros.
O teste de concorrência exige permissão de criar/remover trigger no banco de teste.

No PowerShell dessa cópia, com a extensão cURL habilitada no PHP CLI:

```powershell
$env:LOJA_TESTE_URL = 'http://127.0.0.1:8080/Loja-Mobile/'
& 'C:\xampp\php\php.exe' tests/integracao.php
& 'C:\xampp\php\php.exe' tests/concorrencia.php
Get-ChildItem -Recurse -Filter *.php |
    Where-Object { $_.FullName -notmatch '[\\/]\.validation[\\/]' } |
    ForEach-Object { & 'C:\xampp\php\php.exe' -l $_.FullName }
```

Ajuste a URL à porta do Apache de testes. Para apresentação normal, use a URL
http://localhost/Loja-Mobile/ configurada na instalação.

## Decisões técnicas e entrega

PHP procedural separa consultas, validações, sessão e apresentação em funções.
PDO usa prepared statements nativos e utf8mb4. Saídas usam htmlspecialchars.
Moeda usa DECIMAL no banco e centavos inteiros nos cálculos. Checkout bloqueia
produtos por ID em ordem fixa com FOR UPDATE, faz commit/rollback e preserva
o preço unitário nos itens. Uma chave única persistida impede duplicações.

Sessão usa modo estrito, cookies HttpOnly/SameSite=Lax e Secure sob HTTPS.
Alterações usam CSRF e POST. Diretórios internos e uploads têm proteção Apache.
Erros do banco não mostram detalhes internos. Sem recuperação de senha ou
confirmação de e-mail, conforme o escopo acadêmico.

Git registra fundação, autenticação, administração, compra e validação final em
commits separados. O remoto de entrega é privado; configuração, dados de teste,
sessões e uploads não são enviados. Consulte git log --oneline e git remote -v.
