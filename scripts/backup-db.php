<?php
// Consistent MySQL snapshot. Restore into an EMPTY database; never overwrite live data blindly.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../server/bootstrap.php';
if(env('DB_DRIVER')!=='mysql'){fwrite(STDERR,"Backup de publicação requer MySQL.\n");exit(1);}
$target=$argv[1]??'';
if(!$target||file_exists($target)||!is_dir(dirname($target))){fwrite(STDERR,"Informe um arquivo novo em diretório privado existente.\n");exit(1);}
umask(0077);$out=gzopen($target,'wb6');if(!$out)exit(1);
try{
 $tables=query('SHOW FULL TABLES WHERE Table_type = \'BASE TABLE\'')->fetchAll(PDO::FETCH_COLUMN);
 $definitions=[];foreach($tables as$t){$name='`'.str_replace('`','``',$t).'`';$definitions[$t]=db()->query('SHOW CREATE TABLE '.$name)->fetch(PDO::FETCH_NUM)[1];}
 db()->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');db()->beginTransaction();
 $write=function(string $text)use($out){if(gzwrite($out,$text)!==strlen($text))throw new RuntimeException('Backup incompleto.');};
 $write("SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n");
 db()->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,false);
 foreach($tables as$t){$name='`'.str_replace('`','``',$t).'`';$write($definitions[$t].";\n");$q=db()->query('SELECT * FROM '.$name);while($r=$q->fetch(PDO::FETCH_ASSOC)){$cols=implode(',',array_map(fn($c)=>'`'.str_replace('`','``',$c).'`',array_keys($r)));$vals=implode(',',array_map(fn($v)=>$v===null?'NULL':db()->quote((string)$v),array_values($r)));$write('INSERT INTO '.$name.' ('.$cols.') VALUES ('.$vals.");\n");}$q->closeCursor();}
 $write("SET FOREIGN_KEY_CHECKS=1;\n");db()->commit();gzclose($out);echo "Backup do banco concluído.\n";
}catch(Throwable $e){if(db()->inTransaction())db()->rollBack();gzclose($out);fwrite(STDERR,"Falha no backup; publicação interrompida. O arquivo parcial não deve ser restaurado.\n");exit(1);}
