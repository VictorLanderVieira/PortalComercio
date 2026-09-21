<?php
// Private configuration stays outside the public directory and public settings API.
function integrationConfigPath(): string { $db=getenv('SQLITE_PATH');return $db ? $db.'.integrations.json' : ROOT.'/storage/integrations.json'; }
function integrationConfig(): array { $file=integrationConfigPath();if(!is_file($file))return []; $data=json_decode(file_get_contents($file),true);return is_array($data)?$data:[]; }
function integrationFields(): array { return ['RESEND_API_KEY','EMAIL_FROM','TWILIO_ACCOUNT_SID','TWILIO_API_KEY','TWILIO_API_SECRET','TWILIO_WHATSAPP_FROM','TWILIO_CONTENT_SID']; }
function integrationStatus(): array { $configured=[];foreach(integrationFields() as $key)$configured[$key]=env($key)!=='';return ['manual'=>manualNotifications(),'configured'=>$configured,'automation'=>env('NOTIFICATIONS_ENABLED','false')==='true','email'=>$configured['RESEND_API_KEY']&&$configured['EMAIL_FROM'],'whatsapp'=>$configured['TWILIO_ACCOUNT_SID']&&$configured['TWILIO_API_KEY']&&$configured['TWILIO_API_SECRET']&&$configured['TWILIO_WHATSAPP_FROM']&&$configured['TWILIO_CONTENT_SID'],'google'=>googleLoginEnabled()]; }
function saveIntegrationConfig(array $d,int $admin): void {
 if(manualNotifications())fail('Esta versão utiliza avisos manuais pelo WhatsApp. A automação está desativada.',409);
 $file=integrationConfigPath();$lock=fopen($file.'.lock','c');if(!$lock||!flock($lock,LOCK_EX))fail('Não foi possível bloquear a configuração.',500);
 try { $config=integrationConfig();foreach(integrationFields() as $key){$value=field($d,$key,0,500);if($value==='')continue;if(preg_match('/[\r\n\x00]/',$value))fail('Configuração contém caracteres inválidos.');if($key==='EMAIL_FROM'&&!filter_var($value,FILTER_VALIDATE_EMAIL))fail('Informe apenas o endereço do remetente verificado.');if($key==='TWILIO_WHATSAPP_FROM'&&!preg_match('/^whatsapp:\+[1-9][0-9]{7,14}$/',$value))fail('Use whatsapp:+ seguido do número internacional.');$config[$key]=$value;}
 $config['NOTIFICATIONS_ENABLED']=!empty($d['automation'])?'true':'false';
 $get=fn($key)=>$config[$key]??env($key);
 $email=$get('RESEND_API_KEY')&&$get('EMAIL_FROM');$whatsapp=$get('TWILIO_ACCOUNT_SID')&&$get('TWILIO_API_KEY')&&$get('TWILIO_API_SECRET')&&$get('TWILIO_WHATSAPP_FROM')&&$get('TWILIO_CONTENT_SID');
 if($config['NOTIFICATIONS_ENABLED']==='true'&&!$email&&!$whatsapp)fail('Configure ao menos um canal completo antes de habilitar a automação.');
 $tmp=tempnam(dirname($file),'integration-');if($tmp===false)fail('Não foi possível salvar a configuração.',500);chmod($tmp,0600);if(file_put_contents($tmp,json_encode($config,JSON_THROW_ON_ERROR),LOCK_EX)===false||!rename($tmp,$file)){@unlink($tmp);fail('Não foi possível salvar a configuração.',500);}chmod($file,0600);audit($admin,null,'integrations_updated','Configuração dos canais de avisos atualizada; credenciais omitidas.');
 }finally{flock($lock,LOCK_UN);fclose($lock);}
}
