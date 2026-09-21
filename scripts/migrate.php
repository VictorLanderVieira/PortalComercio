<?php
require_once __DIR__.'/../server/bootstrap.php';
$sql=file_get_contents(ROOT.'/database/schema.sql');
if(env('DB_DRIVER','sqlite')==='mysql') $sql=str_replace('INTEGER PRIMARY KEY AUTOINCREMENT','INTEGER PRIMARY KEY AUTO_INCREMENT',$sql);
// Run once per empty database. Versioned migrations should be added for future releases.
if(one("SELECT 1 FROM ".(env('DB_DRIVER','sqlite')==='mysql' ? "information_schema.tables WHERE table_schema=DATABASE() AND table_name='users'" : "sqlite_master WHERE type='table' AND name='users'"))) { echo "Banco já inicializado.\n"; require __DIR__.'/upgrade.php'; exit; }
db()->exec($sql);
foreach([[1,'Alimentação','coffee'],[2,'Saúde e bem-estar','heart'],[3,'Beleza','sparkles'],[4,'Serviços','tool'],[5,'Compras','bag'],[6,'Casa e construção','home'],[7,'Autônomos','user'],[8,'Farmácias','plus']] as $c) query('INSERT INTO categories(id,name,icon) VALUES(?,?,?)',$c);
foreach([[1,'Essencial',5000,5,2,1,0],[2,'Destaque',7500,12,8,2,2],[3,'Premium',10000,25,20,3,5]] as $p) query('INSERT INTO plans(id,name,price_cents,photo_limit,post_limit,priority,promotion_limit) VALUES(?,?,?,?,?,?,?)',$p);
echo "Banco inicializado.\n";

foreach(['launch_start'=>'','launch_end'=>'','privacy_email'=>'','controller_name'=>'','support_whatsapp'=>''] as $k=>$v) query('INSERT INTO settings(setting_key,value) VALUES(?,?)',[$k,$v]);

require __DIR__.'/upgrade.php';
