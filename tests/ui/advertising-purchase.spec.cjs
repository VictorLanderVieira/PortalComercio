const {test,expect}=require('@playwright/test');
const {approveBusiness}=require('./approval-helper.cjs');

test('Divulgue aqui exige conta e apresenta Pix antes do envio do banner',async({page})=>{
 await page.goto('/');
 const cta=page.getByRole('button',{name:/Anunciar por R\$/});
 await expect(cta).toBeVisible();
 await cta.click();
 const auth=page.getByRole('dialog',{name:/Entre e fique à vontade/});
 await expect(auth).toBeVisible();
 await expect(page.getByRole('link',{name:/Enviar material pelo WhatsApp/})).toHaveCount(0);
});

test('QR Code da publicidade aparece quando o Asaas o disponibiliza após criar a cobrança',async({page})=>{
 const session=await (await page.request.get('/api/session')).json();
 const account=await (await page.request.post('/api/register',{headers:{'X-CSRF-Token':session.csrf},data:{name:'Anunciante QR QA',email:'anunciante-qr@qa.local',password:'AnuncianteQA#2026!',consent:true}})).json();
 const created=await page.request.post('/api/me/business',{headers:{'X-CSRF-Token':account.csrf},data:{name:'Comércio QR QA',kind:'company',category_id:1,plan_id:4,interest_plan_id:4,description:'Negócio para testar o QR da publicidade.',neighborhood:'Centro',phone:'31988887777',publication_consent:true}});
 expect(created.ok()).toBeTruthy();
 await approveBusiness(page,'Comércio QR QA');
 let issued=false;
 const order={id:987,status:'pending',provider:'asaas',amount_cents:3500,expires_in_seconds:1800,expires_at:'2026-09-24 12:00:00'};
 await page.route('**/api/me/ad-order',route=>{
  if(route.request().method()==='POST'){issued=true;return route.fulfill({json:order});}
  return issued?route.fulfill({json:{...order,payload:'PIX-COPIA-E-COLA-TESTE',encodedImage:'aGVsbG8='}}):route.fulfill({status:200,contentType:'application/json',body:'null'});
 });
 await page.goto('/');
 await page.getByRole('button',{name:/Anunciar por R\$/}).click();
 const dialog=page.getByRole('dialog',{name:'Destaque sua oferta em Sarzedo'});
 await dialog.getByRole('button',{name:'Gerar Pix da publicidade'}).click();
 await expect(dialog).toContainText('Preparando seu QR Code Pix');
 await expect(dialog.getByLabel('Pix copia e cola')).toHaveValue('PIX-COPIA-E-COLA-TESTE',{timeout:10000});
});
