# Privacidade e LGPD — base de implementação

Este documento organiza o trabalho técnico e operacional. Não representa certificação de conformidade. O responsável pelo portal deve identificar o controlador, validar as bases legais e os prazos de retenção de sua operação antes de captar dados pessoais reais.

Referências consultadas: [Lei 13.709/2018 — LGPD](https://www.planalto.gov.br/ccivil_03/_ato2015-2018/2018/lei/l13709.htm) e [guia de segurança para agentes de pequeno porte da ANPD](https://www.gov.br/anpd/pt-br/centrais-de-conteudo/materiais-educativos-e-publicacoes/guia-orientativo-sobre-seguranca-da-informacao-para-agentes-de-tratamento-de-pequeno-porte).

## Registro das operações de tratamento

| Dados | Finalidade | Exposição / compartilhamento | Base a avaliar pelo responsável |
|---|---|---|---|
| Nome, e-mail, senha com hash | Conta e autenticação | Privados; administrador autorizado | Execução de contrato/procedimentos preliminares |
| Identificador Google, nome e e-mail | Login solicitado pelo titular | Google e backend; sem caixa de Gmail | Execução do serviço solicitado |
| Nome comercial, categoria, descrição, fotos, links | Divulgação da empresa/autônomo | Página pública durante vigência | Contrato e autorização de publicação |
| Endereço e mapa | Localizar o estabelecimento | Publicação opcional; ocultável para autônomo | Necessidade do serviço e escolha expressa |
| WhatsApp | Contato comercial; avisos de serviço | Público em anúncio ativo; WhatsApp aberto manualmente pelo administrador | Contato e atendimento referentes ao cadastro e ao plano |
| E-mail do responsável | Avisos operacionais | Resend e responsável | Execução de contrato; não publicidade indiscriminada |
| CPF/CNPJ no checkout | Emitir cobrança Pix | Enviado ao Asaas; não persistido localmente | Contrato/obrigação aplicável |
| IDs e estados de cobrança | Confirmar pagamento e acesso | Privados; Asaas e admin | Contrato/obrigações legais e exercício de direitos |
| Nome, comentário e nota de avaliação | Compartilhar experiência | Público | Termos de uso/serviço; avaliar proteção dos titulares |
| Cookies de sessão e chave técnica de tentativas | Autenticação e prevenção de abuso | Uso interno; sem rastreador de marketing | Segurança e prestação do serviço |
| Ações administrativas e solicitações | Prestação de contas e atendimento | Administração restrita | Obrigação aplicável/exercício de direitos |

Não são solicitados dados sensíveis. Não incluir documentos, saúde, dados de terceiros ou endereço residencial de cliente em fotos, descrições e avaliações.

## O que está implementado

- A conta exige ciência da política; não há caixa de publicidade pré-selecionada.
- A primeira publicação exige confirmação de autorização dos dados e imagens da vitrine.
- O endereço exato e mapa podem ser ocultados; o backend omite esses campos, não apenas a tela.
- Nesta entrega, avisos de ativação e vencimento são preparados para envio manual pelo administrador. O cadastro informa essa finalidade; não há disparos automáticos nem uso para marketing em massa.
- A versão da política é registrada na conta. O antigo consentimento específico de automação é preservado no banco, sem ser marcado automaticamente ou utilizado para enviar mensagens nesta versão.
- A conta pode baixar seus próprios dados em JSON, sem hash de senha.
- Solicitações de acesso, correção, exclusão e revogação ficam registradas com resposta administrativa.
- Ações de ativação, suspensão, consulta detalhada e atendimento administrativo ficam em auditoria.
- APIs financeiras e privadas usam sessão/CSRF; acesso administrativo é controlado no servidor.
- Cookies são necessários à sessão, sem analytics de terceiros nesta versão. Cache offline não contém dados pessoais da API.
- Links para Google Maps, redes e WhatsApp são abertos por ação do usuário; não há iframe de mapa rastreando todos os visitantes.

## Antes da abertura da captação

Preencher no administrativo o nome/identificação do controlador, e-mail de privacidade e contato de suporte. Publicar política e termos revisados que expliquem finalidades, compartilhamento, canal de direitos e retenção. Registrar os papéis contratuais de hospedagem, Google, Asaas, Resend e Twilio, inclusive transferências internacionais quando aplicáveis.

Definir quem recebe solicitações, como comprova identidade, como responde e como registra providências. Não exigir cópia de documento sem necessidade; privilegie a sessão da própria conta e validações proporcionais.

A solicitação de exclusão não apaga automaticamente pagamentos nem relatórios: isso exige análise de retenção. O prazo de resposta e o formato do atendimento devem seguir a obrigação aplicável ao pedido concreto. O sistema registra data e resposta, mas não substitui esse processo operacional.

## Procedimento sugerido para exclusão

1. Confirmar que o pedido veio do titular ou representante autorizado.
2. Suspender a vitrine para retirar dados de consulta pública enquanto o pedido é analisado, quando adequado.
3. Cancelar a renovação no provedor, sem apagar comprovantes que precisem ser mantidos.
4. Identificar dados sujeitos a retenção e a justificativa; separar registros financeiros de divulgação pública.
5. Remover fotos, redes, contatos e conteúdo não mais necessário, e anonimizar o que não precisar de identificação.
6. Avaliar pedidos de exclusão aos operadores e registrar o que foi realizado ou por que algum registro foi preservado.
7. Informar o titular e registrar a resposta no painel. “Registrar atendimento” apenas fecha a solicitação, não executa os passos anteriores.
8. Aplicar a política de expiração de backups e prevenção de reintrodução de dados em restaurações.

## Retenção e segurança

A ausência de pagamento não autoriza retenção indefinida. O pedido do produto de manter o cadastro inativo deve ser conciliado com um prazo de retenção definido e informado. Não foi arbitrado um prazo legal universal no código.

Defina períodos para candidatos que nunca ativaram, contas canceladas, fotos removidas, logs, solicitações e comprovantes. Implemente uma rotina de anonimização/expurgo depois dessa decisão. Remova arquivos órfãos de capa/logo substituídos por rotina controlada antes de expirar backups.

Use acesso mínimo de administradores, senhas fortes, HTTPS, backup protegido, atualização de componentes e monitoramento. Uma evolução recomendada é MFA do administrador, recuperação/verificação de e-mail e moderação/denúncia de avaliações. A dependência AngularJS sem suporte é um risco técnico que precisa ser resolvido antes de exposição ampla.

Para incidentes, documente avaliação, contenção, preservação de evidências, responsáveis e comunicação conforme normas aplicáveis. A aplicação não decide sozinha a necessidade de notificação de incidente.
