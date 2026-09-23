<?php
if(!defined('ROOT'))require __DIR__.'/../server/bootstrap.php';
query('CREATE TABLE IF NOT EXISTS schema_migrations (version INTEGER PRIMARY KEY, applied_at DATETIME NOT NULL)');
if(!one('SELECT version FROM schema_migrations WHERE version=2')){
 $sql=preg_replace('/^\xEF\xBB\xBF/','',file_get_contents(ROOT.'/database/002-management.sql'));
 if(env('DB_DRIVER','sqlite')==='mysql')$sql=str_replace('INTEGER PRIMARY KEY AUTOINCREMENT','INTEGER PRIMARY KEY AUTO_INCREMENT',$sql);
 db()->exec($sql);query('INSERT INTO schema_migrations VALUES(2,?)',[date('Y-m-d H:i:s')]);echo "Migração gerencial aplicada.\n";
}

if(!one('SELECT version FROM schema_migrations WHERE version=3')){db()->exec(file_get_contents(ROOT.'/database/003-plan-pricing.sql'));query('INSERT INTO schema_migrations VALUES(3,?)',[date('Y-m-d H:i:s')]);echo "Migração de preços dos planos aplicada.\n";}

if(!one('SELECT version FROM schema_migrations WHERE version=4')){db()->exec(file_get_contents(ROOT.'/database/004-manual-pix.sql'));query('INSERT INTO schema_migrations VALUES(4,?)',[date('Y-m-d H:i:s')]);echo "Migração de Pix manual aplicada.\n";}

if(!one('SELECT version FROM schema_migrations WHERE version=5')){db()->exec(file_get_contents(ROOT.'/database/005-portal-metrics.sql'));query('INSERT INTO portal_metric_totals(id,started_at) VALUES(1,?)',[date('Y-m-d H:i:s')]);query('INSERT INTO schema_migrations VALUES(5,?)',[date('Y-m-d H:i:s')]);echo "Migração de indicadores do portal aplicada.\n";}

if(!one('SELECT version FROM schema_migrations WHERE version=6')){db()->exec(file_get_contents(ROOT.'/database/006-free-plan.sql'));query('INSERT INTO schema_migrations VALUES(6,?)',[date('Y-m-d H:i:s')]);echo "Plano Free adicionado.\n";}

if(!one('SELECT version FROM schema_migrations WHERE version=7')){db()->exec(file_get_contents(ROOT.'/database/007-support-whatsapp.sql'));query('INSERT INTO schema_migrations VALUES(7,?)',[date('Y-m-d H:i:s')]);echo "Contato de ativação atualizado.\n";}

if(!one('SELECT version FROM schema_migrations WHERE version=8')){db()->exec(file_get_contents(ROOT.'/database/008-review-preference.sql'));query('INSERT INTO schema_migrations VALUES(8,?)',[date('Y-m-d H:i:s')]);echo "Preferência de avaliações adicionada.\n";}

if(!one('SELECT version FROM schema_migrations WHERE version=9')){db()->exec(file_get_contents(ROOT.'/database/009-free-cover-library.sql'));query('INSERT INTO schema_migrations VALUES(9,?)',[date('Y-m-d H:i:s')]);echo "Biblioteca Free e categoria de atividade física adicionadas.\n";}

if(!one('SELECT version FROM schema_migrations WHERE version=10')){$sql=file_get_contents(ROOT.'/database/010-advertisements.sql');if(env('DB_DRIVER','sqlite')==='mysql')$sql=str_replace('INTEGER PRIMARY KEY AUTOINCREMENT','INTEGER PRIMARY KEY AUTO_INCREMENT',$sql);db()->exec($sql);query('INSERT INTO schema_migrations VALUES(10,?)',[date('Y-m-d H:i:s')]);echo "Publicidade avulsa adicionada.\n";}

if(!one('SELECT version FROM schema_migrations WHERE version=11')){db()->exec(file_get_contents(ROOT.'/database/011-business-approval.sql'));query('INSERT INTO schema_migrations VALUES(11,?)',[date('Y-m-d H:i:s')]);echo "Aprovação administrativa e categorias adicionadas.\n";}

if(!one('SELECT version FROM schema_migrations WHERE version=12')){$sql=file_get_contents(ROOT.'/database/012-promotion-images.sql');if(env('DB_DRIVER','sqlite')==='mysql')$sql=str_replace('INTEGER PRIMARY KEY AUTOINCREMENT','INTEGER PRIMARY KEY AUTO_INCREMENT',$sql);db()->exec($sql);query('INSERT INTO schema_migrations VALUES(12,?)',[date('Y-m-d H:i:s')]);echo "Imagens próprias de promoções adicionadas.\n";}

if(!one('SELECT version FROM schema_migrations WHERE version=13')){db()->exec(file_get_contents(ROOT.'/database/013-support-whatsapp.sql'));query('INSERT INTO schema_migrations VALUES(13,?)',[date('Y-m-d H:i:s')]);echo "WhatsApp de atendimento atualizado.\n";}

if(!one('SELECT version FROM schema_migrations WHERE version=14')){db()->exec(file_get_contents(ROOT.'/database/014-business-minisite.sql'));query('INSERT INTO schema_migrations VALUES(14,?)',[date('Y-m-d H:i:s')]);echo "Conteúdo do minisite empresarial adicionado.\n";}

if(!one('SELECT version FROM schema_migrations WHERE version=15')){db()->exec(file_get_contents(ROOT.'/database/015-advertisement-creative-mode.sql'));query('INSERT INTO schema_migrations VALUES(15,?)',[date('Y-m-d H:i:s')]);echo "Modos de criação de publicidade adicionados.\n";}

if(!one('SELECT version FROM schema_migrations WHERE version=16')){db()->exec(file_get_contents(ROOT.'/database/016-maps-embed-key.sql'));query('INSERT INTO schema_migrations VALUES(16,?)',[date('Y-m-d H:i:s')]);echo "Configuração do mapa incorporado adicionada.\n";}

if(!one('SELECT version FROM schema_migrations WHERE version=17')){db()->exec(file_get_contents(ROOT.'/database/017-review-moderation.sql'));query('INSERT INTO schema_migrations VALUES(17,?)',[date('Y-m-d H:i:s')]);echo "Moderação de avaliações adicionada.\n";}
