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
