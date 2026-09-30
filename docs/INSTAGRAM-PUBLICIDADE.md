# Publicidade avulsa no Instagram do Guia Sarzedo

A integração é opcional e permanece desligada até ser configurada no painel administrativo. Não publica publicações comuns nem promoções incluídas nos planos. Também não republica anúncios antigos.

## Fluxo

1. O negócio aprovado paga um pedido de publicidade avulsa. A confirmação do Pix pelo Asaas continua independente do Instagram.
2. Em **Minha conta → Publicidade**, o cliente monta o anúncio e seleciona **Pré-visualizar publicidade e post**. A prévia mostra o anúncio do portal, a imagem e a legenda que irão ao Instagram.
3. O cliente confirma a prévia e pode marcar **Autorizo publicar ... no Instagram @guia_sarzedo**. Sem esse consentimento, apenas a publicidade no portal é criada.
4. Ao salvar, o portal registra no máximo um item de Instagram por publicidade paga. O agendador publica no máximo um por dia, somente em produção, com a integração habilitada.
5. O estado aparece em **Minha conta** e na grade administrativa. `published` guarda o ID e o link do post. `needs_review` indica resultado incerto depois da tentativa de publicar: o sistema não repete automaticamente para evitar duplicidade.

O token fica em `storage/integrations.json`, fora da pasta pública, com permissão restrita. O painel devolve somente o estado da configuração, nunca o token. A ativação exige senha de administrador e verifica se a credencial aponta para `@guia_sarzedo`.

## Configuração necessária na Meta

Use uma conta Instagram profissional ligada a uma Página do Facebook e um aplicativo Meta com as permissões de leitura da conta e `instagram_content_publish`. Gere um **Page Access Token** com acesso a essa Página e obtenha o ID numérico da conta Instagram profissional. No painel administrativo, abra **Configurar Instagram**, informe ID e token, marque a ativação e confirme sua senha. O token precisa permanecer válido; ao expirar, novos itens não serão publicados até atualização. Não cole a senha do Instagram no portal.

O servidor precisa de HTTPS público, `APP_URL` correto e um agendador chamando `php scripts/worker.php` a cada cinco minutos. A API da Meta busca a imagem na URL pública do portal. A integração usa a [Instagram Graph API](https://www.postman.com/meta/instagram/documentation/6yqw8pt/instagram-api) com Facebook Login.

Antes de habilitar em produção, faça uma publicidade controlada com uma conta de teste do portal e confira imagem, legenda, link e estado. A integração no ambiente local e na homologação não envia posts reais.
