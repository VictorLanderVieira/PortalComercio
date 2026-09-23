# Mapa dentro do portal

O botão **Ver localização** abre um modal sem sair da busca ou do perfil. O mapa interativo usa a [Google Maps Embed API](https://developers.google.com/maps/documentation/embed/quickstart). Enquanto a chave não estiver configurada, o modal mostra o endereço informado pela empresa e um botão para abrir o link original do Google Maps.

## Configurar a chave

1. Entre no [Google Cloud Console](https://console.cloud.google.com/) com sua conta e crie ou selecione um projeto para o Guia Sarzedo.
2. Associe uma conta de faturamento. Segundo a [tabela oficial](https://developers.google.com/maps/documentation/embed/usage-and-billing), o uso da Maps Embed API está disponível sem cobrança, mas a chave e a conta de faturamento são exigidas.
3. Em **APIs e serviços → Biblioteca**, procure **Maps Embed API** e clique em **Ativar**.
4. Em **APIs e serviços → Credenciais**, crie uma **chave de API** específica para o mapa do portal.
5. Edite a chave. Em **Restrições de aplicativo**, selecione **Sites** e autorize `https://guiasarzedo.com.br/*` e `https://www.guiasarzedo.com.br/*`. Em **Restrições de API**, permita somente **Maps Embed API**. Salve.
6. Entre como administrador em `https://guiasarzedo.com.br/#!/admin`. Em **Campanha de lançamento e atendimento**, cole a chave no campo **Chave da Google Maps Embed API** e clique em **Salvar configurações**.
7. Abra um negócio que tenha localização publicada e toque em **Ver localização**. Confira o pin e o endereço. Caso a busca mostre outro local, peça à empresa para conferir nome, endereço e link cadastrados.

A chave é enviada ao navegador no endereço do iframe, portanto **não é uma senha privada**. As restrições de site e de API são essenciais para limitar o uso. Crie uma chave separada da usada para outros serviços Google. A chave do login Google é outra configuração e não substitui a da Maps Embed API.
