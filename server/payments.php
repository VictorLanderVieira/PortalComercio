<?php
require_once __DIR__.'/manual-pix.php';
class AsaasUnavailableException extends RuntimeException {}
function asaasConfigured(): bool {
 return env('PAYMENT_DRIVER')==='asaas' && env('ASAAS_API_KEY')!=='' && strlen(env('ASAAS_WEBHOOK_TOKEN'))>=32 && in_array(env('ASAAS_ENV','sandbox'),['sandbox','production'],true);
}
function asaasSettings(): array {
 return ['enabled'=>env('PAYMENT_DRIVER')==='asaas','api_key_configured'=>env('ASAAS_API_KEY')!=='','webhook_token_configured'=>strlen(env('ASAAS_WEBHOOK_TOKEN'))>=32,'environment'=>env('ASAAS_ENV','production'),'webhook_url'=>rtrim(env('APP_URL','https://guiasarzedo.com.br'),'/').'/api/webhook'];
}
function saveAsaasSettings(array $d,int $admin): void {
 $bucket='asaas_admin_'.$admin.'_'.(int)floor(time()/600);
 $blocked=transaction(function()use($bucket){$r=one('SELECT attempts FROM rate_limits WHERE bucket=?',[$bucket]);if($r&&$r['attempts']>=5)return true;if($r)query('UPDATE rate_limits SET attempts=attempts+1 WHERE bucket=?',[$bucket]);else query('INSERT INTO rate_limits VALUES(?,?,?)',[$bucket,1,time()+600]);return false;});
 if($blocked)fail('Muitas tentativas. Aguarde dez minutos para alterar o recebimento Asaas.',429);
 $account=one('SELECT password_hash,role FROM users WHERE id=?',[$admin]);
 if(!$account||$account['role']!=='admin'||!password_verify((string)($d['admin_password']??''),$account['password_hash']))fail('Informe sua senha de administrador para alterar o recebimento Asaas.',403);
 query('DELETE FROM rate_limits WHERE bucket=?',[$bucket]);
 $apiKey=field($d,'api_key',0,512);$token=field($d,'webhook_token',0,255);$enabled=!empty($d['enabled']);
 if($enabled&&empty($d['webhook_ready']))fail('Confirme que o webhook já está ativo no Asaas com a URL e o token corretos.');
 if($apiKey!==''&&(strlen($apiKey)<20||preg_match('/\s|[\x00-\x1f\x7f]/',$apiKey)))fail('Informe uma chave de API Asaas válida, sem espaços.');
 if($token!==''&&(strlen($token)<32||preg_match('/\s|[\x00-\x1f\x7f]/',$token)||hash_equals($apiKey,$token)))fail('O token do webhook deve ter de 32 a 255 caracteres, sem espaços, e ser diferente da chave de API.');
 if($enabled&&env('DEPLOY_ENV')==='staging')fail('Cobranças reais permanecem desabilitadas na homologação.',403);
 if(!$enabled&&env('PAYMENT_DRIVER')==='asaas'&&one("SELECT id FROM subscriptions WHERE status IN ('creating','pending','active') AND provider_id NOT LIKE 'manual_%' AND provider_id NOT LIKE 'demo_%' LIMIT 1"))fail('Existem assinaturas Asaas em andamento. Cancele e concilie as renovações antes de desativar.',409);
 if(!$enabled&&env('PAYMENT_DRIVER')==='asaas'&&one("SELECT id FROM ad_orders WHERE provider='asaas' AND status IN ('creating','pending','confirmed','overdue') LIMIT 1"))fail('Existem publicidades aguardando Pix no Asaas. Concilie esses pedidos antes de desativar.',409);
 $file=integrationConfigPath();$lock=fopen($file.'.lock','c');if(!$lock||!flock($lock,LOCK_EX))fail('Não foi possível bloquear a configuração.',500);
 try{$config=integrationConfig();if($apiKey!=='')$config['ASAAS_API_KEY']=$apiKey;if($token!=='')$config['ASAAS_WEBHOOK_TOKEN']=$token;
  $effectiveKey=$config['ASAAS_API_KEY']??env('ASAAS_API_KEY');$effectiveToken=$config['ASAAS_WEBHOOK_TOKEN']??env('ASAAS_WEBHOOK_TOKEN');
  if($effectiveKey!==''&&$effectiveToken!==''&&hash_equals($effectiveKey,$effectiveToken))fail('Use segredos distintos para API e webhook.');
  if($enabled&&($effectiveKey===''||strlen($effectiveToken)<32))fail('Cadastre a chave de API e o token do webhook antes de habilitar o Asaas.');
  $config['ASAAS_ENV']=env('APP_ENV','local')==='production'?'production':'sandbox';
  $currentDriver=env('PAYMENT_DRIVER','demo');
  $config['PAYMENT_DRIVER']=$enabled?'asaas':($currentDriver==='asaas'?((env('PIX_KEY')&&env('PIX_RECIPIENT')&&env('PIX_CITY'))?'manual_pix':(env('APP_ENV','local')==='local'?'demo':'disabled')):$currentDriver);
  $tmp=tempnam(dirname($file),'asaas-');if($tmp===false)fail('Não foi possível salvar a configuração.',500);chmod($tmp,0600);
  if(file_put_contents($tmp,json_encode($config,JSON_THROW_ON_ERROR),LOCK_EX)===false||!rename($tmp,$file)){@unlink($tmp);fail('Não foi possível salvar a configuração.',500);}chmod($file,0600);
  audit($admin,null,'asaas_settings_updated','Configuração Asaas atualizada; credenciais omitidas; modo '.($enabled?'ativo':'inativo').'.');
 }finally{flock($lock,LOCK_UN);fclose($lock);}
}
function asaas(string $method,string $path,?array $data=null): array {
 if(!env('ASAAS_API_KEY'))fail('O recebimento por Pix ainda não foi configurado pelo administrador.',503);
 $base=env('ASAAS_ENV','sandbox')==='production'?'https://api.asaas.com/v3':'https://api-sandbox.asaas.com/v3';
 $ch=curl_init($base.$path);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>65,CURLOPT_HTTPHEADER=>['Content-Type: application/json','User-Agent: SarzedoPortal/1.0','access_token: '.env('ASAAS_API_KEY')]]);
 if($data!==null)curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($data));
 $raw=curl_exec($ch);$status=curl_getinfo($ch,CURLINFO_HTTP_CODE);$error=curl_error($ch);curl_close($ch);
 if($raw===false || $status<200 || $status>=300)throw new AsaasUnavailableException('Falha Asaas HTTP '.$status.' '.$error);
 $result=json_decode($raw,true);if(!is_array($result))throw new RuntimeException('Resposta inválida do provedor');return $result;
}
function checkout(array $b,array $d): array {
 $driver=env('PAYMENT_DRIVER','demo');if($driver==='demo' && env('APP_ENV','local')!=='local')fail('Pagamentos reais não configurados.',503);
 if(!in_array($driver,['demo','asaas','manual_pix']))fail('Provedor indisponível.',503);
 $plan=one('SELECT * FROM plans WHERE id=?',[(int)($d['plan_id']??$b['plan_id'])]);if(!$plan)fail('Plano inválido.');if(isFreePlanId($plan['id']))fail('O plano Free não precisa de cobrança Pix.');
 $existing=one("SELECT * FROM subscriptions WHERE business_id=? AND status IN ('creating','pending','active') ORDER BY id DESC",[$b['id']]);
 if($existing){
  if($existing['status']==='creating')fail('Cobrança em processamento. O administrador precisa conciliar a solicitação antes de uma nova tentativa.',409);
  if($existing['plan_id']!=$plan['id'])fail('Cancele a renovação atual antes de escolher outro plano.',409);
  $existingManual=str_starts_with($existing['provider_id']??'','manual_');
  $existingDemo=str_starts_with($existing['provider_id']??'','demo_');
  if(($existingManual&&!in_array($driver,['manual_pix','asaas'],true))||($existingDemo&&$driver!=='demo')||(!$existingManual&&!$existingDemo&&$driver!=='asaas'))fail('Cancele a renovação anterior para contratar no novo meio de pagamento.',409);
  // Existing manual subscriptions remain manual; only new signups use Asaas after the switch.
  return invoice($existing);
 }
 if($driver==='asaas'&&!asaasConfigured())fail('Configure a chave Asaas, o token do webhook e o ambiente antes de emitir cobranças.',503);
 $plan=pricedPlan($plan);if(isset($d['expected_amount_cents'])&&(int)$d['expected_amount_cents']!==$plan['price_cents'])fail('O preço do plano mudou. Atualize a página e confira o valor antes de continuar.',409);
 if($driver==='manual_pix'&&!manualPixSettings()['configured'])fail('Recebimento Pix não configurado.',503);
 $tax=preg_replace('/\D/','',(string)($d['cpfCnpj']??''));
 if($driver==='asaas' && !validDocument($tax))fail('Informe um CPF ou CNPJ válido para emitir a cobrança.');
 $photoCount=one('SELECT COUNT(*) n FROM photos WHERE business_id=?',[$b['id']])['n'];if($photoCount>$plan['photo_limit'])fail('Remova fotos excedentes antes de contratar este plano.');
 $id=transaction(function()use($b,$plan){query('UPDATE plans SET id=id WHERE id=?',[$plan['id']]);$current=pricedPlan(one('SELECT * FROM plans WHERE id=?',[$plan['id']]));if($current['price_cents']!==$plan['price_cents'])fail('O preço mudou. Atualize a página.',409);lockBusiness((int)$b['id']);if(one("SELECT id FROM subscriptions WHERE business_id=? AND status IN ('creating','pending','active')",[$b['id']]))fail('Já existe uma solicitação. Atualize a página.',409);query("INSERT INTO subscriptions(business_id,plan_id,billing_type,status,created_at,amount_cents) VALUES(?,?,'PIX','creating',?,?)",[$b['id'],$plan['id'],date('Y-m-d H:i:s'),$plan['price_cents']]);return (int)db()->lastInsertId();});
 $due=(validUntil($b)>date('Y-m-d H:i:s'))?substr(validUntil($b),0,10):date('Y-m-d');
 if($driver==='manual_pix'){query("UPDATE subscriptions SET provider_id=?,status='pending' WHERE id=?",['manual_sub_'.$id,$id]);return manualInvoice(one('SELECT * FROM subscriptions WHERE id=?',[$id]));}
 if($driver==='demo'){
  query("UPDATE subscriptions SET provider_id=?,status='pending' WHERE id=?",['demo_sub_'.$id,$id]);
  query("INSERT INTO payments(subscription_id,provider_id,amount_cents,status,due_date) VALUES(?,?,?,'PENDING',?)",[$id,'demo_pay_'.$id,$plan['price_cents'],$due]);
 }else{
  $u=user();$customer=$b['provider_customer_id'];
  if(!$customer){$remote=asaas('POST','/customers',['name'=>$u['name'],'email'=>$u['email'],'cpfCnpj'=>$tax,'externalReference'=>'business_'.$b['id']]);$customer=$remote['id'];query('UPDATE businesses SET provider_customer_id=? WHERE id=?',[$customer,$b['id']]);}
  $remote=asaas('POST','/subscriptions',['customer'=>$customer,'billingType'=>'PIX','value'=>$plan['price_cents']/100,'nextDueDate'=>$due,'cycle'=>'MONTHLY','description'=>'Sarzedo por perto · Plano '.$plan['name'],'externalReference'=>'subscription_'.$id]);
  query("UPDATE subscriptions SET provider_id=?,status='pending' WHERE id=?",[$remote['id'],$id]);
 }
 return invoice(one('SELECT * FROM subscriptions WHERE id=?',[$id]));
}
function validDocument(string $value): bool {
 if(preg_match('/^(\d)\1+$/',$value))return false;
 if(strlen($value)===11){for($len=9;$len<11;$len++){ $sum=0;for($i=0;$i<$len;$i++)$sum+=(int)$value[$i]*($len+1-$i);$digit=($sum*10)%11;if($digit===10)$digit=0;if($digit!==(int)$value[$len])return false;}return true;}
 if(strlen($value)===14){foreach([[5,4,3,2,9,8,7,6,5,4,3,2],[6,5,4,3,2,9,8,7,6,5,4,3,2]] as $weights){$sum=0;foreach($weights as $i=>$w)$sum+=(int)$value[$i]*$w;$r=$sum%11;$digit=$r<2?0:11-$r;if($digit!==(int)$value[count($weights)])return false;}return true;}return false;
}
function invoice(array $s): array {
 if(str_starts_with($s['provider_id']??'','manual_'))return manualInvoice($s);
 if(env('PAYMENT_DRIVER','demo')==='demo')return ['demo'=>true,'message'=>'Ambiente de demonstração. Nenhuma cobrança real será feita.','payment'=>one('SELECT * FROM payments WHERE subscription_id=? ORDER BY id DESC',[$s['id']])];
 $result=asaas('GET','/subscriptions/'.rawurlencode($s['provider_id']).'/payments?limit=100');
 $pending=array_values(array_filter($result['data']??[],fn($p)=>in_array($p['status'],['PENDING','OVERDUE'])));
 usort($pending,fn($a,$b)=>strcmp($a['dueDate'],$b['dueDate']));
 if(!$pending)return ['demo'=>false,'message'=>'Nenhuma cobrança pendente. Se acabou de gerar, aguarde alguns instantes e consulte novamente.'];
 $p=$pending[0];$plan=one('SELECT * FROM plans WHERE id=?',[$s['plan_id']]);
 if(!one('SELECT id FROM payments WHERE provider_id=?',[$p['id']]))query('INSERT INTO payments(subscription_id,provider_id,amount_cents,status,due_date,invoice_url) VALUES(?,?,?,?,?,?)',[$s['id'],$p['id'],(int)round($p['value']*100),$p['status'],$p['dueDate'],$p['invoiceUrl']??null]);
 $qr=asaas('GET','/payments/'.rawurlencode($p['id']).'/pixQrCode');
 return ['demo'=>false,'paymentId'=>$p['id'],'invoiceUrl'=>$p['invoiceUrl']??null,'encodedImage'=>$qr['encodedImage']??null,'payload'=>$qr['payload']??null,'expirationDate'=>$qr['expirationDate']??null,'amount'=>$p['value'],'message'=>'Pague o Pix para ativar seu anúncio. A confirmação pode levar alguns instantes.'];
}
function applyPayment(array $p,bool $insideTransaction=false): void {
 $operation=function()use($p){
  $payment=one('SELECT * FROM payments WHERE provider_id=?',[$p['id']]);
  $s=$payment?one('SELECT * FROM subscriptions WHERE id=?',[$payment['subscription_id']]):one('SELECT * FROM subscriptions WHERE provider_id=?',[$p['subscription']??'']);
  if(!$s)return;
  lockBusiness((int)$s['business_id']);
  $plan=one('SELECT * FROM plans WHERE id=?',[$s['plan_id']]);
  if(isset($p['subscription']) && $p['subscription']!==$s['provider_id'])throw new RuntimeException('Assinatura divergente');
  $expected=(int)($s['amount_cents']??$payment['amount_cents']??$plan['price_cents']);if((int)round(($p['value']??0)*100)!==$expected)throw new RuntimeException('Valor divergente');
  if($s['amount_cents']===null)query('UPDATE subscriptions SET amount_cents=? WHERE id=?',[$expected,$s['id']]);
  // A conta PF pode receber CONFIRMED temporário durante bloqueio cautelar.
  $status=$p['status'];$paid=$status==='RECEIVED';
  $valid=$payment['valid_until']??null;$paidAt=$payment['paid_at']??null;
  if($paid){$date=max($p['dueDate'],$p['paymentDate']??$p['clientPaymentDate']??date('Y-m-d'));$paidAt=$p['paymentDate']??date('Y-m-d');$valid=$payment['valid_until']??nextMonth($date.' 23:59:59');}
  if($payment)query('UPDATE payments SET status=?,paid_at=?,valid_until=? WHERE id=?',[$status,$paidAt,$valid,$payment['id']]);
  else query('INSERT INTO payments(subscription_id,provider_id,amount_cents,status,due_date,paid_at,valid_until,invoice_url) VALUES(?,?,?,?,?,?,?,?)',[$s['id'],$p['id'],$expected,$status,$p['dueDate'],$paidAt,$valid,$p['invoiceUrl']??null]);
  if($paid && function_exists('queueNotice')) { $business=one('SELECT * FROM businesses WHERE id=?',[$s['business_id']]);queueNotice($business,'paid_'.$p['id'],'Olá! O Pix da vitrine de '.$business['name'].' foi confirmado. Acesse seu painel no Sarzedo por perto para acompanhar a ativação.'); }
  if($paid && $s['status']!=='cancelled')query("UPDATE subscriptions SET status='active' WHERE id=?",[$s['id']]);
  if(in_array($status,['REFUNDED','CHARGEBACK_REQUESTED','CHARGEBACK_DISPUTE']))query('UPDATE payments SET refunded_at=COALESCE(refunded_at,?) WHERE provider_id=?',[date('Y-m-d H:i:s'),$p['id']]);
  $latest=one("SELECT p.valid_until,s.plan_id FROM payments p JOIN subscriptions s ON s.id=p.subscription_id WHERE s.business_id=? AND p.status='RECEIVED' ORDER BY p.valid_until DESC LIMIT 1",[$s['business_id']]);
  if($latest)query("UPDATE businesses SET status=CASE WHEN status='suspended' THEN 'suspended' ELSE 'active' END,paid_until=?,plan_id=? WHERE id=?",[$latest['valid_until'],$latest['plan_id'],$s['business_id']]);
  else query("UPDATE businesses SET status=CASE WHEN status='suspended' THEN 'suspended' ELSE 'pending' END,paid_until=NULL WHERE id=?",[$s['business_id']]);
 };if($insideTransaction)$operation();else transaction($operation);
}
function handleWebhook(): never {
 $secret=env('ASAAS_WEBHOOK_TOKEN');if(env('ASAAS_API_KEY')==='' || strlen($secret)<32 || !hash_equals($secret,$_SERVER['HTTP_ASAAS_ACCESS_TOKEN']??''))fail('Não autorizado.',401);
 $d=input();$id=field($d,'id',1,150);$event=field($d,'event',1,80);
 if(one('SELECT id FROM webhook_events WHERE id=?',[$id]))jsonResponse(['ok'=>true,'duplicate'=>true]);
 if(str_starts_with($event,'PAYMENT_') && !empty($d['payment']['id'])) {
  // Retrieve authoritative current state: delayed/out-of-order events cannot reactivate a refund.
  $p=asaas('GET','/payments/'.rawurlencode($d['payment']['id']));
  if(str_starts_with((string)($p['externalReference']??''),'ad_order_'))applyAdOrderPayment($p);
  else applyPayment($p);
 }
 // Entitlements are idempotent per payment; a retry between these commits is safe.
 try{query('INSERT INTO webhook_events VALUES(?,?,?)',[$id,$event,date('Y-m-d H:i:s')]);}catch(PDOException $e){if(!one('SELECT id FROM webhook_events WHERE id=?',[$id]))throw $e;}
 jsonResponse(['ok'=>true]);
}
