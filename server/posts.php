<?php
function postsWithImages(array $posts,?int $imageLimit=null): array {
 if(!$posts)return [];
 $ids=array_map(fn($post)=>(int)$post['id'],$posts);
 $placeholders=implode(',',array_fill(0,count($ids),'?'));
 $images=query("SELECT id,post_id,url,position FROM post_images WHERE post_id IN ($placeholders) ORDER BY post_id,position,id",$ids)->fetchAll();
 $byPost=[];foreach($images as $image)$byPost[(int)$image['post_id']][]=['id'=>(int)$image['id'],'url'=>$image['url']];
 foreach($posts as &$post){$gallery=$byPost[(int)$post['id']]??[];if(!$gallery&&!empty($post['image_url']))$gallery=[['url'=>$post['image_url']]];if($imageLimit!==null)$gallery=array_slice($gallery,0,$imageLimit);$post['images']=$gallery;$post['image_url']=$gallery[0]['url']??'';}
 return $posts;
}
function postUploadFiles(array $files): array {
 if(isset($files['photos'])){
  $group=$files['photos'];if(!is_array($group['name']??null))fail('Selecione imagens válidas.');
  $items=[];foreach($group['name'] as $i=>$name)$items[]=['name'=>$name,'error'=>$group['error'][$i]??null,'size'=>$group['size'][$i]??null,'tmp_name'=>$group['tmp_name'][$i]??null];
  return $items;
 }
 return isset($files['photo'])?[$files['photo']]:[];
}
function validatePostUploads(array $uploads): void {
 foreach($uploads as $upload){
  if(!is_int($upload['error']??null)||$upload['error']!==UPLOAD_ERR_OK||($upload['size']??0)>5*1024*1024||($upload['size']??0)<1)fail('Envie imagens JPG, PNG ou WebP de até 5 MB cada.');
  $info=@getimagesize($upload['tmp_name']);if(!$info||!in_array($info['mime'],['image/jpeg','image/png','image/webp'],true)||$info[0]*$info[1]>25000000)fail('Imagem inválida ou superior a 25 megapixels.');
 }
}
function savePostUpload(array $upload): string {
 $info=getimagesize($upload['tmp_name']);$source=@imagecreatefromstring(file_get_contents($upload['tmp_name']));if(!$source)fail('Não foi possível abrir a imagem.');
 $scale=min(1,1600/max($info[0],$info[1]));$width=(int)round($info[0]*$scale);$height=(int)round($info[1]*$scale);
 $image=imagecreatetruecolor($width,$height);imagefill($image,0,0,imagecolorallocate($image,255,255,255));imagecopyresampled($image,$source,0,0,0,0,$width,$height,$info[0],$info[1]);imagedestroy($source);
 $url='/uploads/'.bin2hex(random_bytes(16)).'.jpg';$ok=imagejpeg($image,ROOT.'/public'.$url,85);imagedestroy($image);if(!$ok)fail('Não foi possível salvar a imagem.');return $url;
}
function createBusinessPost(array $business,array $data,array $files): void {
 if(!isLive($business))fail('Ative seu plano antes de publicar.');
 $title=field($data,'title',3,100);$body=field($data,'body',10,1500);
 $type=(string)($data['listing_type']??'novidade');if(!in_array($type,['novidade','produto','servico','imovel'],true))fail('Tipo de publicação inválido.');
 $priceRaw=$data['price']??'';$price=$priceRaw===''?null:priceInCents($priceRaw);
 $uploads=postUploadFiles($files);$plan=one('SELECT * FROM plans WHERE id=?',[$business['plan_id']]);
 if(count($uploads)>(int)$plan['post_image_limit'])fail('Seu plano permite até '.$plan['post_image_limit'].' foto(s) por publicação.');
 validatePostUploads($uploads);
 $saved=[];
 try{
  transaction(function()use($business,$title,$body,$type,$price,$uploads,&$saved){
   lockBusiness((int)$business['id']);$plan=one('SELECT * FROM plans WHERE id=?',[$business['plan_id']]);
   $where=isFreeBusiness($business)?'business_id=?':'business_id=? AND created_at>=?';$params=isFreeBusiness($business)?[$business['id']]:[$business['id'],date('Y-m-01 00:00:00')];
   $count=(int)one("SELECT COUNT(*) n FROM posts WHERE $where",$params)['n'];
   if($count>=(int)$plan['post_limit'])fail(isFreeBusiness($business)?'O plano Free permite uma publicação durante os 30 dias.':'Limite mensal de publicações atingido.');
   foreach($uploads as $upload)$saved[]=savePostUpload($upload);
   query('INSERT INTO posts(business_id,title,body,image_url,listing_type,price_cents,created_at) VALUES(?,?,?,?,?,?,?)',[$business['id'],$title,$body,$saved[0]??'',$type,$price,date('Y-m-d H:i:s')]);$postId=(int)db()->lastInsertId();
   foreach($saved as $position=>$url)query('INSERT INTO post_images(post_id,url,position) VALUES(?,?,?)',[$postId,$url,$position]);
  });
 }catch(Throwable $error){foreach($saved as $url)if(is_file(ROOT.'/public'.$url))@unlink(ROOT.'/public'.$url);throw $error;}
}
function addPostPhotos(array $business,int $postId,array $files): void {
 $uploads=postUploadFiles($files);if(!$uploads)fail('Selecione ao menos uma foto.');validatePostUploads($uploads);
 $saved=[];
 try{transaction(function()use($business,$postId,$uploads,&$saved){
  lockBusiness((int)$business['id']);$post=one('SELECT id FROM posts WHERE id=? AND business_id=?',[$postId,$business['id']]);if(!$post)fail('Publicação não encontrada.',404);
  if(!isLive($business))fail('Ative seu plano antes de alterar fotos.');
  $plan=one('SELECT post_image_limit FROM plans WHERE id=?',[$business['plan_id']]);$current=(int)one('SELECT COUNT(*) n FROM post_images WHERE post_id=?',[$postId])['n'];
  if($current+count($uploads)>(int)$plan['post_image_limit'])fail('Limite de fotos desta publicação atingido.');
  foreach($uploads as $index=>$upload){$url=savePostUpload($upload);$saved[]=$url;query('INSERT INTO post_images(post_id,url,position) VALUES(?,?,?)',[$postId,$url,$current+$index]);}
  if($current===0)query('UPDATE posts SET image_url=? WHERE id=?',[$saved[0],$postId]);
 });}catch(Throwable $error){foreach($saved as $url)if(is_file(ROOT.'/public'.$url))@unlink(ROOT.'/public'.$url);throw $error;}
}
function removePostPhoto(array $business,int $postId,int $imageId): void {
 $url=transaction(function()use($business,$postId,$imageId){
  lockBusiness((int)$business['id']);$image=one('SELECT i.id,i.url FROM post_images i JOIN posts p ON p.id=i.post_id WHERE i.id=? AND i.post_id=? AND p.business_id=?',[$imageId,$postId,$business['id']]);if(!$image)fail('Foto não encontrada.',404);
  if(!isLive($business))fail('Ative seu plano antes de alterar fotos.');
  query('DELETE FROM post_images WHERE id=?',[$imageId]);$first=one('SELECT url FROM post_images WHERE post_id=? ORDER BY position,id LIMIT 1',[$postId]);query('UPDATE posts SET image_url=? WHERE id=?',[$first['url']??'',$postId]);return $image['url'];
 });if(preg_match('#^/uploads/[a-f0-9]{32}\.jpg$#',$url))@unlink(ROOT.'/public'.$url);
}
function setPostCover(array $business,int $postId,int $imageId): void {
 transaction(function()use($business,$postId,$imageId){
  lockBusiness((int)$business['id']);$post=one('SELECT id FROM posts WHERE id=? AND business_id=?',[$postId,$business['id']]);if(!$post)fail('Publicação não encontrada.',404);
  if(!isLive($business))fail('Ative seu plano antes de alterar a capa.');
  $images=query('SELECT id,url FROM post_images WHERE post_id=? ORDER BY position,id',[$postId])->fetchAll();
  $selected=null;foreach($images as $image)if((int)$image['id']===$imageId)$selected=$image;
  if(!$selected)fail('Foto não encontrada nesta publicação.',404);
  $ordered=array_merge([$selected],array_values(array_filter($images,fn($image)=>(int)$image['id']!==$imageId)));
  foreach($ordered as $position=>$image)query('UPDATE post_images SET position=? WHERE id=?',[$position,$image['id']]);
  query('UPDATE posts SET image_url=? WHERE id=?',[$selected['url'],$postId]);
 });
}
