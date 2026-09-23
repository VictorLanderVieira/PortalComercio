# Executar o portal no computador

Pré-requisitos: PHP 8.3 ou superior no PATH, Node.js e npm. O PHP local deve ter PDO SQLite, GD, cURL, mbstring, OpenSSL e fileinfo habilitados.

Na pasta existente, com seu banco preservado:

~~~powershell
cd C:\Pessoal\PortalComercio
npm install
npm start
~~~

`npm start` aplica somente as migrações pendentes antes de abrir o servidor; não recria o banco nem insere dados fictícios. Acesse http://localhost:8080 e http://localhost:8080/#!/admin. Use a conta administrativa já cadastrada. Ctrl+C no terminal encerra o servidor. Se a porta estiver ocupada, verifique se já há uma instância aberta antes de iniciar outra.

## Ambiente
O arquivo privado .env deve usar APP_ENV=local, APP_URL=http://localhost:8080 e DB_DRIVER=sqlite. NOTIFICATION_MODE=manual mantém os avisos manuais. Não divulgue nem envie .env ao GitHub.

O banco local padrão é storage/portal.sqlite. Ele não é enviado na publicação. A produção e a homologação usam bancos MySQL e credenciais distintos, conforme [PUBLICAR-PASSO-A-PASSO.md](PUBLICAR-PASSO-A-PASSO.md).

Não execute npm run setup sobre o banco atual: esse comando inclui seed e demonstração. Para uma instalação realmente nova, copie .env.example para .env, configure o ambiente e execute a migração antes de criar o administrador. Não sobrescreva um .env existente.

## Validar alterações
~~~powershell
npm run check:php
npm test
npm run test:ui
npm run release
~~~
Os testes usam bases SQLite isoladas e não enviam mensagens nem recebem pagamentos reais. Os testes de navegador precisam do Google Chrome. O pacote sai em dist/portal-release.tar.gz. O PHP embutido é para desenvolvimento, não para servir a produção.
