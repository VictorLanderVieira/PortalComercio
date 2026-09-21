# Publicação na Hostinger

Atualizado em 20/09/2026.

## Produção

- Domínio: `guiasarzedo.com.br` (Registro.br).
- Plano: Hostinger Premium, contratado pelo proprietário.
- URL temporária verificada: https://deepskyblue-guanaco-618704.hostingersite.com/
- PHP 8.3.33 e extensões necessárias disponíveis.
- Banco: `u211282174_portal_prod`, migrações até a versão 10 aplicadas, sem dados de demonstração.
- Diretório privado: `/home/u211282174/domains/guiasarzedo.com.br/portal`.
- Configuração: `portal/shared/.env`, com permissão 600.
- `public_html` aponta para `portal/current/public`; página padrão preservada em `public_html.before-portal`.
- Pacote e checksum em `portal/incoming`; instalador em `portal/deploy.sh`.
- API de catálogo: HTTPS 200, planos e categorias carregados.
- Arquivos `.env`, servidor e banco local não acessíveis por URLs públicas verificadas.

## Acesso e atualizações

SSH ativado com autorização do proprietário. A chave privada local está em `storage/deploy/hostinger_ed25519`, fora dos pacotes de publicação e ignorada pelo Git. Não compartilhar nem adicionar ao repositório. A chave pública está cadastrada como `guiasarzedo-deploy`.

O host é `147.93.38.156`, porta `65002`, usuário `u211282174`. A chave do servidor foi registrada em `storage/deploy/known_hosts` na primeira conexão.

Para futuras versões, gerar o pacote com o script do projeto, enviar pacote e checksum para `portal/incoming` e executar `bash deploy.sh update production` dentro de `portal`. O instalador valida o ambiente e salva backup antes das migrações. Seguir o guia de publicação para configurar GitHub Actions; essa integração ainda não foi configurada.

## Pendências

- DNS: painel autenticado do Registro.br conferido em 20/09/2026; já exibe `pixel.dns-parking.com` e `byte.dns-parking.com`. O painel informa transição em andamento, com aproximadamente 1h55 restantes no momento da consulta. Aguardar conclusão e verificar resolução pública; nenhuma alteração adicional foi realizada.
- Verificar HTTPS e funcionamento no domínio definitivo após propagação.
- Conta administrativa `admin@sarzedo.local` criada com os dados fornecidos pelo proprietário. Login pela interface aguarda conclusão pelo proprietário. Esse endereço não recebe e-mails.
- Configurar chave Pix e Google no administrativo antes de liberar esses recursos. Pagamentos e login Google permanecem desabilitados na configuração inicial.
- Criar e proteger ambiente de testes com banco separado. Apenas o banco de produção foi confirmado no painel.

Nenhuma senha foi incluída neste documento.
