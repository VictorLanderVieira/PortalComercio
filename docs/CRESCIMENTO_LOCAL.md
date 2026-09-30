# Plano local de crescimento — Guia Sarzedo

Rascunho para validação. Nenhuma alteração na página pública ou na produção foi feita nesta análise.

## Diagnóstico em 28/09/2026

- API pública de produção: **2 empresas visíveis**, **0 promoções ativas** e **0 publicidades ativas**. Esses números mudam conforme cadastros, aprovações e validades.
- O portal já oferece busca por nome, categoria e bairro, filtros de atendimento, página própria para cada negócio, notas, WhatsApp, localização, ofertas e planos. O ponto fraco imediato é a pouca oferta de conteúdo real e atualizado para o morador voltar.
- A tela inicial de testes mostra banners de exemplo quando não há ofertas reais. São úteis para vender o espaço, mas não substituem descoberta local de verdade.

## Meta de produto

Fazer o morador resolver uma necessidade local em menos de um minuto: achar uma opção confiável, saber se atende sua região/horário, ver uma oferta vigente e entrar em contato. Fazer o comerciante enxergar visitas ao perfil e contatos gerados, sem prometer vendas.

## Próximos 30 dias

1. **Massa inicial:** convidar pessoalmente 30 negócios distribuídos entre alimentação, mercados, farmácias, beleza, oficina, construção, transporte e autônomos. Priorizar perfis completos, com descrição específica, horário, serviço, bairro e contato autorizado. Usar o Free de 30 dias para reduzir a barreira inicial; não cadastrar ninguém sem consentimento.
2. **Conteúdo recorrente:** conseguir 10 ofertas reais com data de validade de participantes que escolherem Destaque/Premium. Publicar um resumo semanal por WhatsApp e redes, com links diretos para as páginas dos participantes. Se ainda não houver ofertas reais, divulgar uma seleção útil de negócios cadastrados, identificada como guia, sem inventar descontos.
3. **Distribuição por parceiros:** apresentar uma proposta curta à ACIAPS de Sarzedo, à Sala Mineira do Empreendedor e a administradores de grupos locais. Oferecer uma demonstração do cadastro e um link/QR rastreável para cada parceiro. Pedir divulgação voluntária, sem insinuar endosso institucional.
4. **Aprendizado:** acompanhar semanalmente empresas ativas, perfis completos, promoções vigentes, buscas, visitas aos perfis, cliques em WhatsApp/mapa e conversão de Free para pago. Perguntar a 5 moradores se acharam o que procuravam e a 5 comerciantes se os contatos foram úteis.

## Parceria proposta

> O Guia Sarzedo ajuda moradores a encontrar comércios e profissionais da cidade por bairro, serviço e oferta vigente. Queremos convidar seus associados/participantes a criar uma vitrine gratuita por 30 dias. Podemos fazer uma apresentação rápida do portal, fornecer um QR Code para cadastro e compartilhar indicadores agregados de uso. A participação e a divulgação seriam voluntárias; os dados de cada empresa só entram com autorização do responsável.

Para lojas ou prestadores com audiência própria, sugerir divulgação cruzada: o parceiro compartilha sua página do Guia Sarzedo, e o portal destaca uma oferta real e vigente dentro das regras do plano. Para associação ou órgão público, começar com demonstração e escuta, sem oferecer prioridade paga disfarçada de recomendação editorial.

## Melhorias de produto, em ordem

1. **Completar perfis e ofertas reais antes de redesenhar a página.** Mostrar data de atualização e horários corretos já é possível; incentivar preenchimento. Evitar blocos vazios e distinguir exemplos de ofertas reais.
2. **Compartilhamento de cada oferta.** Link direto e imagem apropriada para WhatsApp, com título, preço e validade. Hoje o link compartilhado da oferta leva ao perfil da empresa; uma página de oferta própria facilitaria medir a campanha e evitaria o usuário procurar a promoção dentro do perfil.
3. **Guias úteis por necessidade:** “onde comer”, “serviços em domicílio”, “aberto agora”, “para seu bairro”. Gerar páginas somente quando houver negócios suficientes e informação real, para não criar páginas vazias ou repetitivas.
4. **Alertas opcionais para moradores:** receber novas ofertas de categorias/bairros escolhidos, por e-mail ou canal consentido. Implementar apenas depois de haver frequência de novidades e processo claro de descadastro.
5. **Prova de resultado para assinantes:** relatório mensal simples com visitas ao perfil, cliques no WhatsApp/mapa e cliques em ofertas, comparado ao mês anterior. Nomear como interesse, não como vendas confirmadas.

## Como medir se está funcionando

| Indicador | Linha de base | Primeiro sinal de tração |
|---|---:|---:|
| Empresas visíveis | 2 | 30 com perfil completo |
| Promoções vigentes | 0 | 10 de empresas reais |
| Publicidades vigentes | 0 | Testar somente após haver audiência demonstrável |
| Buscas e contatos | Medir no painel | Crescimento semanal e retorno de visitantes |

Os números da última coluna são metas de experimento, não projeções de receita. Antes de investir em tráfego pago ou vender mais espaços publicitários, confirmar que o morador encontra opções suficientes e que os comerciantes recebem contatos úteis.

## Convites por WhatsApp e e-mail: proposta local, sem disparos

O módulo atual de Resend/Twilio foi feito para avisos operacionais de contas existentes. O e-mail e o WhatsApp informados por quem se cadastra não autorizam automaticamente campanhas de aquisição ou propaganda. Não importar listas extraídas de grupos, Google Maps ou redes sociais para envio em massa.

Fluxo proposto para futura automação:

1. Página curta de interesse: empresa/autônomo informa nome, categoria, bairro, contato e marca separadamente se deseja receber convites do Guia Sarzedo por e-mail e/ou WhatsApp. Nenhuma opção pré-selecionada. Registrar data, origem, texto aceito e versão da política.
2. Importação administrativa apenas de contatos com origem e permissão documentadas. Deduplicar, validar formato e manter lista de bloqueio/descadastro. Nunca reaproveitar autorização para avisos de cobrança como permissão de marketing.
3. Campanha em rascunho com prévia, destinatários, custo estimado, limite diário e envio de teste ao próprio responsável. O disparo real exige uma decisão administrativa explícita depois de conferir público e texto.
4. E-mail por provedor de campanhas com domínio verificado e link de descadastro; WhatsApp por conta Business/API oficial e modelo de marketing aprovado. Registrar aceitação, falha, resposta e pedido de parada; respeitar limites e preferências de canal.
5. Medir visitas ao link, cadastros iniciados e concluídos por campanha. Não interpretar e-mail aceito pelo provedor ou mensagem enviada como venda realizada.

Texto curto para canal ou parceiro que já aceitou compartilhar o convite:

> Comércio ou trabalho autônomo em Sarzedo? O Guia Sarzedo ajuda moradores a encontrar negócios por serviço e bairro. Cadastre sua vitrine gratuitamente por 30 dias, com descrição e contato: https://guiasarzedo.com.br/ . Planos pagos permitem mais fotos, novidades e promoções. Dúvidas: (31) 98767-1102.

E-mail individual para contato que solicitou informações:

> Assunto: Sua empresa no Guia Sarzedo
>
> Olá, [nome]. Conforme seu interesse em conhecer o Guia Sarzedo, segue o convite para cadastrar [negócio]: https://guiasarzedo.com.br/ . O cadastro Free fica disponível por 30 dias. Se quiser divulgar fotos, novidades e promoções, há planos mensais a partir de R$ 50. Posso ajudar pelo WhatsApp (31) 98767-1102. Se não quiser mais receber nossos convites, use [link de descadastro].

**Situação atual:** não há lista de contatos autorizados, consentimento promocional nem campanha configurada no portal. Este documento não habilita envios.

## Referências consultadas

- [ACIAPS — Associação Comercial, Industrial, Agropecuária e Prestação de Serviços de Sarzedo](https://www.aciapssarzedo.com.br/)
- [Sala Mineira do Empreendedor em Sarzedo — RedeSim/Jucemg](https://redesimhom.jucemg.mg.gov.br/unidades-atendimento)
- [Google Search Central — dados estruturados de negócios locais](https://developers.google.com/search/docs/appearance/structured-data/local-business)
- [Google Business Profile — publicações, ofertas e novidades](https://support.google.com/business/answer/7342169?hl=pt-BR)
- [Google Search Central — conteúdo útil feito para pessoas](https://developers.google.com/search/docs/fundamentals/creating-helpful-content)
