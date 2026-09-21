const fs=require('fs'),path=require('path'),{execFileSync}=require('child_process');
let count=0;
function walk(dir){for(const entry of fs.readdirSync(dir,{withFileTypes:true})){const file=path.join(dir,entry.name);if(entry.isDirectory())walk(file);else if(file.endsWith('.php')){execFileSync('php',['-l',file],{stdio:'pipe'});count++;}}}
for(const dir of ['server','scripts','public/api'])walk(dir);
console.log(`${count} arquivos PHP com sintaxe válida.`);
