(function(){
  const id=document.body.dataset.businessId;
  if(!id)return;
  fetch('/api/session',{credentials:'same-origin'}).then(r=>r.ok?r.json():null).then(session=>{
    if(!session||!session.csrf)return;
    const event=type=>fetch('/api/businesses/'+id+'/events',{method:'POST',credentials:'same-origin',keepalive:true,headers:{'Content-Type':'application/json','X-CSRF-Token':session.csrf},body:JSON.stringify({type})}).catch(()=>{});
    event('profile_view');
    document.querySelectorAll('[data-metric]').forEach(link=>link.addEventListener('click',()=>event(link.dataset.metric)));
  }).catch(()=>{});
})();
