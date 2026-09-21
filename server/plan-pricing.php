<?php
function isFreePlanId(mixed $id): bool { return (int)$id===4; }
function isFreeBusiness(array $b): bool { return isFreePlanId($b['plan_id']??0); }
function pricedPlan(array $p): array {
 $p['is_free']=isFreePlanId($p['id']);$p['reviews_enabled']=!$p['is_free'];$now=date('Y-m-d H:i:s');$p['regular_price_cents']=(int)$p['price_cents'];$p['promotion_active']=!empty($p['promo_price_cents'])&&!empty($p['promo_start'])&&!empty($p['promo_end'])&&$p['promo_start']<=$now&&$p['promo_end']>$now;
 $p['price_cents']=$p['promotion_active']?(int)$p['promo_price_cents']:(int)$p['price_cents'];return $p;
}
function planCatalog(): array {return array_map('pricedPlan',query('SELECT * FROM plans ORDER BY id')->fetchAll());}
function priceInCents(mixed $value): int {if(!is_scalar($value)||!preg_match('/^\d{1,6}(?:\.\d{1,2})?$/',(string)$value))fail('Informe um preço com até duas casas decimais.');$cents=(int)round((float)$value*100);if($cents<100||$cents>99999900)fail('O preço deve estar entre R$ 1,00 e R$ 999.999,00.');return $cents;}
function savePlanPricing(int $id,array $d,int $admin): void {
 if(isFreePlanId($id))fail('O plano Free é gratuito e não permite alteração de preço.');
 $price=priceInCents($d['price']??'');$promo=null;$start=null;$end=null;
 if(!empty($d['promotion_enabled'])){$promo=priceInCents($d['promo_price']??'');if($promo>=$price)fail('O preço promocional deve ser menor que o preço normal.');$start=field($d,'promo_start',19,19);$end=field($d,'promo_end',19,19);foreach([$start,$end]as$date){$parsed=DateTimeImmutable::createFromFormat('!Y-m-d H:i:s',$date);if(!$parsed||$parsed->format('Y-m-d H:i:s')!==$date)fail('Informe datas válidas no formato AAAA-MM-DD HH:MM:SS.');}if($end<=$start||$end<=date('Y-m-d H:i:s'))fail('A promoção precisa terminar depois do início e no futuro.');}
 transaction(function()use($id,$price,$promo,$start,$end,$admin){query('UPDATE plans SET id=id WHERE id=?',[$id]);$old=one('SELECT * FROM plans WHERE id=?',[$id]);if(!$old)fail('Plano não encontrado.',404);query('UPDATE plans SET price_cents=?,promo_price_cents=?,promo_start=?,promo_end=? WHERE id=?',[$price,$promo,$start,$end,$id]);audit($admin,null,'plan_price_updated',json_encode(['plan_id'=>$id,'before'=>[$old['price_cents'],$old['promo_price_cents'],$old['promo_start'],$old['promo_end']],'after'=>[$price,$promo,$start,$end]],JSON_UNESCAPED_UNICODE));});
}
