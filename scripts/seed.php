<?php
require __DIR__.'/../server/bootstrap.php';
if(env('APP_ENV','local')!=='local')die("Demonstrações permitidas apenas no ambiente local.\n");
if(one('SELECT id FROM businesses LIMIT 1'))die("Banco já possui comércios; seed ignorado.\n");
$now=date('Y-m-d H:i:s');
$items=[
 ['Café da Praça',1,3,'Centro','Um café quentinho, pão de queijo saindo do forno e aquele acolhimento mineiro. Seu novo lugar favorito para uma pausa.','Café, prosa e bons encontros.','Rua da Praça, 120','Seg a sáb · 7h às 19h','cafe'],
 ['Studio Bela',3,3,'Centro','Cuidado e beleza para você se sentir bem. Cortes, coloração, manicure e tratamentos em um ambiente acolhedor.','Realçar a sua beleza, do seu jeito.','Rua das Flores, 45','Ter a sáb · 9h às 19h','beauty'],
 ['Empório da Terra',5,2,'Jardim Anchieta','Produtos frescos, quitandas e sabores da nossa região. Uma seleção feita com carinho para a sua mesa.','Valorizar os produtores e os sabores locais.','Av. Principal, 230','Seg a sáb · 8h às 20h','market'],
 ['Drogaria Bem Viver',8,2,'Centro','Atendimento próximo e uma equipe pronta para ajudar. Medicamentos, perfumaria e cuidado no seu dia a dia.','Cuidar de quem faz parte da nossa cidade.','Rua São José, 88','Seg a dom · 8h às 21h','pharmacy'],
 ['Oficina do João',4,1,'São Joaquim','Manutenção e revisão automotiva com atenção a cada detalhe. Agende uma avaliação para cuidar do seu carro.','Confiança em cada quilômetro.','Rua das Acácias, 310','Seg a sex · 8h às 18h','workshop'],
 ['Flor de Casa',6,1,'Brasília','Plantas, flores e detalhes que transformam sua casa. Arranjos para presentear e deixar a vida mais bonita.','Levar um pouco de natureza para perto.','Rua dos Ipês, 72','Seg a sáb · 9h às 18h','flowers']
];
foreach($items as $i=>$x){query('INSERT INTO users(name,email,password_hash,created_at) VALUES(?,?,?,?)',[$x[0],'demo'.($i+1).'@example.test',password_hash(bin2hex(random_bytes(20)),PASSWORD_DEFAULT),$now]);$uid=db()->lastInsertId();query("INSERT INTO businesses(user_id,category_id,plan_id,name,description,purpose,neighborhood,address,phone,hours,cover_url,status,paid_until,is_demo,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,'active',?,1,?)",[$uid,$x[1],$x[2],$x[0],$x[4],$x[5],$x[3],$x[6],'31999990000',$x[7],'/assets/'.$x[8].'.svg',date('Y-m-d H:i:s',strtotime('+1 year')),$now]);}
query('INSERT INTO users(name,email,password_hash,created_at) VALUES(?,?,?,?)',['Cliente de exemplo','cliente@example.test',password_hash(bin2hex(random_bytes(20)),PASSWORD_DEFAULT),$now]);$reviewer=db()->lastInsertId();foreach([1,2,3,4,5,6] as $id)query('INSERT INTO reviews(business_id,user_id,rating,comment,created_at) VALUES(?,?,?,?,?)',[$id,$reviewer,5,'Avaliação ilustrativa para demonstração do portal.',$now]);
echo "Seis comércios fictícios criados.\n";
