# Atualizar o portal na Hostinger

## Esta versão

- Todos os negócios precisam de aprovação administrativa para aparecer na busca, no perfil público, nas promoções e na publicidade avulsa.
- A migração 11 coloca também os cadastros existentes em análise. Nenhum dado, imagem, plano ou pagamento é apagado.
- No administrativo, filtre **Revisão do cadastro → Aguardando aprovação**, abra **Ver cadastro**, confira o conteúdo, preencha **Motivo (obrigatório)** e clique em **Aprovar cadastro**. **Reprovar / retirar aprovação** volta a ocultar o negócio. Suspensão e vencimento continuam valendo.
- Pagamento/ativação e aprovação são controles separados. Aprovar não renova o prazo nem confirma pagamento. A aprovação inicial não constitui moderação individual de futuras alterações de conteúdo; o administrador pode retirar a aprovação ao identificar irregularidades.
- Categorias: Transporte, Mecânica, Studio de Tatuagem e Foto e filmagem.
- Plano pago vencido: exibição reduzida a Free por 5 dias a partir do fim da validade. Após esse prazo, fica suspenso por vencimento, sem busca, perfil público, promoções ou publicidade. A verificação ocorre nas consultas, sem depender de agendador. Renovação restaura a exibição se o cadastro estiver aprovado e sem suspensão manual. O Free contratado originalmente mantém seus 30 dias.
- Publicidade extra: até dois anúncios por vez, troca automática a cada 10 segundos, retornando ao início. Os controles manuais continuam disponíveis. Com um número ímpar, a última página tem um anúncio; o banner “Divulgue aqui” permanece separado.

## 1. Gerar pacote local

Abra o PowerShell na pasta do projeto:

```powershell
Set-Location C:\Pessoal\PortalComercio
npm run check:php
npm test
npm run release
```

O pacote exclui banco local, senhas, chave SSH e fotos dos clientes. Nunca rode `npm run setup` na produção: ele é destinado a demonstração local.

## 2. Enviar o pacote e seu checksum

A chave de publicação e a identificação do servidor já estão em `storage/deploy`. Use esta mesma máquina. Nunca envie a chave privada para o GitHub ou para a pasta pública.

```powershell
& "$env:WINDIR\System32\OpenSSH\scp.exe" -o StrictHostKeyChecking=yes -o UserKnownHostsFile=storage/deploy/known_hosts -i storage/deploy/hostinger_ed25519 -P 65002 dist/portal-release.tar.gz dist/portal-release.tar.gz.sha256 u211282174@147.93.38.156:domains/guiasarzedo.com.br/portal/incoming/
```

Só prossiga se o envio terminar sem erros. Se a identidade do servidor mudar, confira com a Hostinger antes de atualizar o arquivo de hosts conhecidos.

## 3. Aplicar a atualização

```powershell
& "$env:WINDIR\System32\OpenSSH\ssh.exe" -o StrictHostKeyChecking=yes -o UserKnownHostsFile=storage/deploy/known_hosts -i storage/deploy/hostinger_ed25519 -p 65002 u211282174@147.93.38.156 "cd /home/u211282174/domains/guiasarzedo.com.br/portal && bash deploy.sh update production"
```

O instalador confere a integridade do pacote, valida a conexão, gera backup do banco e configurações, aplica as migrações e muda a versão atual. Durante a atualização, a API fica brevemente em manutenção. Banco, uploads, configuração Pix e demais configurações privadas são preservados. Não substitua `shared/.env`.

O banco local `storage/portal.sqlite` e o MySQL de produção nunca são apagados ou recriados. A publicação também bloqueia automaticamente migrações com comandos destrutivos. Consulte [SEGURANCA-DOS-BANCOS.md](SEGURANCA-DOS-BANCOS.md).

Espere a mensagem **Versão publicada. Banco, uploads e configurações preservados.** Se houver erro, não repita mudanças manuais no banco: guarde a mensagem para diagnóstico. Backups ficam em `portal/backups`; não há reversão automática de migrações.

## 4. Conferir

Abra https://guiasarzedo.com.br/ (ou a URL temporária enquanto o DNS estiver em transição), atualize a página e entre no administrativo. Revise os cadastros pendentes antes de liberá-los. Confira as novas categorias e, se houver mais de dois anúncios ativos e aprovados, aguarde dez segundos para ver o rodízio.

O pacote desta alteração está preparado localmente. Publicá-lo exige executar os passos 2 e 3 acima; gerar o pacote não modifica o servidor.
