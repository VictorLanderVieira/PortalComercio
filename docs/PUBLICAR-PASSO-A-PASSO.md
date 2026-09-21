# Publicar o portal do zero — produção e homologação

## 1. Domínio registrado

O titular informou o registro de **guiasarzedo.com.br** no Registro.br.

- Produção: https://guiasarzedo.com.br
- Homologação: https://teste.guiasarzedo.com.br
- Administração: https://guiasarzedo.com.br/#!/admin
- Retorno do Google em produção: https://guiasarzedo.com.br/api/auth/google/callback
- Retorno do Google em homologação: https://teste.guiasarzedo.com.br/api/auth/google/callback

O registro do domínio não publica o portal: ainda é necessário configurar hospedagem, DNS e HTTPS. O subdomínio de teste será criado na hospedagem e no DNS, sem registrar outro domínio. O endereço local de desenvolvimento continua http://localhost:8080.

## 2. Hospedagem escolhida

O titular escolheu **Hostinger Premium por 12 meses** para validar a adesão das empresas. O Cloud Startup abaixo fica como referência de upgrade. Produção e teste devem usar bases e diretórios separados também no Premium. Como o Premium anuncia backups semanais, planejar backup diário do banco e cópias externas protegidas.

### Referência para crescimento

**Hostinger Cloud Startup**: https://www.hostinger.com/br/hospedagem-cloud . Escolha hospedagem PHP personalizada, não o construtor de sites. Se disponível para o plano, escolha servidor no Brasil.

Na consulta de 20/09/2026: 100 GB NVMe, backups diários; oferta de R$ 39,99/mês na contratação de 48 meses (R$ 1.919,52 antes de eventuais impostos), renovação anunciada de R$ 129,99/mês. Confira valores, período, recursos e total no carrinho. Os preços são uma referência, não cobrança mensal garantida pelo valor promocional.

Os 5.450 negócios informados representam o público potencial, não acessos simultâneos. Cloud Startup é a recomendação inicial com margem, sem promessa de capacidade sem teste de carga. Exemplo de armazenamento: 5.450 negócios × 10 fotos × 300 KB ≈ 16,35 GB; não inclui outras imagens, autônomos, banco nem backups. O tamanho real varia. Monitorar disco, CPU, tempo de resposta, banco e concorrência; ampliar antes de saturar. Free usa arquivos compartilhados, sem upload. Novos uploads são reduzidos para até 1.600 px no maior lado; arquivos antigos não são modificados.

Antes de contratar confirme com o suporte: PHP 8.3+, MySQL 8 compatível, SSH/Bash, tar/flock, cron, HTTPS e possibilidade de servir apenas portal/current/public (por link simbólico em public_html). Esta estrutura evita publicar arquivos privados.

### Compra, em ordem

1. Domínio já registrado: guiasarzedo.com.br. Na contratação da hospedagem, selecione a opção de usar um domínio existente.
2. Na Hostinger, abra Hospedagem Cloud e selecione **Premium por 12 meses**, conforme escolha do titular; o preço de R$ 39,99 divulgado corresponde a 48 meses. Confirme o total e a renovação no carrinho. O valor de 12 meses depende da oferta apresentada na sua conta.
3. Revise itens adicionais antes de pagar. O domínio guiasarzedo.com.br já está no Registro.br; mantenha o registro lá e associe-o à hospedagem usando DNS.
4. Crie ou use sua conta Hostinger, conclua o pagamento e ative autenticação em dois fatores.
5. No assistente, escolha criar um site → **PHP/HTML em branco**, usando seu domínio. Nosso portal já tem código próprio.
6. Selecione servidor no Brasil se oferecido. Depois siga os itens abaixo para DNS, PHP, bancos e publicação.

Referências oficiais: [planos Cloud](https://www.hostinger.com/br/hospedagem-cloud), [adicionar site](https://support.hostinger.com/pt/articles/1583214-como-adicionar-um-site), [recursos de SSH e links simbólicos](https://www.hostinger.com/support/which-file-transfer-and-server-access-options-are-supported-at-hostinger/). A Hostinger informa que links simbólicos podem exigir habilitação; confirme especificamente a ligação de public_html para a pasta public, sem liberar indiscriminadamente funções PHP.

## 3. Dois sites e duas bases independentes

No painel da hospedagem:
1. Adicione o domínio principal como site PHP/HTML vazio.
2. Crie o subdomínio teste.guiasarzedo.com.br como site/pasta independente.
3. No Registro.br, configure DNS exatamente conforme os registros ou servidores DNS apresentados pela hospedagem. Não invente IP ou nameservers.
4. Emita/ative SSL para ambos e habilite redirecionamento HTTPS no painel.
5. Selecione PHP 8.3+ e habilite pdo_mysql, gd, curl, mbstring, fileinfo, openssl. Configure upload_max_filesize=8M, post_max_size=10M, memory_limit=256M. Confirme também a versão usada no SSH com php -v.
6. Crie dois bancos: portal_prod e portal_teste (o painel poderá adicionar prefixos ao nome).
7. Crie usuários e senhas DIFERENTES. Cada usuário só deve acessar seu respectivo banco.

Produção: domínio principal + banco prod + uploads e integrações próprios.
Homologação: subdomínio teste + banco teste + uploads e integrações próprios.

Em homologação, mantenha APP_ENV=production para cookies HTTPS seguros e DEPLOY_ENV=staging. O código bloqueia recebimentos reais e envio de notificações nesse ambiente, mesmo que uma configuração administrativa tente habilitá-los. Para testar pagamentos completos, os testes automatizados usam bancos descartáveis e dados fictícios. Não copie dados pessoais de produção para testes sem necessidade e tratamento adequado. Proteja a homologação com senha HTTP: crie shared/.htpasswd com htpasswd -c /CAMINHO/portal/shared/.htpasswd USUARIO_TESTE (a ferramenta solicita a senha) ou peça ao suporte para gerar esse arquivo fora da raiz pública. O deploy reaplica a proteção em cada versão. Mantenha o teste fora da divulgação pública.

A separação de bancos na mesma hospedagem é lógica; não equivale a isolamento de servidores. Se o risco ou volume justificar, separe também as contas de hospedagem.

## 4. Colocar o código no seu GitHub

Você já possui conta: crie um repositório **privado**, por exemplo portal-comercio-local, sem README automático se usar a pasta existente. Use o GitHub Desktop para adicionar esta pasta como repositório local e publicar no seu GitHub.

Antes de enviar, confira os arquivos exibidos: não devem constar storage, uploads reais, .env, senhas, bancos SQLite, certificados privados ou pacotes dist. O .gitignore já exclui esses itens. Ative 2FA/passkey no GitHub. Não desative a privacidade do repositório para facilitar publicação.

Não use npm run setup no servidor: esse comando é de desenvolvimento e inclui dados fictícios. O pacote de publicação usa lista explícita e não inclui seed, bancos locais nem credenciais.

## 5. Preparar os diretórios no servidor (uma vez por ambiente)

Ative SSH no painel. Copie host, porta e usuário exibidos lá. Acesse pelo terminal com ssh -p PORTA USUARIO@HOST, conferindo a impressão digital do servidor com o provedor.

Defina uma pasta PRIVADA por ambiente, fora de public_html. Exemplos ilustrativos (substitua pelos caminhos reais do seu painel):
- Produção: /home/USUARIO/domains/guiasarzedo.com.br/portal
- Homologação: /home/USUARIO/domains/teste.guiasarzedo.com.br/portal

Exemplo de criação, já conectado ao SSH e após substituir o caminho pelo informado no seu painel:

~~~bash
PORTAL_BASE=/home/USUARIO/domains/guiasarzedo.com.br/portal
mkdir -p "$PORTAL_BASE/shared/storage" "$PORTAL_BASE/shared/uploads" "$PORTAL_BASE/incoming" "$PORTAL_BASE/releases" "$PORTAL_BASE/backups"
~~~

Para o teste, repita com o caminho do subdomínio. Não use o mesmo PORTAL_BASE para ambos.

Dentro de cada pasta portal, crie shared/storage, shared/uploads, incoming, releases e backups. Copie .env.production.example ou .env.staging.example como shared/.env, preenchendo o domínio e as credenciais do banco correspondente. Permissão do arquivo: 600. Não copie storage/integrations.json local: ele pode apontar ao Google localhost ou usar configurações de desenvolvimento.

A pasta pública do site deve apontar para portal/current/public. Em uma instalação NOVA, preserve primeiro a pasta pública padrão com outro nome (não apague conteúdo). Crie o link public_html apontando para portal/current/public. Esse link ficará sem destino até a primeira publicação. Se o painel restringir links simbólicos, peça ao suporte para configurar a raiz pública; não exponha o repositório inteiro como alternativa.

O deploy cria versões em releases, mantém .env/storage/uploads em shared e troca current somente depois de verificar e migrar. Os caminhos da aplicação usam a raiz física da versão; não mova server ou .env para dentro de public_html.

## 6. Configurar GitHub Actions

No repositório: Settings → Environments. Crie **staging** e **production**.

Em CADA ambiente, configure os secrets abaixo com os dados do destino correto:

| Secret | Conteúdo |
|---|---|
| SSH_HOST | Host SSH informado no painel |
| SSH_PORT | Porta SSH do painel |
| SSH_USER | Usuário SSH |
| SSH_PRIVATE_KEY | Chave privada exclusiva de publicação; a pública deve estar autorizada na hospedagem |
| SSH_KNOWN_HOSTS | Registro known_hosts do servidor após verificar a impressão digital com a hospedagem |
| DEPLOY_PATH | Caminho absoluto da pasta privada portal daquele ambiente |

Adicione também a variável de ambiente GitHub **PORTAL_URL**, com https://guiasarzedo.com.br ou https://teste.guiasarzedo.com.br, sem barra final.

Crie uma chave exclusiva para deploy no PowerShell:

~~~powershell
ssh-keygen -t ed25519 -C "publicacao-portal" -f "$env:USERPROFILE\.ssh\portal_deploy"
~~~

Use uma chave exclusiva da automação e guarde-a com acesso restrito. O workflow usa essa chave sem passphrase interativa. Cadastre o conteúdo do arquivo portal_deploy.pub nas chaves SSH autorizadas da hospedagem. O conteúdo do arquivo portal_deploy é privado: copie-o diretamente para SSH_PRIVATE_KEY no GitHub, sem enviar ao chat. Use chaves distintas por ambiente quando a hospedagem permitir restringir o destino. A chave pública e a privada têm funções diferentes.

Para SSH_KNOWN_HOSTS, obtenha a chave do host SSH e confira a impressão digital com a Hostinger antes de salvá-la. Não confie em uma saída de ssh-keyscan sem essa conferência.

Crie chave exclusiva para deploy com ssh-keygen, não reutilize a chave pessoal. Guarde a privada somente no secret correspondente. Nunca coloque chave privada no repositório nem desative StrictHostKeyChecking. Proteja a branch principal e habilite aprovação do ambiente production se seu plano do GitHub permitir. Não compartilhe seu acesso GitHub ou hospedagem. Quem controla essas contas pode modificar o site e o Pix.

## 7. Primeira publicação em teste

1. Envie o código para a branch principal do repositório.
2. Abra Actions → Publicar portal → Run workflow.
3. Escolha target=staging e mode=initialize.
4. Aguarde testes PHP/API/interface, validação MySQL e restauração de backup em banco isolado. Se qualquer etapa falhar, o envio não ocorre.
5. O deploy faz backup do banco de destino (mesmo vazio), aplica migrações sem seed, verifica requisitos e troca a versão.
6. O processo verifica /api/catalog por HTTPS. Para homologação com senha HTTP, cadastre HTTP_BASIC_USER e HTTP_BASIC_PASSWORD nos secrets do ambiente staging com o usuário e senha do shared/.htpasswd. A verificação usa essas credenciais sem remover a proteção. O deploy também configura noindex no teste.

No SSH, crie o administrador sem colocar senha no histórico (Bash, dentro da pasta portal):

    read -r -p 'E-mail administrador: ' ADMIN_EMAIL
    read -r -s -p 'Senha (mínimo 12 caracteres): ' ADMIN_PASSWORD
    echo
    export ADMIN_EMAIL ADMIN_PASSWORD
    php current/scripts/create-admin.php
    unset ADMIN_EMAIL ADMIN_PASSWORD

Use administradores distintos por ambiente. Cadastre negócios fictícios manualmente no teste. Verifique cadastro, Free, planos, imagens, busca, avaliações, expiração e acesso administrativo. O fluxo Pix real é homologado em produção com uma transação controlada após configurar seu recebimento.

## 8. Produção

Repita a publicação com target=production e mode=initialize. Crie o administrador. No painel do portal:
- Configure os dados do responsável, privacidade e contato.
- Cadastre sua chave Pix e confira nome do recebedor/cidade. Informe sua senha de administrador para autorizar a mudança.
- Cadastre credenciais Google próprias de produção; base HTTPS e callback https://guiasarzedo.com.br/api/auth/google/callback. Credenciais e callback de teste devem ser separados.
- Mantenha NOTIFICATION_MODE=manual. Os avisos desta entrega são enviados pelo administrador através dos links do WhatsApp.
- Confira um pagamento real controlado no aplicativo bancário, incluindo recebedor e valor, e registre o recebimento no administrativo. Comprovante sozinho não confirma o crédito.

O pacote NÃO leva cadastros ou fotos do computador. Se você quiser preservar cadastros reais locais, faça uma migração de dados separada e revisada antes de divulgar o site; não importe o banco de demonstração inteiro como produção.

## 9. Preparação de lembretes e backups

Configure no painel um cron a cada cinco minutos, separado por ambiente, usando o caminho e binário PHP corretos:

    cd /CAMINHO/portal/current && /CAMINHO/php scripts/worker.php >> /CAMINHO/portal/shared/storage/worker.log 2>&1

O worker prepara lembretes pendentes; NOTIFICATION_MODE=manual impede o disparo por provedor. A expiração de anúncios e publicidades é verificada na consulta, mesmo sem cron. Em staging, envios externos permanecem bloqueados. Defina rotação/retenção de worker.log no provedor.

O deploy salva backup comprimido do banco e das configurações antes da migração. Uploads são compartilhados e não são substituídos pelo deploy: mantenha backups diários da hospedagem que incluam MySQL e uploads, além de cópia externa periódica protegida. Backups contêm dados pessoais e segredos; mantenha acesso restrito. A restauração SQL deve ser para um banco vazio, seguida de verificação. Nunca restaure automaticamente por cima do banco em operação.

As versões e backups de deploy não são apagados automaticamente. Monitore espaço e defina retenção depois de verificar as cópias externas; guardar tudo indefinidamente também consome disco.

## 10. Atualizações futuras

1. Desenvolva e envie a alteração ao GitHub.
2. Execute Publicar portal com target=staging e mode=update.
3. Valide o resultado no subdomínio de teste.
4. Publique o MESMO commit em production com mode=update. Não avance a branch entre as duas publicações; para maior controle utilize uma branch de release protegida.

O processo usa bloqueio para evitar duas publicações simultâneas por ambiente. Durante o backup/migração, a API retorna aviso de manutenção; não promete atualização sem interrupção. Requisições e worker em andamento são aguardados.

Uma falha antes da troca de current mantém a versão de código anterior. Migrações MySQL podem já ter aplicado mudanças: rollback do código NÃO desfaz banco. Se o teste HTTPS falhar depois da troca, o workflow sinaliza falha, mas não tenta reverter banco nem versão automaticamente. Analise antes de restaurar. O caminho da versão anterior é registrado em backups.

Não habilite a publicação Git nativa da hospedagem junto com este workflow: dois mecanismos publicando no mesmo destino podem conflitar.

## 11. Segurança do recebimento Pix

A chave só pode ser configurada por administrador autenticado, com CSRF e confirmação da senha atual. Há limite de tentativas. A configuração fica fora da pasta pública; chaves e senhas não entram no pacote nem em respostas públicas de configuração. O payload Pix é armazenado com a cobrança; mudanças posteriores de chave não reescrevem cobranças antigas. QR e copia-e-cola usam o mesmo payload.

Os cabeçalhos de segurança restringem scripts à própria origem e bloqueiam incorporação em frames. Isso não garante invulnerabilidade. Invasão do servidor, do GitHub ou da conta administrativa ainda pode comprometer o recebimento. Use 2FA nas contas de infraestrutura, senhas exclusivas, acesso mínimo, atualizações, backups e conferência do recebedor no banco. Não divulgue credenciais pelo chat.

## Estado desta preparação

Domínio guiasarzedo.com.br registrado no Registro.br, conforme informado pelo titular. Pacote e scripts preparados localmente. A contratação da hospedagem, a criação do repositório remoto, a configuração do DNS e a publicação externa ainda não foram confirmadas. Os testes MySQL e de restauração estão no workflow e precisam passar no GitHub antes da publicação. O funcionamento SSH, permissões, SSL, cron e links simbólicos precisa ser verificado na conta contratada. O portal usa AngularJS legado: planeje atualização do framework antes de ampliar a exposição e mantenha revisão de segurança; esta preparação não equivale a uma auditoria de invasão.

## Avisos e publicidade nesta entrega
`NOTIFICATION_MODE=manual` bloqueia envios automáticos, mesmo com credenciais antigas. O administrador abre o WhatsApp e registra o atendimento. A migração 10 adiciona publicidades avulsas; nunca execute seed ou demo em produção. Veja [PUBLICIDADE.md](PUBLICIDADE.md).
