<?php
function runDeliveries(bool $dryRun=false): array {
 $summary=['queued'=>0,'sent'=>0,'failed'=>0,'skipped'=>0];
 if(manualNotifications())return $summary;
 foreach(query("SELECT n.*,b.notification_channel,b.whatsapp_consent FROM notifications n JOIN businesses b ON b.id=n.business_id WHERE n.status='pending' AND b.is_demo=0")->fetchAll()as$n){
  $channels=$n['notification_channel']==='both'?['email','whatsapp']:[$n['notification_channel']];foreach($channels as$channel){if($channel==='whatsapp'&&!$n['whatsapp_consent'])continue;if(!one('SELECT id FROM deliveries WHERE notification_id=? AND channel=?',[$n['id'],$channel])){query('INSERT INTO deliveries(notification_id,channel,updated_at) VALUES(?,?,?)',[$n['id'],$channel,date('Y-m-d H:i:s')]);$summary['queued']++;}}
 }
 // A prior process may have stopped after sending: do not risk duplicate WhatsApp delivery.
 query("UPDATE deliveries SET status='uncertain',last_error='Processo interrompido durante envio; verificar no provedor.' WHERE status='sending' AND updated_at<?",[date('Y-m-d H:i:s',strtotime('-10 minutes'))]);
 $rows=query("SELECT d.*,n.message,n.business_id,n.dedup_key,b.name,b.phone,b.whatsapp_consent,b.notification_channel,b.status business_status,b.paid_until,b.trial_until,b.manual_until,u.email FROM deliveries d JOIN notifications n ON n.id=d.notification_id JOIN businesses b ON b.id=n.business_id JOIN users u ON u.id=b.user_id WHERE d.status IN ('pending','retry') AND (d.next_attempt_at IS NULL OR d.next_attempt_at<=?) AND n.status='pending' AND b.is_demo=0 ORDER BY d.id LIMIT 100",[date('Y-m-d H:i:s')])->fetchAll();
 foreach($rows as$d){
  if(str_starts_with($d['dedup_key'],'paid_')){$payment=one('SELECT status FROM payments WHERE provider_id=?',[substr($d['dedup_key'],5)]);if(!$payment||!in_array($payment['status'],['RECEIVED','CONFIRMED'])){query("UPDATE deliveries SET status='cancelled',updated_at=? WHERE id=?",[date('Y-m-d H:i:s'),$d['id']]);continue;}}

  if($d['business_status']==='suspended'||(str_starts_with($d['dedup_key'],'expiry_')&&$d['dedup_key']!==('expiry_'.$d['business_id'].'_'.validUntil($d).'_'.(validUntil($d)<=date('Y-m-d H:i:s')?'expired':'soon')))||(str_starts_with($d['dedup_key'],'expiry_')&&!str_contains($d['dedup_key'],validUntil($d)))||(str_starts_with($d['dedup_key'],'activation_')&&validUntil($d)>date('Y-m-d H:i:s'))){query("UPDATE deliveries SET status='cancelled',updated_at=? WHERE id=?",[date('Y-m-d H:i:s'),$d['id']]);continue;}
  if(($d['channel']==='whatsapp'&&!$d['whatsapp_consent'])||!in_array($d['notification_channel'],[$d['channel'],'both'])){query("UPDATE deliveries SET status='cancelled',updated_at=? WHERE id=?",[date('Y-m-d H:i:s'),$d['id']]);continue;}
  $configured=$d['channel']==='email'?(env('RESEND_API_KEY')&&env('EMAIL_FROM')):(env('TWILIO_ACCOUNT_SID')&&env('TWILIO_API_KEY')&&env('TWILIO_API_SECRET')&&env('TWILIO_WHATSAPP_FROM')&&env('TWILIO_CONTENT_SID'));
  if($dryRun||env('NOTIFICATIONS_ENABLED','false')!=='true'||!$configured){$summary['skipped']++;continue;}
  $claim=query("UPDATE deliveries SET status='sending',attempts=attempts+1,updated_at=? WHERE id=? AND status IN ('pending','retry') AND EXISTS (SELECT 1 FROM notifications n WHERE n.id=deliveries.notification_id AND n.status='pending')",[date('Y-m-d H:i:s'),$d['id']]);if(!$claim->rowCount())continue;
  try{
   if($d['channel']==='email'){$result=externalJson('https://api.resend.com/emails','POST',['Authorization: Bearer '.env('RESEND_API_KEY'),'Content-Type: application/json','Idempotency-Key: sarzedo-notice-'.$d['id']],json_encode(['from'=>env('EMAIL_FROM'),'to'=>[$d['email']],'subject'=>'Sua vitrine no Sarzedo por perto','text'=>$d['message']."\n\nAviso de serviço referente ao seu cadastro. Acesse seu painel para consultar detalhes e preferências."]));$id=$result['id'];}
   else{$result=externalJson('https://api.twilio.com/2010-04-01/Accounts/'.rawurlencode(env('TWILIO_ACCOUNT_SID')).'/Messages.json','POST',['Authorization: Basic '.base64_encode(env('TWILIO_API_KEY').':'.env('TWILIO_API_SECRET')),'Content-Type: application/x-www-form-urlencoded'],http_build_query(['From'=>env('TWILIO_WHATSAPP_FROM'),'To'=>'whatsapp:+55'.$d['phone'],'ContentSid'=>env('TWILIO_CONTENT_SID'),'ContentVariables'=>json_encode(['1'=>$d['name'],'2'=>preg_replace('/\s+/',' ',$d['message']),'3'=>rtrim(env('APP_URL'),'/').'/#!/painel'])]));$id=$result['sid'];}
   query("UPDATE deliveries SET status='sent',provider_id=?,sent_at=?,updated_at=?,last_error=NULL WHERE id=?",[$id,date('Y-m-d H:i:s'),date('Y-m-d H:i:s'),$d['id']]);$summary['sent']++;
  }catch(Throwable $e){$retry=$d['channel']==='email'&&$d['attempts']<4;$state=$retry?'retry':'uncertain';query('UPDATE deliveries SET status=?,last_error=?,next_attempt_at=?,updated_at=? WHERE id=?',[$state,mb_substr($e->getMessage(),0,255),date('Y-m-d H:i:s',strtotime('+5 minutes')),date('Y-m-d H:i:s'),$d['id']]);$summary['failed']++;}
 }
 return $summary;
}
function reconcilePayments(): void {
 if(env('PAYMENT_DRIVER','demo')!=='asaas')return;
 foreach(query("SELECT * FROM subscriptions WHERE provider_id IS NOT NULL AND provider_id NOT LIKE 'demo_%'")->fetchAll()as$s){
  $offset=0;do{$page=asaas('GET','/subscriptions/'.rawurlencode($s['provider_id']).'/payments?limit=100&offset='.$offset);foreach($page['data']??[]as$p)applyPayment($p);$offset+=100;}while(!empty($page['hasMore']));
 }
}
