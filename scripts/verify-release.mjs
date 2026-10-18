import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
const version=process.argv[2]||'1.0.0-rc.2';
if(!/^[0-9A-Za-z.-]+$/.test(version))throw new Error('Invalid release version');
const root=path.resolve(import.meta.dirname,'../release');
const dir=path.join(root,version);
const manifest=JSON.parse(fs.readFileSync(path.join(dir,'manifest.json'),'utf8'));
let verified=0;
for(const file of manifest.files){
  const resolved=path.resolve(dir,file.path);
  if(!resolved.startsWith(dir+path.sep))throw new Error('Invalid manifest path');
  if(crypto.createHash('sha256').update(fs.readFileSync(resolved)).digest('hex')!==file.sha256)throw new Error('Hash mismatch: '+file.path);
  if(/(?:^|\/)\.env(?:$|\.(?!example$))|\.sqlite(?:$|-)|^server\/(?:tests|scripts)\/|^server\/vendor\/(?:phpunit|fakerphp|mockery|laravel\/pint)\//.test(file.path))throw new Error('Unexpected development/private file: '+file.path);
  if(file.path.endsWith('.js') && file.path.startsWith('server/public/spa/')){
    const text=fs.readFileSync(resolved,'utf8');
    for(const marker of ['312 dari 398','Dari 42 kegiatan aktif','Local-Dmf-Testing-2026!'])if(text.includes(marker))throw new Error('Example/test content in production bundle');
  }
  verified++;
}
const installed=JSON.parse(fs.readFileSync(path.join(dir,'server/vendor/composer/installed.json'),'utf8'));
if(installed.dev!==false)throw new Error('Production dependencies expected');
const archive=path.join(root,'dmf-'+version+'.zip');
const hash=crypto.createHash('sha256').update(fs.readFileSync(archive)).digest('hex');
if(!fs.readFileSync(archive+'.sha256','utf8').startsWith(hash+' '))throw new Error('Archive checksum mismatch');
console.log(JSON.stringify({version,verified_files:verified,archive_bytes:fs.statSync(archive).size,sha256:hash,production_dependencies:true},null,2));
