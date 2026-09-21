# Sarzedo por perto

Portal de empresas e profissionais autônomos de Sarzedo, desenvolvido em **AngularJS 1.8.3 + PHP 8.3**, com **MySQL 8 para publicação** e SQLite para execução local. Interface responsiva e PWA instalável.

## Abrir agora

O ambiente local fica em **http://localhost:8080**. Administração: **http://localhost:8080/#!/admin**.

O acesso administrativo local é gerado aleatoriamente e salvo em `storage/acesso-local.txt`. Não há senha fixa de administrador no código. Este arquivo, o banco local e os uploads não devem ser publicados.

```powershell
npm install
npm run setup
npm start
```

Requisitos locais: Node 20+, PHP 8.3 com PDO SQLite, GD, cURL, mbstring e fileinfo. Os comandos habilitam `pdo_sqlite` na execução sem alterar o PHP da máquina. Em Linux, habilite a extensão pelo gerenciador de pacotes e remova `-d extension=pdo_sqlite` caso ela já esteja carregada.

`setup` cria os dados ilustrativos. Em produção, use apenas a migração, **nunca o seed/demo**. O processo de publicação está em [docs/PUBLICACAO.md](docs/PUBLICACAO.md).

## O que esta versão contém

- Página inicial com pesquisa, categorias, bairro, cartões, notas de 1 a 5 estrelas, promoções com navegação por slides e comparação de planos.
- Cadastro de empresa ou autônomo, plano de interesse separado do plano contratado, mini site, objetivo, descrição, logo, capa, galeria, novidades e links para WhatsApp, Google Maps, Instagram, Facebook e site.
- Campanha de captação com início e fim configuráveis. Durante a campanha, a degustação termina na data configurada, inclusive para cadastros feitos perto do encerramento.
- Prazo normal de cinco dias: na ausência de campanha ou pagamento, nome e resumo aparecem sem contatos. Depois, sai da consulta pública e permanece no painel. Esta é a interpretação inicial adotada para os cinco dias, configurada no código e documentada para revisão.
- Pix mensal: cobrança via Asaas, QR Code, copia e cola, confirmação por webhook, conciliação periódica, expiração automática por data e cancelamento da renovação. Sem cartão nesta versão.
- Modo local para simular confirmação sem dinheiro real.
- Administração com inspeção de dados e fotos, ativação por prazo, suspensão, trilha de auditoria e campanha de lançamento.
- Gestão com potencial mensal da captação, interesse por plano, recebimentos, estornos, cobrança vencida, mensalidades pagas vigentes e gráfico de 12 meses.
- Login Google por OAuth com estado, PKCE e sessão no servidor, condicionado às credenciais.
- Fila de avisos automáticos com e-mail via Resend e WhatsApp via Twilio, preferências de canal, consentimento para WhatsApp e agendador PHP.
- Exportação dos próprios dados, pedido de acesso/correção/exclusão/revogação e atendimento no administrativo.

## Licenciamento proposto

| Benefício | Essencial | Destaque | Premium |
|---|---:|---:|---:|
| Mensalidade Pix | R$ 50 | R$ 75 | R$ 100 |
| Fotos de galeria | 5 | 12 | 25 |
| Publicações por mês-calendário | 2 | 8 | 20 |
| Promoções simultâneas no mini site e nos slides | 0 | 2 | 5 |
| Prioridade padrão na busca | 3ª | 2ª | 1ª |
| Logo, capa, contatos, redes e avaliações | Sim | Sim | Sim |

A promoção aceita uma imagem própria, enviada separadamente, ou uma imagem da biblioteca do portal. O comerciante não pode excluir uma promoção publicada; a exclusão administrativa fica registrada. Logo e capa não consomem o limite da galeria. Não há cobrança avulsa por postagem. Maior plano amplia a vitrine, não compra notas melhores. O visitante pode ordenar por avaliação.

## Integrações e credenciais

Copie `.env.example` para `.env` e preencha somente os serviços que pretende ativar. Não envie segredos por mensagens públicas nem os versionе.

| Serviço | Configuração | Situação inicial |
|---|---|---|
| Pix | `PAYMENT_DRIVER=asaas`, chave Asaas, ambiente e segredo de webhook | Simulado localmente |
| Login Google | ID/segredo OAuth e URL autorizada | Botão sinaliza indisponibilidade sem credenciais |
| E-mail automático | chave Resend e remetente validado | Fila sem envio |
| WhatsApp automático | conta/chave Twilio, remetente e modelo aprovado | Fila sem envio |
| Agendador | executar `scripts/worker.php` a cada 5 minutos | Não instalado no SO automaticamente |

Nesta primeira versão, o portal gera Pix com valor fixo usando a chave pessoal cadastrada pelo administrador. A confirmação é manual, exige conferência no extrato e ativa o plano após o registro do recebimento.

## Validação

```powershell
npm test
npm run test:ui
```

Os testes da API usam banco SQLite isolado e simulam a operação financeira. Os testes de interface usam Chrome instalado e outra porta/banco isolado. Não fazem envios ou cobranças externas. Imagens das verificações ficam em `test-results/`.

**Limites de validação:** MySQL real, Pix sandbox/produção, OAuth Google, entrega de e-mail e WhatsApp precisam ser homologados com as contas do responsável. Nenhum desses serviços foi acionado com credenciais reais durante o desenvolvimento.

**AngularJS:** o suporte oficial terminou em janeiro de 2022. Foi mantido por solicitação do projeto. O npm informa vulnerabilidade conhecida na dependência. Esta base deve passar por decisão de atualização para Angular suportado, ou suporte estendido e revisão de segurança, antes da abertura pública. CSP, escaping e validações aplicadas aqui não substituem atualização da dependência.

## Documentação

- [Regras e fluxo de captação](docs/PRODUTO.md)
- [Arquitetura, banco e API](docs/ARQUITETURA.md)
- [Publicação e operação](docs/PUBLICACAO.md)
- [Pix, Google e avisos automáticos](docs/INTEGRACOES.md)
- [Privacidade e LGPD](docs/LGPD.md)
- [Divulgação no WhatsApp](docs/DIVULGACAO.md)
- [Histórico e pendências de homologação](docs/DESENVOLVIMENTO.md)

