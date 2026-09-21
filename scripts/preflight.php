<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../server/bootstrap.php';
$errors=[];
if(PHP_VERSION_ID<80300)$errors[]='Use PHP 8.3 ou superior.';
foreach(['pdo_mysql','gd','curl','mbstring','fileinfo','openssl'] as $ext)if(!extension_loaded($ext))$errors[]='Extensão ausente: '.$ext;
if(env('APP_ENV')!=='production')$errors[]='APP_ENV deve ser production.';
if(!in_array(env('DEPLOY_ENV'),['staging','production']))$errors[]='Defina DEPLOY_ENV como staging ou production.';
if(env('DB_DRIVER')!=='mysql')$errors[]='A publicação está preparada para MySQL.';
if(!filter_var(env('APP_URL'),FILTER_VALIDATE_URL)||parse_url(env('APP_URL'),PHP_URL_SCHEME)!=='https'||str_contains(env('APP_URL'),'SEU-DOMINIO'))$errors[]='Configure APP_URL com seu domínio HTTPS definitivo.';
if(env('PAYMENT_DRIVER')==='demo')$errors[]='Pagamento de demonstração proibido em produção.';
foreach(['storage','public/uploads']as$dir)if(!is_dir(ROOT.'/'.$dir)||!is_writable(ROOT.'/'.$dir))$errors[]='Diretório sem escrita: '.$dir;
try{db()->query('SELECT 1');if(!in_array('--before-migration',$argv,true)){if(!one('SELECT version FROM schema_migrations WHERE version=11'))$errors[]='Migrações pendentes.';query('SELECT free_cover_key,reviews_opt_in FROM businesses LIMIT 1');}}catch(Throwable $e){$errors[]='Falha na conexão ou estrutura do banco. Confira credenciais e migrações (detalhes omitidos).';}
foreach($errors as$error)fwrite(STDERR,$error."\n");
if($errors)exit(1);
echo "Verificação de produção concluída.\n";
