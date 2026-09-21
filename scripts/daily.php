<?php
require __DIR__.'/../server/bootstrap.php';
require __DIR__.'/../server/features.php';
prepareNotifications();
query('DELETE FROM rate_limits WHERE reset_at<?',[time()]);
echo "Lembretes preparados. Nenhuma mensagem foi enviada.\n";
