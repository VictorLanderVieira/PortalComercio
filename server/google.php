<?php
function externalJson(string $url,string $method='GET',array $headers=[],?string $body=null): array {
 $ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>30,CURLOPT_HTTPHEADER=>$headers]);
 // Trust the Windows certificate store, including locally managed certificate authorities.
 // Peer and hostname verification remain enabled; Linux keeps its configured CA bundle.
 if(PHP_OS_FAMILY==='Windows'&&defined('CURLSSLOPT_NATIVE_CA'))curl_setopt($ch,CURLOPT_SSL_OPTIONS,CURLSSLOPT_NATIVE_CA);
 if($body!==null)curl_setopt($ch,CURLOPT_POSTFIELDS,$body);$raw=curl_exec($ch);$code=curl_getinfo($ch,CURLINFO_HTTP_CODE);$errno=curl_errno($ch);curl_close($ch);
 if($raw===false){if($errno===60)throw new RuntimeException('O servidor não conseguiu validar o certificado HTTPS do provedor. Verifique os certificados confiáveis do PHP.');throw new RuntimeException('Falha de conexão com o provedor (cURL '.$errno.'). Tente novamente.');}
 $data=json_decode($raw,true);
 if($code<200||$code>=300){$error=is_array($data)?($data['error']??''):'';if($error==='invalid_client')throw new RuntimeException('O Google recusou as credenciais. Confira o Client ID e o Client Secret no administrativo.');if($error==='invalid_grant')throw new RuntimeException('O código de login Google expirou ou já foi usado. Inicie o login novamente pelo portal.');throw new RuntimeException('Provedor externo indisponível (HTTP '.$code.').');}
 if(!is_array($data))throw new RuntimeException('Resposta externa inválida.');return $data;
}
function googleRoutes(string $path,string $method): void {
 if(!str_starts_with($path,'/api/auth/google/'))return;
 if(!googleLoginEnabled())fail('Login Google disponível após configurar as credenciais do portal.',503);
 $redirect=googleRedirectUri();
 if($path==='/api/auth/google/start'&&$method==='POST'){
  $d=input();if(empty($d['consent']))fail('Confirme a leitura da política de privacidade.');
  $returnTo=(string)($d['return_to']??'');if($returnTo!==''&&!preg_match('#^/#!/empresa/[1-9][0-9]*$#',$returnTo))fail('Destino de retorno inválido.');
  $state=bin2hex(random_bytes(32));$verifier=bin2hex(random_bytes(32));$_SESSION['google_oauth']=['state'=>$state,'verifier'=>$verifier,'created'=>time(),'client_id'=>env('GOOGLE_CLIENT_ID'),'redirect_uri'=>$redirect,'link_user'=>$_SESSION['user_id']??null,'return_to'=>$returnTo?:'/#!/painel'];
  $params=['client_id'=>env('GOOGLE_CLIENT_ID'),'redirect_uri'=>$redirect,'response_type'=>'code','scope'=>'openid email profile','state'=>$state,'code_challenge'=>rtrim(strtr(base64_encode(hash('sha256',$verifier,true)),'+/','-_'),'='),'code_challenge_method'=>'S256','prompt'=>'select_account'];
  jsonResponse(['url'=>'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query($params)]);
 }
 if($path==='/api/auth/google/callback'&&$method==='GET'){
  $flow=$_SESSION['google_oauth']??null;unset($_SESSION['google_oauth']);
  if(!$flow||time()-$flow['created']>600||!hash_equals($flow['state'],(string)($_GET['state']??'')))fail('Solicitação de login inválida ou expirada. Volte ao portal e clique em Entrar com Google; não abra o callback diretamente.',403);
  if(($flow['client_id']??'')!==env('GOOGLE_CLIENT_ID')||($flow['redirect_uri']??'')!==$redirect)fail('A configuração Google mudou. Inicie o login novamente.',409);
  if(!empty($_GET['error'])){header('Location: /#!/cadastro?google=cancelled');exit;}
  $code=(string)($_GET['code']??'');if(!$code||strlen($code)>4096)fail('Código Google inválido.');
  try{$token=externalJson('https://oauth2.googleapis.com/token','POST',['Content-Type: application/x-www-form-urlencoded'],http_build_query(['code'=>$code,'client_id'=>env('GOOGLE_CLIENT_ID'),'client_secret'=>env('GOOGLE_CLIENT_SECRET'),'redirect_uri'=>$redirect,'grant_type'=>'authorization_code','code_verifier'=>$flow['verifier']]));
  $profile=externalJson('https://openidconnect.googleapis.com/v1/userinfo','GET',['Authorization: Bearer '.$token['access_token']]);
  }catch(RuntimeException $e){error_log('Google OAuth: '.$e->getMessage());fail($e->getMessage(),502);}
  if(empty($profile['email_verified'])||empty($profile['sub'])||!filter_var($profile['email']??'',FILTER_VALIDATE_EMAIL))fail('Use uma conta Google com e-mail verificado.',403);
  $email=strtolower($profile['email']);if($flow['link_user']){$current=one('SELECT email FROM users WHERE id=?',[$flow['link_user']]);if(!$current||$current['email']!==$email)fail('Vincule uma conta Google com o mesmo e-mail do seu cadastro.',409);}$u=one('SELECT * FROM users WHERE google_sub=?',[$profile['sub']]);
  if(!$u){$u=one('SELECT * FROM users WHERE email=?',[$email]);if($u && (($flow['link_user']??null)!=$u['id']))fail('Este e-mail já possui cadastro. Entre com a senha e vincule o Google na seção Privacidade da conta.',409);if($u){query('UPDATE users SET google_sub=? WHERE id=?',[$profile['sub'],$u['id']]);}else{query('INSERT INTO users(name,email,password_hash,google_sub,created_at) VALUES(?,?,?,?,?)',[mb_substr($profile['name']??$email,0,100),$email,password_hash(bin2hex(random_bytes(40)),PASSWORD_DEFAULT),$profile['sub'],date('Y-m-d H:i:s')]);$u=one('SELECT * FROM users WHERE id=?',[db()->lastInsertId()]);}}
  if($flow['link_user'] && $flow['link_user']!=$u['id'])fail('A conta Google não corresponde à conta que você está vinculando.',409);
  session_regenerate_id(true);$_SESSION['user_id']=$u['id'];$_SESSION['csrf']=bin2hex(random_bytes(32));header('Location: '.$flow['return_to']);exit;
 }
 fail('Rota não encontrada.',404);
}
