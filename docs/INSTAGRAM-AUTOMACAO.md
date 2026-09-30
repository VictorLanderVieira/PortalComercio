# Integração de conteúdo do portal com @guia_sarzedo

> Este é um estudo anterior sobre todas as publicações e o Metricool. A primeira implementação foi limitada à **publicidade avulsa paga**, com consentimento específico, fila única e Instagram Graph API direta. Consulte [INSTAGRAM-PUBLICIDADE.md](INSTAGRAM-PUBLICIDADE.md) para o fluxo atual.

## Viabilidade

É possível criar e programar publicações por API do Metricool. Em setembro de 2026, o acesso à API exige plano Advanced ou Custom; Free e Starter não incluem esse acesso. Referências oficiais: [guia da API](https://help.metricool.com/basic-guide-for-api-integration-r97af), [planos e acesso](https://help.metricool.com/plans-add-ons-and-api-access-explained-xux1u), [publicação no Instagram](https://help.metricool.com/schedule-and-post-on-instagram-6b6q5).

O responsável usa Free ou Starter atualmente. Portanto, o portal não consegue enviar posts automaticamente pelo Metricool nessa conta. As opções são contratar Advanced/Custom, criar uma integração direta com a [Instagram API da Meta](https://www.postman.com/meta/workspace/instagram/documentation/23987686-9386f468-7714-490f-9bfc-9442db5c8f00), ou manter a fila de conteúdos selecionados no portal e agendar manualmente no Metricool. A integração direta exige conta profissional, configuração de aplicativo Meta, permissões de publicação e testes de autenticação antes de ativar.

Para publicação automática, conectar @guia_sarzedo como conta profissional ao Metricool, preferencialmente via Página do Facebook vinculada. Uma conta pessoal ou uma conexão com limitações pode exigir publicação manual.

## Regra recomendada para o Guia Sarzedo

1. Promoção: só entra na fila social se o negócio estiver aprovado, a promoção estiver vigente, o plano permitir promoções e a empresa tiver aceitado o uso da imagem e do texto no Instagram do portal.
2. Publicidade avulsa: só entra na fila depois de pagamento confirmado e ativação da peça. A contratação atual de publicidade no site não deve ser presumida como contratação de post no Instagram; é preciso definir essa vantagem e o consentimento no produto.
3. Revisão: o administrador vê prévia da arte e legenda, aprova ou rejeita o conteúdo específico. Aprovar o cadastro da empresa não aprova automaticamente futuras peças.
4. Programação: um worker envia o item aprovado ao Metricool, guarda o ID remoto e impede duplicação mesmo se houver nova tentativa após falha.
5. Antes da publicação: verificar de novo aprovação, vigência e suspensão do negócio. Se deixou de ser elegível, cancelar ou remover o agendamento.
6. Operação: registrar tentativas, erros e status; permitir reenvio controlado e desligar a integração sem afetar publicações no portal.

Não enviar telefone privado, e-mail do responsável, comprovantes, token da API ou outros dados administrativos. A legenda deve conter apenas dados públicos do negócio e um link para sua página no Guia Sarzedo. Imagens devem ter URL pública estável; a API do Metricool normaliza a mídia e usa o `mediaId` no agendamento.

## Frequência editorial

Evitar um post por promoção ou publicidade no feed. Isso tornaria o perfil repetitivo e poderia privilegiar quem publica muitas peças. Começar com até 1 post de publicidade paga por dia, se esta vantagem for vendida explicitamente, e uma seleção de ofertas da semana em carrossel. Stories podem receber mais peças, observadas as restrições de publicação automática do Instagram. O administrador deve poder alterar a ordem e horários.

## Configuração necessária

- Plano Metricool com API, token `X-Mc-Auth`, `userId`, `blogId` e Instagram conectado à marca.
- Campos administrativos para habilitar/desabilitar integração, fuso `America/Sao_Paulo`, horários permitidos e limite diário.
- Token guardado fora do Git e da resposta ao navegador, seguindo o padrão de segredos já usado pelo portal para Asaas.
- Tabela de fila social com origem (`promotion` ou `advertisement`), ID da origem, estado, versão do conteúdo, consentimento, horário, ID Metricool e histórico de erros. Índice único por origem e versão para evitar duplicatas.
- Arte de feed 1080 × 1350 px com logo do Guia, nome da empresa, oferta e validade; imagem enviada pela empresa com redimensionamento proporcional. Não usar diretamente o banner de publicidade horizontal como post vertical.

Sem plano/API configurados, nenhuma ação automática deve ser tentada; o portal continua funcionando e o administrador pode produzir posts manualmente no Metricool.
