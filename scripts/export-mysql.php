<?php
$root=__DIR__.'/..';
$sql=file_get_contents($root.'/database/schema.sql')."\n".preg_replace('/^\xEF\xBB\xBF/','',file_get_contents($root.'/database/002-management.sql'));
$sql.="\n".file_get_contents($root.'/database/003-plan-pricing.sql');
$sql.="\n".file_get_contents($root.'/database/004-manual-pix.sql');
$sql.="\n".file_get_contents($root.'/database/005-portal-metrics.sql');
$sql.="\n".file_get_contents($root.'/database/006-free-plan.sql');
$sql.="\n".file_get_contents($root.'/database/008-review-preference.sql');
$sql.="\n".file_get_contents($root.'/database/009-free-cover-library.sql');
$sql=str_replace('INTEGER PRIMARY KEY AUTOINCREMENT','INTEGER PRIMARY KEY AUTO_INCREMENT',$sql);
echo "-- Sarzedo por perto: MySQL 8, banco vazio.\nSET NAMES utf8mb4;\n".$sql;
echo "\nINSERT INTO categories VALUES (1,'Alimentação','coffee'),(2,'Saúde e bem-estar','heart'),(3,'Beleza','sparkles'),(4,'Serviços','tool'),(5,'Compras','bag'),(6,'Casa e construção','home'),(7,'Autônomos','user'),(8,'Farmácias','plus');\n";
echo "INSERT INTO plans(id,name,price_cents,photo_limit,post_limit,priority,promotion_limit) VALUES (1,'Essencial',5000,5,2,1,0),(2,'Destaque',7500,12,8,2,2),(3,'Premium',10000,25,20,3,5);\n";
echo "INSERT INTO settings VALUES ('launch_start',''),('launch_end',''),('privacy_email',''),('controller_name',''),('support_whatsapp','31987671102');\nCREATE TABLE IF NOT EXISTS schema_migrations (version INTEGER PRIMARY KEY, applied_at DATETIME NOT NULL);\nINSERT INTO schema_migrations VALUES(2,NOW()),(3,NOW()),(4,NOW()),(5,NOW()),(6,NOW()),(7,NOW()),(8,NOW()),(9,NOW());\nINSERT INTO portal_metric_totals(id,started_at) VALUES(1,NOW());\n";

