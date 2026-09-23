<?php
declare(strict_types=1);
date_default_timezone_set('America/Sao_Paulo');
const ROOT = __DIR__ . '/..';
if (is_file(ROOT . '/.env')) {
    foreach (file(ROOT . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        if (getenv(trim($key)) === false) putenv(trim($key) . '=' . trim($value, " \t\n\r\0\x0B\"'"));
    }
}
require_once __DIR__.'/integration-config.php';
require_once __DIR__.'/google-config.php';
require_once __DIR__.'/plan-pricing.php';
function manualNotifications(): bool { return getenv('NOTIFICATION_MODE') !== 'automatic'; }
function env(string $key, string $default = ''): string { if($key==='NOTIFICATIONS_ENABLED'&&manualNotifications())return 'false';if(getenv('DEPLOY_ENV')==='staging'){if($key==='PAYMENT_DRIVER')return 'disabled';if(in_array($key,['NOTIFICATIONS_ENABLED','ANALYTICS_ENABLED']))return 'false';} $config=integrationConfig();return array_key_exists($key,$config)?(string)$config[$key]:(getenv($key) === false ? $default : (string)getenv($key)); }
function db(): PDO {
    static $db;
    if ($db) return $db;
    $dsn = env('DB_DRIVER', 'sqlite') === 'mysql'
        ? 'mysql:host='.env('DB_HOST','127.0.0.1').';port='.env('DB_PORT','3306').';dbname='.env('DB_DATABASE','sarzedo').';charset=utf8mb4'
        : 'sqlite:'.env('SQLITE_PATH', ROOT.'/storage/portal.sqlite');
    $db = new PDO($dsn, env('DB_USERNAME'), env('DB_PASSWORD'), [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    if (env('DB_DRIVER','sqlite') === 'sqlite') { $db->exec('PRAGMA foreign_keys=ON; PRAGMA busy_timeout=5000;'); }
    return $db;
}
function query(string $sql, array $params=[]): PDOStatement { $q=db()->prepare($sql); $q->execute($params); return $q; }
function one(string $sql, array $params=[]): ?array { return query($sql,$params)->fetch() ?: null; }
function jsonResponse(mixed $data, int $code=200): never { http_response_code($code); header('Content-Type: application/json; charset=utf-8'); echo json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }
function fail(string $message, int $code=422): never { jsonResponse(['error'=>$message],$code); }
function input(): array { $data=json_decode(file_get_contents('php://input'),true); if (!is_array($data)) fail('Envie um formulário válido.'); return $data; }
function field(array $data, string $key, int $min=0, int $max=255): string { $v=trim((string)($data[$key]??'')); if (mb_strlen($v)<$min || mb_strlen($v)>$max) fail("Campo $key: informe entre $min e $max caracteres."); return $v; }
function user(): array { if (empty($_SESSION['user_id'])) fail('Entre na sua conta para continuar.',401); return one('SELECT id,name,email,role FROM users WHERE id=?',[$_SESSION['user_id']]) ?? fail('Conta não encontrada.',401); }
function business(): array { $u=user(); return one('SELECT * FROM businesses WHERE user_id=?',[$u['id']]) ?? fail('Cadastre sua empresa primeiro.',404); }
function validUntil(array $b): string { if(isFreeBusiness($b))return ''; return max($b['paid_until']??'', $b['trial_until']??'', $b['manual_until']??''); }
function freeDisplayUntil(array $b): string { $base=isFreeBusiness($b)?($b['created_at']??''):validUntil($b);return $base?date('Y-m-d H:i:s',strtotime($base.(isFreeBusiness($b)?' +30 days':' +5 days'))):''; }
function isLive(array $b): bool { return $b['status']!=='suspended' && (isFreeBusiness($b)?freeDisplayUntil($b):validUntil($b))>date('Y-m-d H:i:s'); }
function suspendedByExpiry(array $b): bool { return !isFreeBusiness($b) && validUntil($b)!=='' && freeDisplayUntil($b)<=date('Y-m-d H:i:s'); }
function hasFreeDisplay(array $b): bool { return $b['status']!=='suspended' && freeDisplayUntil($b)>date('Y-m-d H:i:s') && (isFreeBusiness($b)||!isLive($b)); }
function isListed(array $b): bool { return ($b['approval_status']??'pending')==='approved' && (isLive($b) || hasFreeDisplay($b) || ($b['status']==='pending' && !validUntil($b) && ($b['activation_deadline']??'')>date('Y-m-d H:i:s'))); }
function visibilitySql(string $alias='b', bool $grace=true): string {
 $now="'".date('Y-m-d H:i:s')."'";
 $add=function($column,$days=30){return env('DB_DRIVER','sqlite')==='mysql'?"DATE_ADD($column, INTERVAL $days DAY)":"datetime($column, '+$days days')";};
 $live="($alias.plan_id<>4 AND ($alias.paid_until>? OR $alias.trial_until>? OR $alias.manual_until>?))";
 $free="($alias.plan_id=4 AND ".$add("$alias.created_at").">$now)";
 $fallback="($alias.plan_id<>4 AND (".$add("$alias.paid_until",5).">$now OR ".$add("$alias.trial_until",5).">$now OR ".$add("$alias.manual_until",5).">$now))";
 return "$alias.approval_status='approved' AND $alias.status<>'suspended' AND ($live OR $free".($grace?" OR $fallback OR ($alias.plan_id<>4 AND $alias.status='pending' AND $alias.trial_until IS NULL AND $alias.paid_until IS NULL AND $alias.manual_until IS NULL AND $alias.activation_deadline>?)":"").")";
}
function visibilityParams(bool $grace=true): array { return array_fill(0,$grace?4:3,date('Y-m-d H:i:s')); }
function settings(): array { $s=[]; foreach(query('SELECT * FROM settings')->fetchAll() as $r)$s[$r['setting_key']]=$r['value']; return $s; }
function freeCoverLibrary(): array { $labels=['neighborhood'=>'Comércio local','pharmacy'=>'Farmácia e saúde','cafe'=>'Café e alimentação','market'=>'Mercado e compras','beauty'=>'Beleza','workshop'=>'Oficina e serviços','flowers'=>'Flores e jardinagem','fitness'=>'Academia e personal trainer'];$items=[];foreach($labels as $key=>$label)$items[]=['key'=>$key,'label'=>$label,'url'=>'/assets/'.$key.'.svg'];return $items; }
function freeCoverUrl(array $b): string { $key=$b['free_cover_key']??'';if(!in_array($key,array_column(freeCoverLibrary(),'key'),true))$key=[1=>'cafe',2=>'pharmacy',3=>'beauty',4=>'workshop',5=>'market',8=>'pharmacy',9=>'fitness'][(int)($b['category_id']??0)]??'neighborhood';return '/assets/'.$key.'.svg'; }
function businessCode(int $id): string { return 'SZ-'.str_pad((string)$id,6,'0',STR_PAD_LEFT); }
function businessOpenNow(array $b): ?bool { $schedule=json_decode((string)($b['hours_schedule']??''),true);if(!is_array($schedule)||!$schedule)return null;$day=['mon','tue','wed','thu','fri','sat','sun'][(int)date('N')-1];$hours=$schedule[$day]??null;if(!is_array($hours))return false;$time=date('H:i');return !empty($hours['open'])&&!empty($hours['close'])&&$time>=$hours['open']&&$time<$hours['close']; }
function publicBusiness(array $b): array { $b['open_now']=businessOpenNow($b);$b['business_code']=businessCode((int)$b['id']); $b['is_free']=isFreeBusiness($b)||hasFreeDisplay($b);$b['reviews_enabled']=!$b['is_free']&&!empty($b['reviews_opt_in']);if(!$b['reviews_enabled']){$b['rating']=0;$b['review_count']=0;}if($b['is_free']){$b['rating']=0;$b['review_count']=0;$b['logo_url']='';$b['cover_url']=freeCoverUrl($b);$b['plan']='Free';$b['priority']=0;}elseif(!empty($b['free_cover_key'])||(empty($b['cover_url'])&&empty($b['logo_url']))){$b['cover_url']=freeCoverUrl($b);} $b['contact_available']=isLive($b)||hasFreeDisplay($b);$b['is_demo']=(bool)(int)$b['is_demo'];$b['priority']=$b['contact_available']?(int)($b['priority']??0):0; if(!$b['contact_available']) { foreach(['phone','website','instagram','facebook_url','maps_url','address','hours'] as $key)$b[$key]=''; } if(empty($b['publish_address'])) { $b['address']=''; $b['maps_url']=''; } foreach(['user_id','provider_customer_id','whatsapp_consent','consent_at','activation_deadline','paid_until','trial_until','manual_until'] as $key)unset($b[$key]); return $b; }
function urlField(array $data, string $key): string { $url=field($data,$key,0,500); if ($url && (!filter_var($url,FILTER_VALIDATE_URL) || !in_array(parse_url($url,PHP_URL_SCHEME),['https','http']))) fail('Use um link completo começando com https://.'); return $url; }
function transaction(callable $fn): mixed { db()->beginTransaction(); try { $v=$fn(); db()->commit(); return $v; } catch (Throwable $e) { if(db()->inTransaction()) db()->rollBack(); throw $e; } }
function lockBusiness(int $id): void { query('UPDATE businesses SET id=id WHERE id=?',[$id]); }
function nextMonth(string $date): string { $d=new DateTimeImmutable($date); $next=$d->modify('first day of next month'); return $next->setDate((int)$next->format('Y'),(int)$next->format('m'),min((int)$d->format('d'),(int)$next->format('t')))->format('Y-m-d H:i:s'); }
