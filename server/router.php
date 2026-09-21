<?php
$path=rawurldecode(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH));
$root=realpath(__DIR__.'/../public');
if(str_starts_with($path,'/api/')) { require __DIR__.'/api.php'; return true; }
$file=realpath($root.$path);
if($file && str_starts_with($file,$root.DIRECTORY_SEPARATOR) && is_file($file) && !preg_match('/\.(php|env|sql)$/i',$file)) return false;
if($path==='/' || $path==='/index.html') { readfile($root.'/index.html'); return true; }
http_response_code(404); echo 'Não encontrado';
