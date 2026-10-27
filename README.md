# Loja Mobile

Loja virtual de celulares com catálogo, contas de clientes e administradores,
carrinho e registro de pedidos com controle de estoque.

## Funcionalidades

- Catálogo responsivo e detalhes com imagens, descrição, preços e disponibilidade.
- Cadastro, login e logout com perfis Cliente e Administrador.
- Carrinho com adição, alteração de quantidades, remoção e cálculo do total.
- Finalização de pedido para clientes, com registro no MySQL e baixa de estoque.
- Proteção contra pedidos duplicados e compras acima do estoque disponível.
- Painel administrativo para cadastrar, editar e excluir produtos, atualizar
  estoque, substituir imagens e consultar pedidos realizados.
- Nenhum pagamento online é realizado. Não há integração de entrega ou envio
  automático de e-mails.

## Tecnologias

- PHP 8.x procedural, PDO e MySQL/MariaDB.
- HTML5 e CSS3, com layout responsivo.
- Apache e XAMPP para execução local.
- Git para controle de versão. Sem frameworks, ORM ou Composer.

## Requisitos de instalação

- Windows com XAMPP, Apache 2.4 e PHP 8.x.
- MySQL 8.0.16+ ou MariaDB 10.4+, com InnoDB e utf8mb4.
- Extensões PHP PDO, pdo_mysql, mbstring e fileinfo habilitadas.
- Pasta assets/uploads gravável pelo Apache.
- upload_max_filesize de pelo menos 2 MB e post_max_size superior a 2 MB.
- AllowOverride All e módulos mod_alias, mod_mime e mod_authz_core para aplicar
  os arquivos .htaccess. As configurações usuais do XAMPP atendem.

## Configuração do XAMPP e MySQL

1. Coloque o projeto em **C:\xampp\htdocs\Loja-Mobile**. Também é possível usar
   um vínculo de diretório para um checkout local existente.
2. Inicie **Apache** e **MySQL** pelo painel do XAMPP, preservando serviços e
   bancos utilizados por outras aplicações.
3. Abra **http://localhost/phpmyadmin** e importe **database/schema.sql** em UTF-8.
   O script cria loja_mobile e as tabelas usuarios, produtos, pedidos e pedido_itens.
4. Importe **database/seed.sql** para carregar o catálogo inicial com imagens
   locais. A importação é opcional e não cria usuários ou senhas. Os códigos
   dos produtos impedem duplicações em novas importações.
5. Copie **config/banco.exemplo.php** para **config/banco.php** e preencha host,
   porta, banco, usuário e senha do MySQL local. Esse arquivo não é versionado.

Se o banco loja_mobile já estiver em uso, escolha outro nome nos dois scripts
SQL e na configuração antes de importar. Nunca substitua um banco existente.
Prefira um usuário MySQL específico com permissões apenas para o banco da loja.

### Atualização de um banco das etapas 01/02

Preserve uma cópia de segurança e importe **database/migracao_03.sql uma única
vez**, ajustando o USE ao nome do seu banco. Essa migração adiciona a chave de
confirmação sem apagar pedidos anteriores. Instalações novas com schema.sql
atual não devem executar a migração novamente.

## Inicializar a loja

Com Apache e MySQL ativos e a conexão configurada, acesse:

**http://localhost/Loja-Mobile/**

Os arquivos PHP precisam ser servidos pelo Apache; não devem ser abertos
diretamente pelo explorador de arquivos. Para acessar pelo próprio computador,
localhost pode usar 127.0.0.1 ou ::1.

## Cadastro e login

Em **Cadastrar**, informe nome, e-mail, tipo de conta, senha e confirmação.
O nome aceita até 120 caracteres e o e-mail até 254. A senha exige pelo menos
8 caracteres e aceita até 72 bytes, para evitar truncamento do bcrypt.

- **Cliente:** pode selecionar produtos e finalizar pedidos.
- **Administrador:** pode gerenciar produtos, imagens, estoque e consultar pedidos.
  O cadastro direto desse perfil só é permitido quando a conexão chega de
  **127.0.0.1 ou ::1**. A regra é verificada no servidor pelo endereço da conexão,
  sem confiar em Host, X-Forwarded-For ou outros cabeçalhos de proxy.

Em acessos de outros endereços, Administrador fica indisponível no formulário
e todo cadastro é gravado como cliente, mesmo que o perfil admin seja enviado
manualmente. Não há senha administrativa fixa ou credenciais incluídas no projeto.

Após cadastrar, faça login com e-mail e senha. Clientes são direcionados à loja;
administradores, ao painel. O carrinho do visitante é preservado no login.
**Sair** encerra a sessão e descarta o carrinho.

## Carrinho e finalização do pedido

1. Abra um celular, selecione uma quantidade disponível e clique em **Adicionar
   ao carrinho**. Essa ação não reserva estoque.
2. No carrinho, use **Atualizar** para alterar quantidades ou **Remover**.
3. Entre como cliente e clique em **Revisar compra**.
4. Confira produtos, quantidades e total e clique em **Confirmar pedido**.
5. A confirmação mostra o número, status, itens e total do pedido registrado.

Os preços são sempre consultados no banco. Se algum preço mudar durante a
revisão, será necessário confirmar o resumo atualizado. Se o estoque acabar,
o pedido não será concluído e o carrinho será preservado. O estoque é reduzido
e o carrinho limpo somente após a confirmação da transação.

Reenviar uma confirmação não gera outro pedido nem outra baixa de estoque.
Administradores não finalizam compras como clientes. Nenhum pagamento online
é realizado durante o checkout, e a aplicação não promete entrega ou e-mail automático.

## Painel administrativo

Entre com uma conta Administrador e acesse **Painel Administrativo**.

- **Cadastrar produto:** informe código único, marca, modelo, descrição, estoque
  inteiro não negativo, preço positivo e imagem JPEG, PNG ou WebP de até 2 MB.
- **Editar:** altere campos, preço e estoque. Envie uma nova imagem para substituir
  a atual; deixar o upload vazio preserva a imagem existente.
- **Excluir:** abra a opção e confirme. Produtos associados a pedidos não podem
  ser removidos; ajustar o estoque para zero impede novas compras.
- **Pedidos realizados:** consulte número, cliente, total, data e status.

Preços aceitam vírgula ou ponto para os centavos, sem separador de milhar.
Uploads passam por validação de extensão, MIME real e estrutura da imagem;
recebem nomes aleatórios e não são versionados. A pasta bloqueia execução de scripts.

## Estrutura resumida

```text
Loja-Mobile/
├── index.php, produto.php, carrinho.php, checkout.php, pedido.php
├── cadastro.php, login.php, logout.php
├── admin/                    # painel e gestão de produtos
├── assets/css/               # estilos compartilhados
├── assets/img/               # imagens do catálogo inicial
├── assets/uploads/           # imagens enviadas e proteção Apache
├── config/                   # exemplo e conexão local não versionada
├── database/                 # schema, seed e migração
├── includes/                 # conexão, autenticação, regras e layout
├── docs/                     # especificação e registros técnicos
├── tests/                    # scripts de validação preservados
├── .gitignore, .htaccess
└── README.md
```
