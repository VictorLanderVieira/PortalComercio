Para a primeira entrega com Pix pessoal e avisos manuais, siga [PUBLICAR-PASSO-A-PASSO.md](PUBLICAR-PASSO-A-PASSO.md). Integrações automáticas descritas abaixo são opcionais futuras e não estão habilitadas nesta versão.

# Publicação e operação

## Ambiente local

`npm run setup` prepara assets e SQLite; `npm start` abre o servidor em localhost:8080. O servidor embutido do PHP é exclusivo para desenvolvimento.

Não copie `storage`, `.env`, `node_modules`, testes ou credenciais locais para o diretório público. A raiz web deve ser **somente `public/`**. A configuração do banco e integrações fica em `.env`, um nível acima da raiz pública.

## MySQL 8 + PHP

1. Crie um banco UTF-8 (`utf8mb4`) e um usuário exclusivo com acesso somente ao banco do portal.
2. Instale PHP 8.3 com PDO MySQL, GD, cURL, mbstring, fileinfo, session e OpenSSL.
3. Copie os arquivos e configure `.env` a partir de `.env.example`:

```dotenv
APP_ENV=production
APP_URL=https://SEU-DOMINIO
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sarzedo
DB_USERNAME=sarzedo
DB_PASSWORD=UMA_SENHA_FORTE
PAYMENT_DRIVER=asaas
ASAAS_ENV=sandbox
```

4. Execute `php scripts/migrate.php`. O script converte identidade para MySQL e aplica a migração gerencial. Arquivos de banco: `database/schema.sql` e `database/002-management.sql`. Para SQL MySQL completo exportável, execute `php scripts/export-mysql.php > database/mysql.sql`.
5. Crie administrador por CLI usando `ADMIN_EMAIL` e `ADMIN_PASSWORD` no ambiente, e execute `php scripts/create-admin.php`. Use senha única de pelo menos 12 caracteres. Não reutilize administrador de desenvolvimento.
6. Configure Apache com mod_rewrite/mod_headers e DocumentRoot em `public`, ou o Nginx de `infra/nginx.conf`.
7. Habilite HTTPS antes de usar login, PWA ou receber dados reais. Cookies são Secure quando `APP_ENV=production`.
8. Garanta escrita somente em `storage` e `public/uploads` para o usuário do PHP. Código e `.env` não precisam de escrita pelo processo web.
9. Entre no painel administrativo e preencha responsável, e-mail de privacidade, WhatsApp de suporte e datas da campanha.
10. Homologue integrações em sandbox. Só depois altere Asaas para produção.

No início da captação, o pagamento pode permanecer indisponível (`PAYMENT_DRIVER=disabled`) enquanto a campanha gratuita estiver ativa. O sistema não permite simular pagamento em produção. Configure cobrança real antes de oferecer ativação paga.

## Agendamento automático

Execute o worker a cada cinco minutos. Ele concilia pagamentos, prepara avisos e despacha a fila. Um lock impede sobreposição no mesmo servidor.

```cron
*/5 * * * * cd /var/www/sarzedo && /usr/bin/php scripts/worker.php >> storage/worker.log 2>&1
```

No Windows, configure o Agendador de Tarefas com `php.exe`, argumento `C:\Pessoal\PortalComercio\scripts\worker.php` e diretório inicial da aplicação. Para SQLite local, inclua `-d extension=pdo_sqlite` antes do script.

Teste sem envios: `php scripts/worker.php --dry-run`. A simulação prepara registros de fila, mas não chama Asaas nem envia mensagens. `NOTIFICATIONS_ENABLED=false` também impede envio externo.

A expiração pública funciona pela consulta das datas, mesmo se o agendador falhar. Avisos e conciliação dependem do agendador; acompanhe o histórico no administrativo e os logs.

## Docker opcional

Há uma composição de referência em `compose.yaml` com Nginx, PHP-FPM, MySQL e worker. Crie `.env` e configure `DB_PASSWORD` e `MYSQL_ROOT_PASSWORD`. Os serviços usam `DB_HOST=db`. O container PHP espera o MySQL e executa migrações. O worker roda a cada cinco minutos. Nginx fica em `http://localhost:8080`; para publicação real, coloque um proxy HTTPS à frente.

```shell
docker compose up --build -d
docker compose exec app php scripts/create-admin.php
```

Para criar admin, passe `ADMIN_EMAIL` e `ADMIN_PASSWORD` ao comando pelo ambiente seguro do container. Não grave senhas no histórico compartilhado. Os containers não criam credenciais padrão ou dados ilustrativos.

A composição é fornecida como configuração de infraestrutura, mas não foi executada neste ambiente (Docker/MySQL não estavam disponíveis).

## Operação

- Faça backup diário do MySQL e dos uploads. Criptografe o backup, restrinja acesso e teste restauração.
- Monitore jobs, erros HTTP, falhas de entrega, webhooks e consumo dos provedores.
- Não mantenha logs indefinidamente. Não registre corpo de OAuth, tokens, documentos ou códigos Pix em logs públicos.
- Antes de uma migração, faça backup. MySQL pode efetivar alterações DDL fora de transação.
- `creating` em assinatura indica operação externa potencialmente incerta. Confira a referência `subscription_ID` no Asaas antes de regularizar; não crie outra cobrança automaticamente.
- Admin pode suspender cadastro, mas não desfaz pagamentos. Estorno financeiro deve ser feito no provedor e sincronizado pelo webhook/conciliação.
- Atualizar plano de assinatura exige cancelar a renovação anterior e emitir uma nova; a edição do plano de interesse não altera cobrança vigente.
- Não excluir empresas com pagamentos por SQL improvisado. Siga o procedimento de privacidade e retenção.

## Homologação antes do lançamento

Validar em MySQL real; escolher solução de suporte/migração do AngularJS; configurar controlador e política final; definir retenção; criar credenciais de produção; testar Pix recebido, vencido, duplicado e estornado; testar login Google e vínculo; comprovar envio e recebimento de e-mail e WhatsApp; instalar agendador; testar backup e restauração; verificar instalação da PWA em Android e iPhone com HTTPS.


Indicadores: execute php scripts/upgrade.php para aplicar a migração 005. A coleta de acessos/buscas é habilitada por padrão em produção e desenvolvimento; ANALYTICS_ENABLED=false desliga e ANALYTICS_ENABLED=true habilita explicitamente. A página informa desde quando os totais são medidos.
