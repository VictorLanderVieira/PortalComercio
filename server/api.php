<?php
require __DIR__.'/bootstrap.php';
$requestLock=fopen(ROOT.'/storage/requests.lock','c');if(!$requestLock||!flock($requestLock,LOCK_SH)){http_response_code(503);exit;}
if(is_file(ROOT.'/storage/maintenance.flag')){header('Retry-After: 60');jsonResponse(['error'=>'Portal em atualização. Tente novamente em instantes.'],503);}
require __DIR__.'/payments.php';
require __DIR__.'/features.php';
require __DIR__.'/advertisements.php';
require __DIR__.'/finance.php';
require __DIR__.'/google.php';
require __DIR__.'/metrics.php';
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
session_set_cookie_params(['httponly'=>true,'secure'=>env('APP_ENV','local')==='production','samesite'=>'Lax','path'=>'/']);
session_start();
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
$method=$_SERVER['REQUEST_METHOD'];
try {
 if ($path==='/api/webhook' && $method==='POST') { handleWebhook(); }
 if ($method!=='GET' && !hash_equals($_SESSION['csrf'],$_SERVER['HTTP_X_CSRF_TOKEN']??'')) fail('Sessão expirada. Atualize a página.',403);
 if($path==='/api/metrics'&&$method==='GET')jsonResponse(publicMetrics());
 if($path==='/api/metrics'&&$method==='POST'){recordPortalMetric(input());jsonResponse(publicMetrics());}
 googleRoutes($path,$method);
 advertisementRoutes($path,$method);
 featureRoutes($path,$method);
 if ($path==='/api/session') {
  jsonResponse(['user'=>empty($_SESSION['user_id'])?null:one('SELECT id,name,email,role FROM users WHERE id=?',[$_SESSION['user_id']]),'csrf'=>$_SESSION['csrf'],'demo'=>env('PAYMENT_DRIVER','demo')==='demo','local'=>env('APP_ENV','local')==='local','settings'=>settings(),'manual_pix'=>env('PAYMENT_DRIVER')==='manual_pix','google_enabled'=>googleLoginEnabled()]);
 }
 if ($path==='/api/catalog' && $method==='GET') jsonResponse(['categories'=>query('SELECT * FROM categories')->fetchAll(),'plans'=>planCatalog(),'free_covers'=>freeCoverLibrary()]);
 if (in_array($path,['/api/login','/api/register']) && $method==='POST') {
  $d=input(); $email=strtolower(field($d,'email',3,190)); $password=field($d,'password',8,200);
  if(!filter_var($email,FILTER_VALIDATE_EMAIL)) fail('Informe um e-mail válido.');
  $bucket=hash('sha256',($_SERVER['REMOTE_ADDR']??'').':'.date('Y-m-d-H').':auth');
  transaction(function()use($bucket){$r=one('SELECT * FROM rate_limits WHERE bucket=?',[$bucket]); if($r && $r['attempts']>=30) fail('Muitas tentativas. Tente novamente na próxima hora.',429); if($r)query('UPDATE rate_limits SET attempts=attempts+1 WHERE bucket=?',[$bucket]); else query('INSERT INTO rate_limits VALUES(?,?,?)',[$bucket,1,time()+3600]);});
  if ($path==='/api/register') {
   if(empty($d['consent']))fail('Leia e aceite as informações de privacidade para continuar.');
   if(one('SELECT id FROM users WHERE email=?',[$email])) fail('Este e-mail já está cadastrado. Entre na sua conta.',409);
   query('INSERT INTO users(name,email,password_hash,created_at) VALUES(?,?,?,?)',[field($d,'name',2,100),$email,password_hash($password,PASSWORD_DEFAULT),date('Y-m-d H:i:s')]);
  }
  $u=one('SELECT * FROM users WHERE email=?',[$email]);
  if(!$u || !password_verify($password,$u['password_hash'])) fail('E-mail ou senha incorretos.',401);
  session_regenerate_id(true); $_SESSION['user_id']=$u['id']; $_SESSION['csrf']=bin2hex(random_bytes(32));
  jsonResponse(['user'=>['id'=>$u['id'],'name'=>$u['name'],'email'=>$u['email'],'role'=>$u['role']],'csrf'=>$_SESSION['csrf']]);
 }
 if($path==='/api/logout' && $method==='POST') { $_SESSION=[]; session_regenerate_id(true); jsonResponse(['ok'=>true]); }
 if($path==='/api/businesses' && $method==='GET') {
  $params=visibilityParams(); $where=visibilitySql();
  if(!empty($_GET['q'])) { $q='%'.mb_substr(trim($_GET['q']),0,100).'%'; $where.=' AND (b.name LIKE ? OR b.description LIKE ? OR c.name LIKE ?)'; array_push($params,$q,$q,$q); }
  if(!empty($_GET['category'])) { $where.=' AND b.category_id=?'; $params[]=(int)$_GET['category']; }
  if(!empty($_GET['neighborhood'])) { $where.=' AND b.neighborhood=?'; $params[]=mb_substr($_GET['neighborhood'],0,100); }
  $order=($_GET['sort']??'')==='rating'?'rating DESC,b.name ASC,b.id ASC':"CASE WHEN (b.paid_until>'".date('Y-m-d H:i:s')."' OR b.trial_until>'".date('Y-m-d H:i:s')."' OR b.manual_until>'".date('Y-m-d H:i:s')."') THEN p.priority ELSE 0 END DESC,rating DESC,b.name ASC,b.id ASC";
  $ratingEligibility="b.plan_id<>4 AND b.reviews_opt_in=1 AND (b.paid_until>'".date('Y-m-d H:i:s')."' OR b.trial_until>'".date('Y-m-d H:i:s')."' OR b.manual_until>'".date('Y-m-d H:i:s')."')";
  $pageSize=40;$total=(int)one("SELECT COUNT(*) n FROM businesses b JOIN plans p ON p.id=b.plan_id JOIN categories c ON c.id=b.category_id WHERE $where",$params)['n'];$pages=max(1,(int)ceil($total/$pageSize));$page=max(1,min($pages,(int)($_GET['page']??1)));$offset=array_key_exists('offset',$_GET)?max(0,min(100000,(int)$_GET['offset'])):($page-1)*$pageSize;
  $items=query("SELECT b.*,c.name category,p.name plan,p.priority,COALESCE((SELECT AVG(r.rating) FROM reviews r WHERE r.business_id=b.id AND $ratingEligibility),0) rating,(SELECT COUNT(*) FROM reviews r WHERE r.business_id=b.id AND $ratingEligibility) review_count FROM businesses b JOIN plans p ON p.id=b.plan_id JOIN categories c ON c.id=b.category_id WHERE $where ORDER BY $order LIMIT $pageSize OFFSET $offset",$params)->fetchAll();
  header('X-Total-Count: '.$total);header('X-Total-Pages: '.$pages);header('X-Current-Page: '.$page);header('X-Page-Size: '.$pageSize);
  jsonResponse(array_map('publicBusiness',$items));
 }
 if(preg_match('#^/api/businesses/(\d+)$#',$path,$m) && $method==='GET') {
  $b=one('SELECT b.*,c.name category,p.name plan,p.priority FROM businesses b JOIN categories c ON c.id=b.category_id JOIN plans p ON p.id=b.plan_id WHERE b.id=?',[$m[1]]);
  if(!$b || !isListed($b)) fail('Este comércio não está disponível no momento.',404);
  $b=publicBusiness($b);
  $b['photos']=$b['is_free']?[]:query('SELECT id,url FROM photos WHERE business_id=?',[$m[1]])->fetchAll();
  $b['reviews']=!$b['reviews_enabled']?[]:query('SELECT r.id,r.rating,r.comment,r.created_at,u.name FROM reviews r JOIN users u ON u.id=r.user_id WHERE business_id=? ORDER BY r.id DESC',[$m[1]])->fetchAll();
  $b['promotions']=businessPromotions((int)$m[1]);
  $b['posts']=$b['is_free']?[]:query('SELECT id,title,body,created_at FROM posts WHERE business_id=? ORDER BY id DESC LIMIT 20',[$m[1]])->fetchAll(); jsonResponse($b);
 }
 if(preg_match('#^/api/businesses/(\d+)/reviews$#',$path,$m) && $method==='POST') {
  $u=user(); $b=one('SELECT * FROM businesses WHERE id=?',[$m[1]]); if(!$b || !isLive($b) || !isListed($b)) fail('Comércio indisponível.',404);
  if(isFreeBusiness($b))fail('Este plano não inclui avaliações de clientes.');if(empty($b['reviews_opt_in']))fail('As avaliações estão desabilitadas para este negócio.');
  if($b['user_id']==$u['id']) fail('Você não pode avaliar seu próprio comércio.');
  $d=input(); $rating=filter_var($d['rating']??null,FILTER_VALIDATE_INT); if(!$rating || $rating<1 || $rating>5) fail('Escolha uma nota de 1 a 5.');
  if(one('SELECT id FROM reviews WHERE business_id=? AND user_id=?',[$b['id'],$u['id']])) fail('Você já avaliou este comércio.',409);
  query('INSERT INTO reviews(business_id,user_id,rating,comment,created_at) VALUES(?,?,?,?,?)',[$b['id'],$u['id'],$rating,field($d,'comment',5,1000),date('Y-m-d H:i:s')]); jsonResponse(['ok'=>true],201);
 }
 if($path==='/api/me/business' && $method==='GET') {
  $u=user(); $b=one('SELECT * FROM businesses WHERE user_id=?',[$u['id']]);
  if($b){$b['business_code']=businessCode((int)$b['id']);$b['free_cover_url']=freeCoverUrl($b);$b['is_free']=isFreeBusiness($b);$b['display_free']=isFreeBusiness($b)||hasFreeDisplay($b);$b['display_until']=$b['display_free']?freeDisplayUntil($b):validUntil($b);$b['listed']=isListed($b);$b['suspended_by_expiry']=suspendedByExpiry($b);$b['visible']=isLive($b)&&isListed($b);$b['valid_until']=validUntil($b);$b['promotions']=query('SELECT * FROM promotions WHERE business_id=? ORDER BY id DESC',[$b['id']])->fetchAll();$b['photos']=query('SELECT * FROM photos WHERE business_id=?',[$b['id']])->fetchAll();$b['posts']=query('SELECT * FROM posts WHERE business_id=? ORDER BY id DESC',[$b['id']])->fetchAll();$b['subscriptions']=query('SELECT s.*,p.name plan FROM subscriptions s JOIN plans p ON p.id=s.plan_id WHERE business_id=? ORDER BY id DESC',[$b['id']])->fetchAll();$b['payments']=query('SELECT p.* FROM payments p JOIN subscriptions s ON s.id=p.subscription_id WHERE s.business_id=? ORDER BY p.id DESC LIMIT 24',[$b['id']])->fetchAll();}
  jsonResponse($b);
 }
 if($path==='/api/me/photos' && $method==='POST') {
  $b=business(); $kind=$_POST['kind']??'gallery'; if(!in_array($kind,['gallery','logo','cover'])) fail('Tipo de imagem inválido.');
  if(isFreeBusiness($b)||!isLive($b))fail('Ative um plano pago para enviar imagens. No Free, usamos a imagem padrão.');
  $f=$_FILES['photo']??null; if(!$f || $f['error']!==UPLOAD_ERR_OK || $f['size']>5*1024*1024) fail('Envie uma imagem de até 5 MB.');
  $info=@getimagesize($f['tmp_name']); if(!$info || !in_array($info['mime'],['image/jpeg','image/png','image/webp']) || $info[0]*$info[1]>25000000) fail('Use JPG, PNG ou WebP com até 25 megapixels.');
  $image=@imagecreatefromstring(file_get_contents($f['tmp_name'])); if(!$image)fail('Não foi possível ler a imagem.');
  $width=imagesx($image);$height=imagesy($image);$scale=min(1,1600/max($width,$height));if($scale<1){$resized=imagecreatetruecolor((int)round($width*$scale),(int)round($height*$scale));imagefill($resized,0,0,imagecolorallocate($resized,255,255,255));imagecopyresampled($resized,$image,0,0,0,0,imagesx($resized),imagesy($resized),$width,$height);imagedestroy($image);$image=$resized;}
  $file='/uploads/'.bin2hex(random_bytes(16)).'.jpg';
  transaction(function()use($b,$kind,$file,$image){lockBusiness((int)$b['id']); $plan=one('SELECT p.* FROM plans p JOIN businesses b ON b.plan_id=p.id WHERE b.id=?',[$b['id']]); if($kind==='gallery' && one('SELECT COUNT(*) n FROM photos WHERE business_id=?',[$b['id']])['n']>=$plan['photo_limit'])fail('Você atingiu o limite de fotos do plano.'); imagejpeg($image,ROOT.'/public'.$file,85); if($kind==='gallery')query('INSERT INTO photos(business_id,url) VALUES(?,?)',[$b['id'],$file]); else query('UPDATE businesses SET '.($kind==='logo'?'logo_url':'cover_url').'=?,free_cover_key=? WHERE id=?',[$file,'',$b['id']]);});
  imagedestroy($image); jsonResponse(['url'=>$file],201);
 }
 if(preg_match('#^/api/me/photos/(\d+)$#',$path,$m) && $method==='DELETE') {
  $b=business(); $p=one('SELECT * FROM photos WHERE id=? AND business_id=?',[$m[1],$b['id']]); if(!$p)fail('Foto não encontrada.',404); if(one('SELECT id FROM promotions WHERE business_id=? AND image_url=?',[$b['id'],$p['url']]))fail('Remova a promoção que utiliza esta imagem antes de excluir a foto.'); query('DELETE FROM photos WHERE id=?',[$p['id']]); if(preg_match('#^/uploads/[a-f0-9]{32}\.jpg$#',$p['url']))@unlink(ROOT.'/public'.$p['url']); jsonResponse(['ok'=>true]);
 }
 if($path==='/api/me/posts' && $method==='POST') {
  $b=business(); if(!isLive($b))fail('Ative seu plano antes de publicar.'); $d=input(); $title=field($d,'title',3,100); $body=field($d,'body',10,1500);
  transaction(function()use($b,$title,$body){lockBusiness((int)$b['id']);$plan=one('SELECT * FROM plans WHERE id=?',[$b['plan_id']]);$n=one('SELECT COUNT(*) n FROM posts WHERE business_id=? AND created_at>=?',[$b['id'],date('Y-m-01 00:00:00')]);if($n['n']>=$plan['post_limit'])fail('Limite mensal de publicações atingido.');query('INSERT INTO posts(business_id,title,body,created_at) VALUES(?,?,?,?)',[$b['id'],$title,$body,date('Y-m-d H:i:s')]);});jsonResponse(['ok'=>true],201);
 }
 if($path==='/api/me/checkout' && $method==='POST') { $b=business(); $d=input(); jsonResponse(checkout($b,$d)); }
 if($path==='/api/me/cancel-subscription' && $method==='POST') {
  $b=business();$s=one("SELECT * FROM subscriptions WHERE business_id=? AND status IN ('active','pending') ORDER BY id DESC",[$b['id']]); if(!$s)fail('Nenhuma assinatura para cancelar.');
  if(!str_starts_with($s['provider_id']??'','manual_')&&!str_starts_with($s['provider_id']??'','demo_'))asaas('DELETE','/subscriptions/'.rawurlencode($s['provider_id']));
  query("UPDATE subscriptions SET status='cancelled' WHERE id=?",[$s['id']]); jsonResponse(['ok'=>true]);
 }
 if($path==='/api/me/demo-pay' && $method==='POST') {
  if(env('APP_ENV','local')!=='local' || env('PAYMENT_DRIVER','demo')!=='demo')fail('Simulação indisponível.',404);
  $b=business(); $p=one("SELECT p.* FROM payments p JOIN subscriptions s ON s.id=p.subscription_id WHERE s.business_id=? AND s.status='pending' AND p.status='PENDING' ORDER BY p.id DESC",[$b['id']]); if(!$p)fail('Gere uma cobrança primeiro.');
  applyPayment(['id'=>$p['provider_id'],'status'=>'RECEIVED','value'=>$p['amount_cents']/100,'dueDate'=>$p['due_date'],'paymentDate'=>date('Y-m-d')]);jsonResponse(['ok'=>true]);
 }
 fail('Rota não encontrada.',404);
} catch(Throwable $e) { if(db()->inTransaction())db()->rollBack(); error_log($e->getMessage()); jsonResponse(['error'=>'Não foi possível concluir a operação. Tente novamente ou contate o suporte.'],500); }


