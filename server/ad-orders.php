<?php
function adOrderAmount(): int { return (int)(settings()['advertising_price_cents']??3500); }
function adOrderWhatsapp(array $order,array $business): string {
 $amount=number_format((int)$order['amount_cents']/100,2,',','.');
 $message='Olá! Já paguei a publicidade avulsa de R$ '.$amount.' no Guia Sarzedo. Pedido #'.$order['id'].', negócio '.businessCode((int)$business['id']).' — '.$business['name'].'. Vou enviar o banner (900 × 600 px) ou a imagem, descrição e preço da oferta para você publicar.';
 return 'https://wa.me/5531987671102?text='.rawurlencode($message);
}
function adOrderView(array $order,array $business): array {
 $result=['id'=>(int)$order['id'],'business_code'=>businessCode((int)$business['id']),'amount_cents'=>(int)$order['amount_cents'],'status'=>$order['status'],'provider'=>$order['provider'],'due_date'=>$order['due_date'],'expires_at'=>$order['expires_at'],'expires_in_seconds'=>max(0,strtotime($order['expires_at'])-time()),'paid_at'=>$order['paid_at'],'linked'=>one('SELECT id FROM advertisements WHERE order_id=?',[$order['id']])!==null,'image_url'=>$order['image_url'],'logo_url'=>$order['logo_url']];
 if($order['status']==='paid')$result['whatsapp_url']=adOrderWhatsapp($order,$business);
 elseif(in_array($order['status'],['pending','confirmed','overdue'],true)){
  if($order['provider']==='manual'){$result['payload']=$order['payload'];$result['recipient']=env('PIX_RECIPIENT');}
  elseif($order['provider']==='asaas'&&!empty($order['provider_id'])){try{$qr=asaas('GET','/payments/'.rawurlencode($order['provider_id']).'/pixQrCode');$result['payload']=$qr['payload']??null;$result['encodedImage']=$qr['encodedImage']??null;}catch(Throwable $e){error_log('QR publicidade #'.$order['id'].': '.$e->getMessage());}}
 }
 return $result;
}
function applyAdOrderPayment(array $payment): void {
 $ref=(string)($payment['externalReference']??'');if(!preg_match('/^ad_order_([1-9][0-9]*)$/',$ref,$match))return;
 transaction(function()use($payment,$match,$ref){
  $order=one('SELECT * FROM ad_orders WHERE id=?',[(int)$match[1]]);if(!$order||$order['provider']!=='asaas')return;
  lockBusiness((int)$order['business_id']);$order=one('SELECT * FROM ad_orders WHERE id=?',[$order['id']]);
  if($ref!=='ad_order_'.$order['id']||($order['provider_id']&&$order['provider_id']!==($payment['id']??'')))throw new RuntimeException('Referência de publicidade divergente');
  if((int)round(((float)($payment['value']??0))*100)!==(int)$order['amount_cents'])throw new RuntimeException('Valor da publicidade divergente');
  $status=(string)($payment['status']??'');$paid=$status==='RECEIVED';$refunded=in_array($status,['REFUNDED','CHARGEBACK_REQUESTED','CHARGEBACK_DISPUTE'],true);
  $local=$paid?'paid':($refunded?'refunded':($status==='DELETED'?'expired':($status==='CONFIRMED'?'confirmed':($status==='OVERDUE'?'overdue':'pending'))));
  $paidAt=$paid?($order['paid_at']??(($payment['paymentDate']??date('Y-m-d')).' 12:00:00')):$order['paid_at'];
  query('UPDATE ad_orders SET provider_id=?,status=?,paid_at=?,refunded_at=? WHERE id=?',[$payment['id'],$local,$paidAt,$refunded?date('Y-m-d H:i:s'):$order['refunded_at'],$order['id']]);
  if($refunded)query("UPDATE advertisements SET status='paused' WHERE order_id=? AND status='active'",[$order['id']]);
 });
}
function reconcileAdOrder(array $order): array {
 if($order['provider']!=='asaas'||$order['status']==='paid'||$order['status']==='refunded'||$order['status']==='expired')return $order;
 if(!$order['provider_id']){
  $result=asaas('GET','/payments?externalReference='.rawurlencode('ad_order_'.$order['id']).'&limit=10');
  $found=array_values(array_filter($result['data']??[],fn($p)=>($p['externalReference']??'')==='ad_order_'.$order['id']));
  if(count($found)>1)fail('Mais de uma cobrança encontrada. O administrador precisa conciliar este pedido.',409);
  if($found)applyAdOrderPayment($found[0]);
 }else applyAdOrderPayment(asaas('GET','/payments/'.rawurlencode($order['provider_id'])));
 return one('SELECT * FROM ad_orders WHERE id=?',[$order['id']]);
}
function expireAdOrder(array $order): array {
 if($order['expires_at']>date('Y-m-d H:i:s')||!in_array($order['status'],['creating','pending','overdue'],true))return $order;
 if($order['provider']==='asaas'){
  $order=reconcileAdOrder($order);if(!in_array($order['status'],['creating','pending','overdue'],true))return $order;
  if(!$order['provider_id']){query("UPDATE ad_orders SET status='expired' WHERE id=? AND status='creating'",[$order['id']]);return one('SELECT * FROM ad_orders WHERE id=?',[$order['id']]);}
  asaas('DELETE','/payments/'.rawurlencode($order['provider_id']));
 }
 query("UPDATE ad_orders SET status='expired' WHERE id=? AND status IN ('creating','pending','overdue')",[$order['id']]);
 return one('SELECT * FROM ad_orders WHERE id=?',[$order['id']]);
}
function expirePendingAdOrders(): void {foreach(query("SELECT * FROM ad_orders WHERE expires_at<=? AND status IN ('creating','pending','overdue') ORDER BY id LIMIT 50",[date('Y-m-d H:i:s')])->fetchAll() as $order)try{expireAdOrder($order);}catch(Throwable $e){error_log('Expiração publicidade #'.$order['id'].': '.$e->getMessage());}}
function createAdOrder(array $business,array $d): array {
 if($business['status']==='suspended'||!isListed($business))fail('Ative e aprove o cadastro do negócio antes de contratar publicidade.',409);
 $driver=env('PAYMENT_DRIVER','disabled');if(!in_array($driver,['asaas','demo'],true)||($driver==='demo'&&env('APP_ENV','local')!=='local'))fail('A publicidade automática estará disponível após configurar o Asaas.',503);
 if($driver==='asaas'&&!asaasConfigured())fail('O Asaas ainda não está pronto para cobrar.',503);
 $amount=adOrderAmount();if($amount<100)fail('Valor da publicidade inválido.',503);
 $existing=transaction(function()use($business){lockBusiness((int)$business['id']);return one("SELECT o.* FROM ad_orders o LEFT JOIN advertisements a ON a.order_id=o.id WHERE o.business_id=? AND a.id IS NULL AND o.status IN ('creating','pending','confirmed','overdue','paid') ORDER BY o.id DESC LIMIT 1",[$business['id']]);});
 if($existing){$existing=expireAdOrder($existing);if($existing['status']!=='expired')return adOrderView(reconcileAdOrder($existing),$business);}
 $provider=$driver;$due=date('Y-m-d');$expires=date('Y-m-d H:i:s',strtotime('+30 minutes'));
 if($provider==='asaas'&&!$business['provider_customer_id']){$tax=preg_replace('/\D/','',(string)($d['cpfCnpj']??''));if(!validDocument($tax))fail('Informe um CPF ou CNPJ válido para emitir o Pix da publicidade.');}
 $id=transaction(function()use($business,$amount,$provider,$due,$expires){lockBusiness((int)$business['id']);if(one("SELECT o.id FROM ad_orders o LEFT JOIN advertisements a ON a.order_id=o.id WHERE o.business_id=? AND a.id IS NULL AND o.status IN ('creating','pending','confirmed','overdue','paid') LIMIT 1",[$business['id']]))fail('Já existe um pedido de publicidade. Atualize a página.',409);query("INSERT INTO ad_orders(business_id,amount_cents,provider,status,due_date,expires_at,created_at) VALUES(?,?,?,'creating',?,?,?)",[$business['id'],$amount,$provider,$due,$expires,date('Y-m-d H:i:s')]);return (int)db()->lastInsertId();});
 if($provider==='demo')query("UPDATE ad_orders SET provider_id=?,status='pending' WHERE id=?",['demo_ad_'.$id,$id]);
 else{
  $customer=$business['provider_customer_id'];if(!$customer){$owner=user();$tax=preg_replace('/\D/','',(string)($d['cpfCnpj']??''));$remote=asaas('POST','/customers',['name'=>$owner['name'],'email'=>$owner['email'],'cpfCnpj'=>$tax,'externalReference'=>'business_'.$business['id']]);$customer=$remote['id'];query('UPDATE businesses SET provider_customer_id=? WHERE id=?',[$customer,$business['id']]);}
  $reference='ad_order_'.$id;$found=asaas('GET','/payments?externalReference='.rawurlencode($reference).'&limit=10');$matches=array_values(array_filter($found['data']??[],fn($p)=>($p['externalReference']??'')===$reference));if(count($matches)>1)fail('Cobranças duplicadas no Asaas. Solicite conciliação.',409);$remote=$matches[0]??null;
  if(!$remote)$remote=asaas('POST','/payments',['customer'=>$customer,'billingType'=>'PIX','value'=>$amount/100,'dueDate'=>$due,'description'=>'Publicidade avulsa Guia Sarzedo · '.businessCode((int)$business['id']),'externalReference'=>$reference]);
  applyAdOrderPayment($remote);
 }
 return adOrderView(one('SELECT * FROM ad_orders WHERE id=?',[$id]),$business);
}
function confirmManualAdOrder(int $id,array $d,int $admin): void {
 if(empty($d['checked']))fail('Confirme que conferiu o recebimento no extrato.');$amount=priceInCents($d['amount']??'');$ref=strtoupper(field($d,'reference',10,100));if(!preg_match('/^[A-Z0-9-]+$/',$ref))fail('Informe o identificador Pix sem espaços.');$date=field($d,'paid_date',10,10);$parsed=DateTimeImmutable::createFromFormat('!Y-m-d',$date);if(!$parsed||$parsed->format('Y-m-d')!==$date||$date>date('Y-m-d'))fail('Informe a data real do recebimento.');
 transaction(function()use($id,$d,$admin,$amount,$ref,$date){$o=one('SELECT * FROM ad_orders WHERE id=?',[$id]);if(!$o)fail('Pedido não encontrado.',404);lockBusiness((int)$o['business_id']);$o=one('SELECT * FROM ad_orders WHERE id=?',[$id]);if($o['provider']!=='manual'||!in_array($o['status'],['pending','overdue'],true))fail('Pedido não está aguardando Pix manual.',409);if($amount!==(int)$o['amount_cents'])fail('O valor deve corresponder à cobrança.');query('UPDATE settings SET value=value WHERE setting_key=?',['support_whatsapp']);if(one('SELECT id FROM advertisements WHERE bank_reference=?',[$ref])||one('SELECT payment_id FROM manual_pix_payments WHERE bank_reference=?',[$ref])||one('SELECT id FROM ad_orders WHERE bank_reference=?',[$ref]))fail('Este identificador já foi utilizado.',409);query("UPDATE ad_orders SET status='paid',paid_at=?,bank_reference=?,confirmed_by=? WHERE id=?",[$date.' 12:00:00',$ref,$admin,$id]);audit($admin,(int)$o['business_id'],'ad_order_paid','Pedido de publicidade #'.$id.' pago; referência conferida.');});
}
function uploadOwnAdImage(array $business): array {
 $order=one("SELECT o.* FROM ad_orders o LEFT JOIN advertisements a ON a.order_id=o.id WHERE o.business_id=? AND o.status='paid' AND a.id IS NULL ORDER BY o.id DESC LIMIT 1",[$business['id']]);if(!$order)fail('Pague a publicidade antes de enviar a imagem.',409);
 $kind=(string)($_POST['kind']??'image');if(!in_array($kind,['image','logo'],true))fail('Tipo de imagem inválido.');
 $file=$_FILES['photo']??null;if(!$file||$file['error']!==UPLOAD_ERR_OK||$file['size']>5*1024*1024)fail('Envie JPG, PNG ou WebP de até 5 MB.');
 $info=@getimagesize($file['tmp_name']);if(!$info||!in_array($info['mime'],['image/jpeg','image/png','image/webp'],true)||$info[0]*$info[1]>20000000)fail('Imagem inválida ou superior a 20 megapixels.');
 $source=@imagecreatefromstring(file_get_contents($file['tmp_name']));if(!$source)fail('Não foi possível abrir a imagem.');
 $w=$kind==='logo'?400:900;$h=$kind==='logo'?400:600;$target=imagecreatetruecolor($w,$h);imagefill($target,0,0,imagecolorallocate($target,255,255,255));$scale=min($w/$info[0],$h/$info[1]);$sw=(int)round($info[0]*$scale);$sh=(int)round($info[1]*$scale);imagecopyresampled($target,$source,(int)(($w-$sw)/2),(int)(($h-$sh)/2),0,0,$sw,$sh,$info[0],$info[1]);imagedestroy($source);
 $url='/uploads/'.bin2hex(random_bytes(16)).'.jpg';try{if(!imagejpeg($target,ROOT.'/public'.$url,85))fail('Não foi possível salvar a imagem.',500);transaction(function()use($order,$business,$kind,$url){lockBusiness((int)$business['id']);if(one('SELECT id FROM advertisements WHERE order_id=?',[$order['id']]))fail('Este pedido já foi utilizado.',409);query('UPDATE ad_orders SET '.($kind==='logo'?'logo_url':'image_url').'=? WHERE id=? AND status=?',[$url,$order['id'],'paid']);});}catch(Throwable $e){if(is_file(ROOT.'/public'.$url))@unlink(ROOT.'/public'.$url);throw $e;}finally{imagedestroy($target);}return ['url'=>$url,'kind'=>$kind];
}
function createOwnAdvertisement(array $business,array $d): array {
 if($business['status']==='suspended'||!isListed($business))fail('A vitrine precisa estar ativa e disponível para publicar a publicidade.',409);
 $orderId=(int)($d['order_id']??0);if($orderId<1)fail('Selecione o pedido pago.');
 $mode=field($d,'creative_mode',1,20);if(!in_array($mode,['ready','manual'],true))fail('Escolha o formato da publicidade.');
 $result=transaction(function()use($business,$d,$orderId,$mode){lockBusiness((int)$business['id']);$o=one('SELECT * FROM ad_orders WHERE id=? AND business_id=? AND status=?',[$orderId,$business['id'],'paid']);if(!$o||one('SELECT id FROM advertisements WHERE order_id=?',[$orderId]))fail('Este pedido não está pago ou já foi utilizado.',409);
  $image=$mode==='ready'?$o['image_url']:field($d,'image_url',0,255);if($mode==='manual'&&$image===''&&$o['image_url'])$image=$o['image_url'];if(!$image||($image!==$o['image_url']&&!in_array($image,array_column(freeCoverLibrary(),'url'),true)))fail('Envie uma imagem ou escolha uma opção do portal.');
  $title=$mode==='ready'?'Publicidade de '.$business['name']:field($d,'title',3,100);$description=$mode==='ready'?'Banner completo de '.$business['name'].'.':field($d,'description',5,600);$offer=$mode==='manual'&&!empty($d['offer_price'])?priceInCents($d['offer_price']):null;$start=date('Y-m-d H:i:s');$end=adMonthEnd($start);
  query("INSERT INTO advertisements(business_id,title,description,image_url,logo_url,offer_price_cents,monthly_amount_cents,creative_mode,order_id,status,starts_at,expires_at,paid_at,bank_reference,created_at) VALUES(?,?,?,?,?,?,?,?,?,'active',?,?,?,?,?)",[$business['id'],$title,$description,$image,$mode==='manual'?$o['logo_url']:'',$offer,$o['amount_cents'],$mode,$orderId,$start,$end,$o['paid_at'],'ADORDER-'.$orderId,$start]);return one('SELECT * FROM advertisements WHERE id=?',[db()->lastInsertId()]);
 });return $result;
}
function adOrderRoutes(string $path,string $method): void {
 if($path==='/api/me/ad-order'&&$method==='GET'){$b=business();$o=one('SELECT * FROM ad_orders WHERE business_id=? ORDER BY id DESC LIMIT 1',[$b['id']]);jsonResponse($o?adOrderView(expireAdOrder(reconcileAdOrder($o)),$b):null);}
 if($path==='/api/me/ad-order/status'&&$method==='GET'){$b=business();$o=one('SELECT * FROM ad_orders WHERE business_id=? ORDER BY id DESC LIMIT 1',[$b['id']]);if(!$o)jsonResponse(null);if($o['provider']==='asaas'&&!in_array($o['status'],['paid','refunded','expired'],true)){try{$o=reconcileAdOrder($o);}catch(Throwable $e){error_log('Consulta publicidade #'.$o['id'].': '.$e->getMessage());}}$o=expireAdOrder($o);jsonResponse(['id'=>(int)$o['id'],'status'=>$o['status'],'expires_at'=>$o['expires_at'],'expires_in_seconds'=>max(0,strtotime($o['expires_at'])-time()),'paid_at'=>$o['paid_at']]);}
 if($path==='/api/me/ad-order'&&$method==='POST'){$b=business();jsonResponse(createAdOrder($b,input()));}
 if($path==='/api/me/ad-order/image'&&$method==='POST'){$b=business();jsonResponse(uploadOwnAdImage($b));}
 if($path==='/api/me/advertisements'&&$method==='GET'){$b=business();jsonResponse(query('SELECT id,title,description,image_url,logo_url,offer_price_cents,monthly_amount_cents,creative_mode,status,starts_at,expires_at,order_id FROM advertisements WHERE business_id=? ORDER BY id DESC',[$b['id']])->fetchAll());}
 if($path==='/api/me/advertisements'&&$method==='POST'){$b=business();jsonResponse(createOwnAdvertisement($b,input()),201);}
 if($path==='/api/me/ad-order/demo-pay'&&$method==='POST'){if(env('APP_ENV','local')!=='local'||env('PAYMENT_DRIVER')!=='demo')fail('Simulação indisponível.',404);$b=business();$o=one("SELECT * FROM ad_orders WHERE business_id=? AND provider='demo' AND status='pending' ORDER BY id DESC LIMIT 1",[$b['id']]);if(!$o)fail('Gere o pedido primeiro.',404);query("UPDATE ad_orders SET status='paid',paid_at=? WHERE id=?",[date('Y-m-d H:i:s'),$o['id']]);jsonResponse(adOrderView(one('SELECT * FROM ad_orders WHERE id=?',[$o['id']]),$b));}
 if(preg_match('#^/api/admin/ad-orders/(\d+)/confirm-manual$#',$path,$m)&&$method==='POST'){$admin=adminUser();confirmManualAdOrder((int)$m[1],input(),(int)$admin['id']);jsonResponse(['ok'=>true]);}
}
