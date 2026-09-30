<?php
// Only paid, active advertisements explicitly authorized by their owner enter this queue.
function instagramAdsConfigured(): bool {
 return env('INSTAGRAM_ADS_ENABLED','false')==='true'
  && preg_match('/^[0-9]{5,30}$/',env('INSTAGRAM_IG_USER_ID'))
  && env('INSTAGRAM_PAGE_ACCESS_TOKEN')!==''
  && preg_match('/^v[0-9]{1,2}\.[0-9]$/',env('INSTAGRAM_GRAPH_VERSION','v26.0'));
}
function instagramAdCaption(array $ad,array $business): string {
 $name=trim($business['name']);$code=businessCode((int)$business['id']);
 $lines=['Publicidade · '.$name,'Sarzedo, MG'];
 if($ad['creative_mode']!=='ready'){
  $lines[]=trim($ad['title']);
  $lines[]=mb_substr(trim($ad['description']),0,450);
  if($ad['offer_price_cents']!==null)$lines[]='Valor informado: R$ '.number_format((int)$ad['offer_price_cents']/100,2,',','.');
 }
 $lines[]='Conheça o negócio no Guia Sarzedo: '.rtrim(env('APP_URL','https://guiasarzedo.com.br'),'/').'/empresa/'.$business['id'];
 $lines[]='Código do negócio: '.$code;
 $lines[]='Consulte disponibilidade, preço e condições diretamente com o anunciante.';
 $lines[]='#GuiaSarzedo #PublicidadeLocal #SarzedoMG';
 return mb_substr(implode("\n\n",array_filter($lines)),0,2100);
}
function queueInstagramAdvertisement(array $ad,array $business): void {
 if(empty($ad['order_id'])||empty($ad['paid_at'])||$ad['status']!=='active'||!empty($business['is_demo']))return;
 // The uploaded ad is always JPEG. SVG library images are not accepted by the Meta image endpoint.
 $image=preg_match('#^/uploads/[a-f0-9]{32}\.jpg$#',(string)$ad['image_url'])
  ? $ad['image_url'] : '/assets/icon-512.png';
 $now=date('Y-m-d H:i:s');query('INSERT INTO instagram_ad_posts(advertisement_id,caption,image_url,consent_at,created_at) VALUES(?,?,?,?,?)',[$ad['id'],instagramAdCaption($ad,$business),$image,$now,$now]);
}
function instagramAdsSettings(): array {return ['enabled'=>env('INSTAGRAM_ADS_ENABLED','false')==='true','configured'=>instagramAdsConfigured(),'ig_user_id'=>env('INSTAGRAM_IG_USER_ID'),'token_configured'=>env('INSTAGRAM_PAGE_ACCESS_TOKEN')!=='','profile'=>'guia_sarzedo'];}
function saveInstagramAdsSettings(array $data,int $admin): void {
 $bucket='instagram_admin_'.$admin.'_'.(int)floor(time()/600);
 $blocked=transaction(function()use($bucket){$r=one('SELECT attempts FROM rate_limits WHERE bucket=?',[$bucket]);if($r&&$r['attempts']>=5)return true;if($r)query('UPDATE rate_limits SET attempts=attempts+1 WHERE bucket=?',[$bucket]);else query('INSERT INTO rate_limits VALUES(?,?,?)',[$bucket,1,time()+600]);return false;});
 if($blocked)fail('Muitas tentativas. Aguarde dez minutos.',429);
 $account=one('SELECT password_hash,role FROM users WHERE id=?',[$admin]);
 if(!$account||$account['role']!=='admin'||!password_verify((string)($data['admin_password']??''),$account['password_hash']))fail('Confirme sua senha de administrador.',403);
 query('DELETE FROM rate_limits WHERE bucket=?',[$bucket]);
 $enabled=!empty($data['enabled']);$id=field($data,'ig_user_id',0,30);$token=field($data,'page_access_token',0,1024);
 if($id!==''&&!preg_match('/^[0-9]{5,30}$/',$id))fail('ID da conta profissional inválido.');
 if($token!==''&&(strlen($token)<30||preg_match('/\s|[\x00-\x1f\x7f]/',$token)))fail('Token inválido.');
 if($enabled&&env('DEPLOY_ENV')==='staging')fail('Publicação no Instagram desativada no ambiente de teste.',403);
 $file=integrationConfigPath();$lock=fopen($file.'.lock','c');if(!$lock||!flock($lock,LOCK_EX))fail('Não foi possível bloquear a configuração.',500);
 try{$config=integrationConfig();if($id!=='')$config['INSTAGRAM_IG_USER_ID']=$id;if($token!=='')$config['INSTAGRAM_PAGE_ACCESS_TOKEN']=$token;
  if($enabled){$effectiveId=$config['INSTAGRAM_IG_USER_ID']??env('INSTAGRAM_IG_USER_ID');$effectiveToken=$config['INSTAGRAM_PAGE_ACCESS_TOKEN']??env('INSTAGRAM_PAGE_ACCESS_TOKEN');if(!preg_match('/^[0-9]{5,30}$/',$effectiveId)||$effectiveToken==='')fail('Informe o ID da conta e o token antes de habilitar.');$profile=instagramGraphWithToken('GET',$effectiveId,['fields'=>'username'],$effectiveToken);if(strtolower((string)($profile['username']??''))!=='guia_sarzedo')fail('A credencial não pertence ao perfil @guia_sarzedo.',403);}
  $config['INSTAGRAM_ADS_ENABLED']=$enabled?'true':'false';
  $tmp=tempnam(dirname($file),'instagram-');if($tmp===false)fail('Não foi possível salvar a configuração.',500);chmod($tmp,0600);if(file_put_contents($tmp,json_encode($config,JSON_THROW_ON_ERROR),LOCK_EX)===false||!rename($tmp,$file)){@unlink($tmp);fail('Não foi possível salvar a configuração.',500);}chmod($file,0600);audit($admin,null,'instagram_settings_updated','Publicação de publicidade no Instagram '.($enabled?'ativada':'desativada').'; credenciais omitidas.');
 }finally{flock($lock,LOCK_UN);fclose($lock);}
}
function instagramGraph(string $method,string $path,array $params=[]): array {
 return instagramGraphWithToken($method,$path,$params,env('INSTAGRAM_PAGE_ACCESS_TOKEN'));
}
function instagramGraphWithToken(string $method,string $path,array $params,string $token): array {
 $version=env('INSTAGRAM_GRAPH_VERSION','v26.0');$url='https://graph.facebook.com/'.$version.'/'.$path;
 $ch=curl_init($url);$headers=['Authorization: Bearer '.$token,'Accept: application/json'];
 if($method==='POST'){$headers[]='Content-Type: application/x-www-form-urlencoded';curl_setopt($ch,CURLOPT_POST,true);curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($params));}
 elseif($params)curl_setopt($ch,CURLOPT_URL,$url.'?'.http_build_query($params));
 curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>45,CURLOPT_HTTPHEADER=>$headers]);
 $body=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$error=curl_error($ch);curl_close($ch);
 if($body===false||$status<200||$status>=300)throw new RuntimeException('Instagram Graph API indisponível (HTTP '.$status.($error?' / conexão':'').').');
 $data=json_decode($body,true);if(!is_array($data))throw new RuntimeException('Resposta inválida da API do Instagram.');return $data;
}
function runInstagramAdPosts(bool $dryRun=false): array {
 $pending=(int)one("SELECT COUNT(*) n FROM instagram_ad_posts WHERE status='pending'")['n'];
 if($dryRun||!instagramAdsConfigured()||env('APP_ENV','local')!=='production')return ['pending'=>$pending,'published'=>0,'configured'=>instagramAdsConfigured(),'environment'=>env('APP_ENV','local')];
 $today=date('Y-m-d').' 00:00:00';if(one("SELECT id FROM instagram_ad_posts WHERE published_at>=? AND status='published' LIMIT 1",[$today]))return ['pending'=>$pending,'published'=>0,'configured'=>true,'daily_limit'=>true];
 $today=date('Y-m-d').' 00:00:00';if(one("SELECT id FROM instagram_ad_posts WHERE published_at>=? AND status='published' LIMIT 1",[$today]))return ['pending'=>$pending,'published'=>0,'configured'=>true,'daily_limit'=>true];
 // A single publication per worker run avoids flooding the profile.
 $job=one("SELECT q.*,a.business_id,a.status ad_status,a.paid_at,a.expires_at,a.order_id,o.status order_status,b.status business_status,b.approval_status,b.is_demo FROM instagram_ad_posts q JOIN advertisements a ON a.id=q.advertisement_id JOIN ad_orders o ON o.id=a.order_id JOIN businesses b ON b.id=a.business_id WHERE q.status='pending' ORDER BY q.id LIMIT 1");
 if(!$job)return ['pending'=>0,'published'=>0,'configured'=>true];
 $business=one('SELECT * FROM businesses WHERE id=?',[$job['business_id']]);
 if($job['ad_status']!=='active'||$job['order_status']!=='paid'||!$job['paid_at']||$job['expires_at']<=date('Y-m-d H:i:s')||!isListed($business)||!empty($job['is_demo'])){
  query("UPDATE instagram_ad_posts SET status='skipped',last_error=? WHERE id=?",['Publicidade não está mais apta a divulgação.',$job['id']]);return ['pending'=>$pending-1,'published'=>0,'configured'=>true];
 }
 $base=rtrim(env('APP_URL'),'/');if(!preg_match('#^https://[^/]+$#',$base))throw new RuntimeException('APP_URL deve ser HTTPS para publicação no Instagram.');
 $image=$base.$job['image_url'];
 try {
  // Create the container once, then retain its ID to avoid recreating media on the next run.
  $container=$job['container_id'];
  if(!$container){$created=instagramGraph('POST',env('INSTAGRAM_IG_USER_ID').'/media',['image_url'=>$image,'caption'=>$job['caption']]);$container=(string)($created['id']??'');if(!preg_match('/^[0-9]+$/',$container))throw new RuntimeException('Container do Instagram não foi retornado.');query("UPDATE instagram_ad_posts SET container_id=?,status='processing',attempts=attempts+1 WHERE id=? AND status='pending'",[$container,$job['id']]);}
 }catch(Throwable $e){query("UPDATE instagram_ad_posts SET status='failed',attempts=attempts+1,last_error=? WHERE id=?",[mb_substr($e->getMessage(),0,500),$job['id']]);return ['pending'=>$pending-1,'published'=>0,'configured'=>true,'failed'=>1];}
 return ['pending'=>$pending-1,'published'=>0,'configured'=>true,'processing'=>1];
}
function finishInstagramAdPosts(bool $dryRun=false): array {
 if($dryRun||!instagramAdsConfigured()||env('APP_ENV','local')!=='production')return ['published'=>0];
 $job=one("SELECT q.*,a.status ad_status,a.expires_at,a.business_id,o.status order_status FROM instagram_ad_posts q JOIN advertisements a ON a.id=q.advertisement_id JOIN ad_orders o ON o.id=a.order_id WHERE q.status='processing' ORDER BY q.id LIMIT 1");
 if(!$job)return ['published'=>0];
 $business=one('SELECT * FROM businesses WHERE id=?',[$job['business_id']]);
 if($job['ad_status']!=='active'||$job['order_status']!=='paid'||$job['expires_at']<=date('Y-m-d H:i:s')||!isListed($business)){query("UPDATE instagram_ad_posts SET status='skipped',last_error=? WHERE id=?",['Publicidade deixou de estar apta antes do envio.',$job['id']]);return ['published'=>0];}
 try{$state=instagramGraph('GET',$job['container_id'],['fields'=>'status_code']);}
 catch(Throwable $e){query('UPDATE instagram_ad_posts SET last_error=? WHERE id=?',[mb_substr($e->getMessage(),0,500),$job['id']]);return ['published'=>0];}
 $code=$state['status_code']??'';
 if($code==='ERROR'||$code==='EXPIRED'){query("UPDATE instagram_ad_posts SET status='failed',last_error=? WHERE id=?",['A Meta recusou o processamento da imagem.',$job['id']]);return ['published'=>0,'failed'=>1];}
 if($code!=='FINISHED')return ['published'=>0,'processing'=>1];
 // Mark uncertain before the irreversible call. A timeout after publish must not duplicate a post.
 query("UPDATE instagram_ad_posts SET status='publishing',attempts=attempts+1,last_error=NULL WHERE id=? AND status='processing'",[$job['id']]);
 try{$published=instagramGraph('POST',env('INSTAGRAM_IG_USER_ID').'/media_publish',['creation_id'=>$job['container_id']]);$media=(string)($published['id']??'');if(!preg_match('/^[0-9]+$/',$media))throw new RuntimeException('ID da publicação não retornado.');query("UPDATE instagram_ad_posts SET status='published',media_id=?,published_at=? WHERE id=?",[$media,date('Y-m-d H:i:s'),$job['id']]);try{$details=instagramGraph('GET',$media,['fields'=>'permalink']);if(!empty($details['permalink']))query('UPDATE instagram_ad_posts SET permalink=? WHERE id=?',[$details['permalink'],$job['id']]);}catch(Throwable $e){}return ['published'=>1];}
 catch(Throwable $e){query("UPDATE instagram_ad_posts SET status='needs_review',last_error=? WHERE id=?",[mb_substr($e->getMessage(),0,500),$job['id']]);return ['published'=>0,'needs_review'=>1];}
}
