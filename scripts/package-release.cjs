// Explicit allowlist: never ship development data, uploads, private settings or seeds.
const fs=require('fs'),path=require('path'),os=require('os'),{execFileSync}=require('child_process'),crypto=require('crypto');
const root=path.resolve(__dirname,'..');
const staging=fs.mkdtempSync(path.join(os.tmpdir(),'portal-release-'));
const copy=(from,to=from)=>{const source=path.join(root,from);if(fs.lstatSync(source).isSymbolicLink())throw Error('Symlink não permitido: '+from);fs.mkdirSync(path.dirname(path.join(staging,to)),{recursive:true});fs.copyFileSync(source,path.join(staging,to));};
function tree(dir){for(const e of fs.readdirSync(path.join(root,dir),{withFileTypes:true})){const f=path.join(dir,e.name);if(f.replaceAll('\\','/').startsWith('public/uploads/'))continue;if(e.isSymbolicLink())throw Error('Symlink não permitido: '+f);if(e.isDirectory())tree(f);else copy(f);}}
for(const dir of ['public','server','database'])tree(dir);
for(const file of ['migrate.php','upgrade.php','create-admin.php','worker.php','preflight.php','backup-db.php','verify-safe-migrations.php'])copy('scripts/'+file);
copy('public/uploads/.htaccess');copy('.env.production.example');copy('.env.staging.example');
fs.mkdirSync(path.join(staging,'storage'),{recursive:true});
fs.mkdirSync(path.join(root,'dist'),{recursive:true});
const output=path.join(root,'dist','portal-release.tar.gz');
execFileSync('tar',['-czf',output,'-C',staging,'.']);
const hash=crypto.createHash('sha256').update(fs.readFileSync(output)).digest('hex');
fs.writeFileSync(output+'.sha256',hash+'  portal-release.tar.gz\n');
console.log('Pacote criado: '+output+'\nSHA256: '+hash);
// Deliberately leave the uniquely named temporary directory to the OS cleanup.
