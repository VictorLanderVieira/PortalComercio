<?php
require __DIR__.'/../server/bootstrap.php';
if(is_file(ROOT.'/storage/maintenance.flag')){echo "Publicação em andamento; worker adiado.\n";exit;}
require __DIR__.'/../server/features.php';
require __DIR__.'/../server/payments.php';
require __DIR__.'/../server/ad-orders.php';
require __DIR__.'/../server/google.php';
require __DIR__.'/../server/notifications.php';
$lock=fopen(ROOT.'/storage/worker.lock','c');if(!flock($lock,LOCK_EX|LOCK_NB)){echo "Worker já em execução.\n";exit;}
try{if(!in_array('--dry-run',$argv,true)){reconcilePayments();expirePendingAdOrders();prepareManualRenewals();}prepareNotifications();$result=runDeliveries(in_array('--dry-run',$argv,true));query('DELETE FROM rate_limits WHERE reset_at<?',[time()]);echo json_encode($result,JSON_UNESCAPED_UNICODE)."\n";}catch(Throwable $e){error_log($e->getMessage());exit(1);}finally{flock($lock,LOCK_UN);fclose($lock);}
