<?php
require __DIR__.'/../server/bootstrap.php';
$email=strtolower(trim(getenv('ADMIN_EMAIL')?:''));$password=getenv('ADMIN_PASSWORD')?:'';
if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<12)die("Defina ADMIN_EMAIL e ADMIN_PASSWORD (mínimo 12 caracteres) no ambiente.\n");
if(one('SELECT id FROM users WHERE email=?',[$email]))die("Este e-mail já existe. Atribuição de administrador deve ser revisada no banco.\n");
query("INSERT INTO users(name,email,password_hash,role,created_at) VALUES(?,?,?,'admin',?)",['Administrador',$email,password_hash($password,PASSWORD_DEFAULT),date('Y-m-d H:i:s')]);
echo "Administrador criado. Nenhuma senha foi registrada em log.\n";
