# Integrações

## Pix mensal — Asaas

A conta de recebimento deve ser do responsável pelo portal. Configure `ASAAS_API_KEY` e escolha `ASAAS_ENV=sandbox` durante os testes. Quando estiver homologado, use a conta/chave de produção.

Fluxo: proprietário salva cadastro → escolhe plano → informa CPF/CNPJ válido → backend cria cliente no Asaas → cria assinatura mensal com `billingType=PIX` → recupera a primeira cobrança pendente e QR Code → visitante paga → webhook confirma → portal libera o período.

O valor vem do banco, nunca do navegador. O CPF/CNPJ é validado por dígitos verificadores e enviado ao provedor; não é persistido no cadastro local. O ID de cliente e assinatura são guardados para futuras cobranças.

O Pix mensal precisa ser pago pelo cliente. Não é Pix Automático com débito autorizado. O Asaas gera as cobranças futuras da assinatura; o worker envia os avisos. Cancelar renovação no painel cancela a assinatura no provedor e impede novas cobranças, preservando o acesso já pago.

### Webhook

- URL: `https://SEU-DOMINIO/api/webhook`
- Método: POST.
- Segredo: configure um token exclusivo de pelo menos 32 caracteres em `ASAAS_WEBHOOK_TOKEN` e no `authToken` do webhook Asaas.
- A aplicação compara `asaas-access-token` em tempo constante e consulta o pagamento por API antes de confiar no estado.
- Configure os eventos de cobrança, incluindo criação, confirmação, recebimento, vencimento, exclusão e estorno.
- Não use a chave de API como segredo do webhook.
- Retorno 2xx somente após processamento persistido; falhas devem ser reenviadas pelo provedor.

A data final do período pago é calculada a partir da data mais recente entre vencimento e pagamento, mais um mês civil, ajustando fins de mês. Confirmar a mesma cobrança novamente não acrescenta dias. Notificações fora de ordem buscam a situação atual do provedor.

Uma solicitação de criação com resposta incerta fica em `creating`. Antes de reenviar, consulte a conta Asaas pela referência `subscription_ID`. A implementação evita retentativa automática de criação para não cobrar duas vezes. Não há falsa confirmação de pagamento pelo retorno do navegador.

A integração permanece sem homologação externa enquanto não houver credenciais. Taxas, prazo de liquidação e habilitação do Pix dependem do provedor; os indicadores são brutos, não saldo bancário.

Referências: [criação de assinatura](https://docs.asaas.com/reference/criar-nova-assinatura), [assinaturas](https://docs.asaas.com/docs/assinaturas), [webhooks](https://docs.asaas.com/docs/sobre-os-webhooks), [eventos de cobrança](https://docs.asaas.com/docs/webhook-para-cobrancas).

## Entrar com Google

Crie um cliente OAuth do tipo aplicação Web no Google Cloud. Configure:

- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`
- URI de redirecionamento autorizada: `https://SEU-DOMINIO/api/auth/google/callback`
- Para desenvolvimento: `http://localhost:8080/api/auth/google/callback`

O botão só fica disponível quando as credenciais existem. O backend inicia a autorização com state, PKCE, escopos `openid email profile` e validade de dez minutos. Troca o código no servidor e usa o endpoint userinfo autenticado, exigindo e-mail verificado. Tokens não são persistidos nem enviados ao navegador.

O identificador permanente é `sub`. Se já existir uma conta local com o mesmo e-mail, não há vinculação silenciosa: a pessoa entra com senha e usa “Vincular minha conta Google” na seção Privacidade. Não há pedido de acesso à caixa de e-mail do Gmail, contatos ou Google Drive.

Referências: [OAuth em aplicações Web](https://developers.google.com/identity/protocols/oauth2/web-server), [OpenID Connect](https://developers.google.com/identity/openid-connect/openid-connect).

## Avisos automáticos

A automação é feita por `scripts/worker.php`, não por tarefas deste aplicativo de desenvolvimento. Configure um agendamento no servidor, normalmente a cada cinco minutos.

Avisos: ativação/degustação, confirmação de pagamento, aproximação do vencimento, período encerrado e pendência inicial. Cada motivo/período possui chave única, evitando novos avisos idênticos em cada execução. O proprietário escolhe e-mail, WhatsApp ou ambos. A autorização para WhatsApp é validada novamente imediatamente antes do envio.

### E-mail via Resend

Valide o domínio/remetente com o provedor e configure `RESEND_API_KEY` e `EMAIL_FROM`. O envio usa texto simples, chave idempotente por entrega e repetição limitada para falhas. A API aceitar um envio não garante que a mensagem chegou à caixa de entrada; acompanhe também o painel do provedor.

Referência: [API de envio do Resend](https://resend.com/docs/api-reference/emails/send-email).

### WhatsApp via Twilio

Configure um remetente WhatsApp Business aprovado e as variáveis:

- `TWILIO_ACCOUNT_SID`
- `TWILIO_API_KEY` e `TWILIO_API_SECRET`
- `TWILIO_WHATSAPP_FROM`, formato `whatsapp:+5531...`
- `TWILIO_CONTENT_SID`, identificador `HX...` de modelo aprovado

O envio usa a API oficial do provedor, sem automatizar WhatsApp Web. O template deve ser aprovado pelo WhatsApp para aviso operacional e aceitar as variáveis `1` (nome do negócio), `2` (texto do aviso) e `3` (URL do painel).

Sugestão a submeter à aprovação, sujeita às regras do provedor: “Olá, responsável por {{1}}. Há uma atualização do seu cadastro no Sarzedo por perto: {{2}}. Para conferir sua assinatura e as opções de atendimento, acesse {{3}}.” Ajuste o texto e a proporção de campos conforme a revisão do WhatsApp; um modelo ainda não aprovado não será enviado.

Referências: [templates de WhatsApp](https://www.twilio.com/docs/whatsapp/tutorial/send-whatsapp-notification-messages-templates), [envio com ContentSid](https://www.twilio.com/docs/content/send-templates-created-with-the-content-template-builder).

### Habilitação, falhas e custos

Depois de homologar, configure `NOTIFICATIONS_ENABLED=true`. Sem isso, o worker apenas prepara a fila. Tokens vazios mantêm a entrega pendente; não fingimos envio bem-sucedido.

No administrativo, `sent` significa aceito pelo provedor, não entregue/lido. `retry` é uma nova tentativa de e-mail dentro do intervalo. `uncertain` exige conciliação no provedor, pois uma interrupção durante WhatsApp pode ter enviado a mensagem. A implementação evita repetir automaticamente um WhatsApp incerto.

E-mails tentam novamente até cinco vezes com intervalo de cinco minutos. A chave do Resend possui janela limitada pelo provedor; uma falha muito antiga precisa ser conciliada, não reenviada sem critério. Respostas definitivas de falha/recusa e recibos de entrega podem ser integrados em webhooks numa próxima evolução.

A assinatura Asaas pode ter seus próprios avisos nativos; escolha no painel do Asaas se deseja mantê-los para não duplicar a mesma cobrança com os avisos do portal. Habilitação de remetentes, custos por envio e limites são definidos pelos provedores.


### Configuração de avisos pelo administrativo

Acesse Administração → Cobranças e avisos automáticos → Configurar canais de envio. Preencha um canal completo antes de habilitar a automação. O Resend usa chave API e e-mail remetente verificado; o Twilio usa Account SID, API Key SID, API Secret, remetente no formato whatsapp:+5531... e Content SID do modelo aprovado. As variáveis do modelo são 1: nome do negócio, 2: aviso e 3: link do painel. Campos vazios mantêm valores existentes.

As configurações são privadas e têm precedência sobre .env para esses campos. Em produção, proteja storage/integrations.json e seus backups; se SQLITE_PATH for definido, use o arquivo adjacente com sufixo .integrations.json. PHP web e worker precisam compartilhar a mesma configuração e permissões. A opção habilitar não cria tarefa no sistema operacional. Configure o worker a cada cinco minutos conforme PUBLICACAO.md. O formulário não configura o provedor de pagamentos Pix e não envia mensagens de teste automaticamente.


### PicPay pessoal: Pix estático com conferência manual

Administração → Pix pessoal · PicPay: informe uma chave Pix já cadastrada na sua conta, nome (até 25 caracteres) e cidade (até 15). Não informe senha bancária. A chave fica na configuração privada; o cliente a recebe no código Pix para poder pagar. O portal não consulta o PicPay nem valida a titularidade da chave. Confirme o nome do recebedor ao testar no aplicativo, sem necessidade de concluir transferência.

Habilitar o modo gera cobranças reais destinadas à chave informada. Cada cobrança tem valor contratado, txid único, QR Code local e Copia e Cola. As credenciais de envio de avisos são independentes. A chave/recebedor de cobranças já geradas não muda ao alterar a configuração.

Após verificar crédito, valor e pagador no extrato: Administração → Pix aguardando conferência → informe valor, data e identificador da transação → confirme. A ação é restrita ao administrador, auditada e registra receita e período pago em uma transação. Não utilize apenas imagem de comprovante como confirmação. Ativação administrativa de cortesia continua sem registrar receita.

A renovação gera novo Pix três dias antes do vencimento pelo worker ou ao consultar o painel. Não há débito automático: o cliente paga a cada mês. O código estático não expira nem pode ser revogado no banco pelo portal; não reutilize código pago. Envios e recebimentos reais não foram testados nesta entrega. Configuração local continua desabilitada até o responsável cadastrar e habilitar sua chave.

Referência do formato BR Code: https://www.bcb.gov.br/content/estabilidadefinanceira/pix/Regulamento_Pix/II_ManualdePadroesparaIniciacaodoPix.pdf (CRC validado pelo exemplo oficial). Biblioteca QR local: qrcode-generator 2.0.4, MIT.


### Configurar Google pelo administrativo

Use o atalho Configurar login Google no topo da administração. Informe Endereço base do portal (origem, sem caminhos), Client ID, Client Secret e habilite. Copie a URI de retorno exibida e cadastre-a exatamente nos redirecionamentos autorizados de um cliente OAuth Aplicativo da Web no Google Auth Platform. Configure também nome, contatos, público e publicação do aplicativo no console.

HTTP é aceito somente para localhost em desenvolvimento; produção requer HTTPS. A configuração Google usa GOOGLE_BASE_URL sem alterar APP_URL das notificações e pagamentos. O segredo fica no arquivo privado de integrações, não é devolvido ao navegador nem incluído na auditoria. Campo vazio preserva segredo; troca do Client ID exige segredo correspondente. Desabilitar mantém as credenciais e bloqueia novas autorizações.

O status habilitado indica configuração local, não validação pelo Google. A validação real exige credenciais válidas, URI cadastrada no Google e conclusão do login por um usuário. Credenciais reais não foram fornecidas nem testadas nesta entrega. Para contas locais já existentes, entrar com senha e vincular Google em Privacidade evita união automática de contas.

### Google no Windows: certificados HTTPS

O cliente HTTP do portal utiliza CURLSSLOPT_NATIVE_CA no Windows, quando disponível, para validar HTTPS usando as autoridades confiáveis do sistema. Isso resolve o erro cURL 60 causado por cadeias locais confiáveis ausentes no bundle OpenSSL. A verificação de certificado e de hostname continua ativa. Em Linux, utiliza-se o bundle CA configurado no servidor.

Callback local: http://localhost:8080/api/auth/google/callback. Cadastre esse endereço como URI de redirecionamento autorizado no cliente OAuth do tipo Aplicativo Web. No administrativo, a URL base é apenas http://localhost:8080. Comece o login pelo botão do portal; abrir o callback manualmente não inicia autenticação.
