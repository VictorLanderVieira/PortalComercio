# Regras de produto — versão inicial

## Captação antes do lançamento

A finalidade da primeira fase é descobrir quantos negócios têm interesse e em qual plano. O cadastro não gera uma cobrança automaticamente: a pessoa escolhe seu plano de interesse, e o administrador enxerga a previsão mensal caso esses interessados se convertam em assinantes.

`interest_plan_id` é a intenção comercial; `plan_id` rege os recursos do cadastro. No primeiro cadastro, o formulário usa o plano escolhido em ambos. Depois, editar o interesse não muda uma assinatura, seu valor, limites ou prioridade.

O administrador configura a campanha em `/#!/admin`. Use início e fim no formato `AAAA-MM-DD HH:MM:SS`, no fuso America/Sao_Paulo. O sistema não inventa uma data oficial de lançamento. Por isso a campanha começa desativada até sua configuração. Novos cadastros dentro da janela recebem `trial_until` igual ao fim da campanha.

Exemplo: captação entre 01/10 e 31/10. Um cadastro em 05/10 e outro em 29/10 experimentam até 31/10. Não são 30 dias individuais. Alterar a janela não modifica benefícios já concedidos; o administrador pode conceder dias por ativação manual quando necessário.

## Estados e contatos

1. Cadastro normal: cinco dias para ativar. Nesta implementação, aparece apenas nome, categoria, bairro e apresentação, sem telefone, mapa, endereço exato e links de contato.
2. Depois dos cinco dias sem ativação: o registro permanece na conta e no administrativo, mas sai da busca e sua página pública retorna indisponibilidade.
3. Degustação: página e contatos ativos até `trial_until`, conforme opções de privacidade.
4. Pagamento confirmado: acesso vigente até o fim do período pago.
5. Cortesia administrativa: acesso até `manual_until`, sem criar recebimento financeiro.
6. Suspensão: retira imediatamente a presença pública mesmo quando há pagamento. Pagamentos posteriores não removem a suspensão.
7. Licença expirada: desaparece da busca independentemente de um cron estar funcionando, pois toda consulta verifica a data.

A data de acesso é a maior entre degustação, período pago e cortesia. Retirar suspensão não concede prazo adicional.

Fora da campanha, o tratamento dos primeiros cinco dias é uma decisão de produto que pode ser ajustada após a primeira revisão. Não há divulgação automática de contatos sem ativação nesse período.

## Planos

Essencial (R$ 50): presença organizada, 5 fotos, 2 novidades por mês, contatos, redes e avaliações. Sem promoção em slides.

Destaque (R$ 75): 12 fotos, 8 novidades/mês, 2 promoções simultâneas, segunda prioridade de busca. Indicado para negócios com ofertas recorrentes.

Premium (R$ 100): 25 fotos, 20 novidades/mês, 5 promoções simultâneas, primeira prioridade de busca e dos slides. Indicado para operação com maior frequência de divulgação.

Limites são conferidos no backend. Limpar uma foto permite substituí-la. Publicações contam pelo mês-calendário. Promoções contam enquanto sua validade não terminou; podem ser removidas. Expiração máxima de promoção: 90 dias. Só um anúncio com licença/degustação/cortesia vigente pode publicar novidades e promoções.

Cada publicação pode incluir uma imagem opcional, enviada em JPG, PNG ou WebP de até 5 MB. O servidor redimensiona a imagem para no máximo 1600 px no maior lado e a armazena como JPEG. Publicações sem imagem continuam válidas; a imagem não consome uma vaga adicional da galeria, mas a publicação continua sujeita ao limite mensal do plano.

No mini site, a imagem da publicação aparece como miniatura lateral e abre ampliada ao clicar. Moradores podem criar uma conta gratuita sem cadastrar empresa para avaliar negócios com avaliações habilitadas; a regra de uma avaliação por conta e negócio permanece.

O cadastro pode informar abertura e fechamento por dia, entrega e atendimento em domicílio. A busca filtra por esses dados e por promoções vigentes. Negócios sem horários estruturados não aparecem em “Aberto agora”; o texto livre de horários continua visível. A página da empresa mostra a data da última atualização informada pelo proprietário.

Cada empresa tem uma página pública em `/empresa/{id}` com metadados próprios para compartilhamento, dados estruturados e inclusão no sitemap. A página só existe enquanto o cadastro puder aparecer publicamente; dados de contato seguem as mesmas restrições da API. A área interativa de avaliação permanece em `#!/empresa/{id}`.

O painel do proprietário apresenta interações dos últimos 30 dias: visitas à página, cliques no WhatsApp, no mapa e nas ofertas. O contador desconsidera robôs conhecidos, administradores, o próprio dono e repetições próximas na mesma sessão. São sinais de interesse, não vendas confirmadas nem pessoas únicas. Os dados agregados não armazenam identidade dos visitantes.

O plano não garante vendas, impressão mínima nem exclusividade por categoria. A ordenação padrão é prioridade do plano, média das avaliações e nome. O visitante pode selecionar melhor avaliação, ignorando a prioridade comercial. Avaliações vão de 1 a 5, requerem conta, são únicas por conta/empresa e não podem ser feitas pelo proprietário da vitrine.

## WhatsApp e autônomos

O número com DDD é obrigatório para atendimento do negócio. A pessoa pode preferir receber avisos operacionais por e-mail. Avisos via WhatsApp requerem autorização específica, revogável ao editar a vitrine. Essa autorização não se aplica a publicidade de terceiros.

Autônomos têm o mesmo acesso aos planos. Podem usar categoria Autônomos ou a categoria específica do serviço. Podem ocultar o endereço e o mapa e divulgar somente bairro/região de atendimento, preservando o endereço residencial.

## Gerencial

- Potencial mensal: soma do preço do plano desejado por cada cadastro real, inclusive não convertido. Não é receita realizada nem promessa de pagamento.
- Mensalidades pagas vigentes: referência mensal dos planos com validade paga atual. Não inclui degustações e cortesias.
- Recebido acumulado: pagamentos confirmados com data de pagamento, em valor bruto.
- Estornos: valores registrados como devolvidos/contestados, separados do recebimento histórico.
- Saldo antes das taxas: recebido menos estornado. Não é saldo bancário nem lucro.
- A vencer: cobranças ainda pendentes com vencimento hoje ou futuro.
- Atraso: cobrança pendente/vencida com vencimento anterior a hoje. Não confundir fim de degustação com dívida.
- Conversão: cadastros com ao menos um recebimento histórico / cadastros captados.
- Gráfico: 12 meses móveis, pela data de pagamento e pela data de registro do estorno, com tabela acessível.

Dados fictícios e IDs de pagamento `demo_` são excluídos dos indicadores monetários. Cartões e parcelamentos não fazem parte desta versão.


### Administração dos preços

Em Valores dos planos, configure mensalidade normal ou habilite promoção com preço menor, início e fim. Desabilitar a promoção ou chegar ao horário final retoma o preço normal para novas assinaturas. Assinaturas já contratadas mantêm seu preço mensal; a promoção não se limita ao primeiro mês. O histórico de pagamentos e a ordenação por categoria de plano não mudam.

## Plano Free e vencimento (19/09/2026)

Free: R$ 0, cadastro básico com descrição, contatos, mapas e redes sociais; imagem padrão, zero uploads, galeria, publicações, promoções e avaliações. Visível por 30 dias corridos desde created_at. Suspensão administrativa prevalece.

Essencial: 5 fotos, 2 publicações/mês, avaliações e prioridade sobre Free. Destaque: 12 fotos, 8 publicações/mês, 2 promoções simultâneas e segunda prioridade. Premium: 25 fotos, 20 publicações/mês, 5 promoções e primeira prioridade. Valores pagos continuam configuráveis no administrativo.

Ao terminar o maior prazo de pagamento, degustação ou cortesia, a vitrine paga aparece como Free por 5 dias desde esse vencimento. Depois sai da busca. Não altera plan_id nem apaga fotos, publicações, promoções ou avaliações. Pagamento confirmado restaura os benefícios do plano contratado; promoções continuam sujeitas à própria validade.

O cliente se cadastra, gera a cobrança Pix no painel e paga. O botão da cobrança envia o comprovante ao WhatsApp (31) 98767-1102: o cliente anexa e envia o arquivo. Não há atalhos de ativação pelo WhatsApp nos planos. O administrador confere o crédito e confirma a cobrança no painel. Enviar comprovante não ativa automaticamente nem registra recebimento.

Avaliações: planos pagos permitem ao proprietário habilitar/desabilitar em Minha vitrine → Disponibilizar avaliações dos clientes. Padrão habilitado para cadastros novos e existentes. Desabilitar oculta notas, contagens e comentários, impede novas avaliações e remove a nota do desempate na busca, sem excluir registros. A preferência permanece após vencimento e renovação; nunca libera avaliações no Free.

Na aba **Avaliações** de Minha Conta, o proprietário pode retirar uma avaliação individual da exibição pública após confirmar a ação. A nota média e a contagem passam a considerar apenas avaliações visíveis. O registro é preservado com data e usuário responsável pela remoção para auditoria; o cliente que publicou a avaliação não pode criar outra para o mesmo negócio usando a mesma conta.

O plano de interesse é coletado somente no cadastro inicial e preservado como histórico de captação. Minha conta exibe o plano atual (plan_id), sem seletor de interesse na edição. A API ignora tentativas de alterar ambos por edição de perfil. A troca continua por pagamento confirmado ou ativação administrativa. Imagens de cartão e capa são ajustadas proporcionalmente para exibição completa, sem recorte, com fundo neutro nas sobras.

No Free, a imagem de cartão e topo pode ser escolhida na biblioteca fixa em Fotos e identidade, sem upload. A escolha é única para ambos, preservada separadamente da capa paga. Sem escolha, usa-se imagem por categoria. Biblioteca: comércio local, farmácia/saúde, alimentação, mercado, beleza, oficina/serviços, flores e academia. Categoria adicionada: Academia / Personal Trainer.

