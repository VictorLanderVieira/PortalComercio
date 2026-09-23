<?php
function metricsEnabled(): bool {return env('ANALYTICS_ENABLED','true')==='true';}
function publicMetrics(): array {$row=one('SELECT visits,started_at FROM portal_metric_totals WHERE id=1');return ['visits'=>(int)($row['visits']??0),'started_at'=>$row['started_at']??null,'enabled'=>metricsEnabled()];}
function adminMetrics(): array {$totals=one('SELECT visits,searches,started_at FROM portal_metric_totals WHERE id=1');$days=query('SELECT * FROM portal_metric_days WHERE metric_date>=? ORDER BY metric_date',[date('Y-m-d',strtotime('-29 days'))])->fetchAll();$today=['visits'=>0,'searches'=>0];$month=$today;foreach($days as $d){$month['visits']+=(int)$d['visits'];$month['searches']+=(int)$d['searches'];if($d['metric_date']===date('Y-m-d'))$today=['visits'=>(int)$d['visits'],'searches'=>(int)$d['searches']];}return ['enabled'=>metricsEnabled(),'totals'=>$totals,'today'=>$today,'last30'=>$month];}
function recordPortalMetric(array $data): void {
 $type=$data['type']??'';if(!in_array($type,['visit','search'],true))fail('Tipo de indicador inválido.');
 if(!metricsEnabled()||preg_match('/bot|crawler|spider|headless|preview/i',$_SERVER['HTTP_USER_AGENT']??''))return;
 if(!empty($_SESSION['user_id'])&&(one('SELECT role FROM users WHERE id=?',[$_SESSION['user_id']])['role']??'')==='admin')return;
 $now=time();$last=(int)($_SESSION['metric_activity']??0);$visit=(!$last||$now-$last>=1800)?1:0;$search=$type==='search'&&$now-(int)($_SESSION['metric_search']??0)>=5?1:0;
 if($visit||$search)transaction(function()use($visit,$search){query('UPDATE portal_metric_totals SET visits=visits+?,searches=searches+? WHERE id=1',[$visit,$search]);$date=date('Y-m-d');if(one('SELECT metric_date FROM portal_metric_days WHERE metric_date=?',[$date]))query('UPDATE portal_metric_days SET visits=visits+?,searches=searches+? WHERE metric_date=?',[$visit,$search,$date]);else query('INSERT INTO portal_metric_days(metric_date,visits,searches) VALUES(?,?,?)',[$date,$visit,$search]);});
 $_SESSION['metric_activity']=$now;if($search)$_SESSION['metric_search']=$now;
}
function businessMetricSummary(int $businessId): array {
 $row=one('SELECT COALESCE(SUM(profile_views),0) profile_views,COALESCE(SUM(whatsapp_clicks),0) whatsapp_clicks,COALESCE(SUM(map_clicks),0) map_clicks,COALESCE(SUM(offer_clicks),0) offer_clicks FROM business_metric_days WHERE business_id=? AND metric_date>=?',[$businessId,date('Y-m-d',strtotime('-29 days'))]);
 return array_map('intval',$row?:['profile_views'=>0,'whatsapp_clicks'=>0,'map_clicks'=>0,'offer_clicks'=>0]);
}
function recordBusinessMetric(int $businessId,array $data): void {
 $columns=['profile_view'=>'profile_views','whatsapp_click'=>'whatsapp_clicks','map_click'=>'map_clicks','offer_click'=>'offer_clicks'];$type=$data['type']??'';
 if(!is_string($type)||!isset($columns[$type]))fail('Tipo de interação inválido.');
 $b=one('SELECT * FROM businesses WHERE id=?',[$businessId]);if(!$b||!isListed($b)||!isLive($b)||$b['is_demo'])fail('Empresa indisponível.',404);
 if(!metricsEnabled()||preg_match('/bot|crawler|spider|headless|preview/i',$_SERVER['HTTP_USER_AGENT']??''))return;
 if(!empty($_SESSION['user_id'])){$u=one('SELECT role FROM users WHERE id=?',[$_SESSION['user_id']]);if(($u['role']??'')==='admin'||(int)$b['user_id']===(int)$_SESSION['user_id'])return;}
 $key='business_metric_'.$businessId.'_'.$type;$now=time();$last=(int)($_SESSION[$key]??0);$period=$type==='profile_view'?86400:30;if($now-$last<$period)return;
 $driver=env('DB_DRIVER','sqlite');$insert=$driver==='mysql'?'INSERT IGNORE INTO business_metric_days(business_id,metric_date) VALUES(?,?)':'INSERT OR IGNORE INTO business_metric_days(business_id,metric_date) VALUES(?,?)';query($insert,[$businessId,date('Y-m-d')]);query('UPDATE business_metric_days SET '.$columns[$type].'='.$columns[$type].'+1 WHERE business_id=? AND metric_date=?',[$businessId,date('Y-m-d')]);$_SESSION[$key]=$now;
}
