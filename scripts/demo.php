<?php
require __DIR__.'/../server/bootstrap.php';
if(env('APP_ENV','local')!=='local')die("Apenas ambiente local.\n");
if(!one("SELECT id FROM users WHERE email='admin@sarzedo.local'")){
 $password=bin2hex(random_bytes(10));
 query("INSERT INTO users(name,email,password_hash,role,created_at) VALUES(?,?,?,'admin',?)",['Administrador local','admin@sarzedo.local',password_hash($password,PASSWORD_DEFAULT),date('Y-m-d H:i:s')]);
 file_put_contents(ROOT.'/storage/acesso-local.txt',"ACESSO EXCLUSIVO AO AMBIENTE LOCAL\nURL: http://localhost:8080/#!/admin\nE-mail: admin@sarzedo.local\nSenha: ".$password."\nNão publicar este arquivo nem reutilizar esta conta em produção.\n");
}
foreach([[1,'Café e pão de queijo para sua pausa','Uma combinação mineira para começar bem o dia. Oferta fictícia para apresentar o portal.',1290,1690,'cafe'],[2,'Um momento de cuidado para você','Corte e escova com atendimento agendado. Promoção ilustrativa, sem validade comercial.',6500,9000,'beauty'],[3,'Sabores frescos da nossa terra','Seleção de frutas e verduras da estação. Oferta ilustrativa para demonstração.',2990,3990,'market']] as $p){
 if(one('SELECT id FROM promotions WHERE business_id=?',[$p[0]]))continue;
 query('INSERT INTO promotions(business_id,title,description,price_cents,original_price_cents,image_url,starts_at,expires_at,created_at) VALUES(?,?,?,?,?,?,?,?,?)',[$p[0],$p[1],$p[2],$p[3],$p[4],'/assets/'.$p[5].'.svg',date('Y-m-d H:i:s'),date('Y-m-d H:i:s',strtotime('+30 days')),date('Y-m-d H:i:s')]);
}
echo "Demonstração preparada. Acesso administrativo em storage/acesso-local.txt.\n";
