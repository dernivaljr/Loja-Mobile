# Administração — etapa 02

`index.php` é a área administrativa inicial, protegida por `exigir_admin()`.
Visitantes são redirecionados ao login e clientes recebem HTTP 403. O painel
mostra a saudação, a previsão de gerenciamento de produtos na etapa 03, retorno
à loja e logout por POST com CSRF na navegação. Não há CRUD implementado.

O procedimento de promoção manual de um cliente está no README principal.
