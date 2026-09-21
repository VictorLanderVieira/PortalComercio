# Publicidade avulsa e gestão financeira

O banner lateral é contratado separadamente da licença. Valor inicial sugerido: **R$ 35,00 por mês**. Esse valor é uma proposta de lançamento, não uma média de mercado nem promessa de retorno. Pode ser alterado em Administração → Campanha de lançamento e atendimento → Publicidade extra mensal. Alterações valem para novas negociações; recebimentos anteriores preservam seus valores.

## Operação
1. O negócio precisa estar cadastrado e informar seu código, como SZ-000123. O código é derivado do ID permanente do cadastro e também aparece na mensagem de comprovante da licença.
2. O responsável envia o material e o comprovante pelo WhatsApp **(31) 98767-1102**. O botão “Quero divulgar aqui” prepara a mensagem; ninguém envia mensagens automaticamente.
3. Administração → Banners dos negócios → Nova publicidade. Informe o código, título, descrição, preço opcional da oferta e o valor mensal combinado. Salve o rascunho.
4. Envie a imagem **900 × 600 px (3:2)** e, opcionalmente, a logo **400 × 400 px**. JPG, PNG ou WebP, até **5 MB** cada, máximo 20 megapixels. O servidor valida e converte os arquivos, ajustando sem cortar o conteúdo. Logos e imagens desse serviço não consomem a galeria da licença.
5. Confira o dinheiro no extrato. Registre valor exato, data e identificador Pix e confirme o recebimento. A publicidade fica visível por **um mês civil a partir da ativação**, com ajuste para o último dia de meses menores.
6. É possível editar conteúdo, pausar e retomar dentro da validade. A pausa não prolonga o mês contratado. Após vencer, preparar novo mês gera outro rascunho e exige nova confirmação, preservando o histórico.

Não há renovação automática. O serviço extra não ativa nem altera a licença da empresa. Uma suspensão administrativa impede a exibição. O vencimento da licença mantém suas próprias regras de Free; a publicidade contratada tem validade independente. Contatos vêm do cadastro, e o Maps respeita a opção de publicar o endereço. A vitrine só recebe link quando está disponível na busca.

O banner padrão “Divulgue aqui” identifica um espaço disponível, sem simular um anunciante real. No computador, a coluna mostra até duas publicidades por página e permite navegar pelas demais. No celular, a área fica após a busca.

## Financeiro
- Recebimentos de licenças e publicidade são separados e somados no total geral e no gráfico mensal.
- O gráfico por origem discrimina Free, Essencial, Destaque, Premium e publicidade extra, com seleção de mês ou acumulado.
- O plano de cada recebimento vem da assinatura da cobrança, e não do plano atual ou de interesse do negócio.
- Publicidade não entra na previsão de licenças nem nas mensalidades vigentes. Rascunhos e exemplos não contam como dinheiro recebido. Pausar uma publicidade não é um estorno.
- Listas administrativas usam 25 registros por página. Históricos, recebimentos avulsos, avisos e solicitações são paginados no servidor.

## Datas e números
Datas na interface usam DD/MM/AAAA; quando necessário, DD/MM/AAAA HH:mm:ss. O formulário converte para o formato interno do banco e valida datas reais. Preços e quantidades têm campos numéricos e validação no servidor; identificadores de Pix e códigos SZ são identificadores alfanuméricos, não valores financeiros.

## Atualização
Aplicar scripts/migrate.php (migração 10) em cada base. Não executar seed ou demo em bancos com cadastros reais. O banco local foi atualizado com backup prévio em storage/backups. O pacote de publicação exclui bancos locais, backups, dados de teste, credenciais e arquivos enviados pelos usuários.

