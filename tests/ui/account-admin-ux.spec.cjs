const {test,expect}=require('@playwright/test');

test('Conta destaca código e admin aprova Free mantendo o plano escolhido',async({page})=>{
 const session=await (await page.request.get('/api/session')).json();
 const account=await (await page.request.post('/api/register',{headers:{'X-CSRF-Token':session.csrf},data:{name:'Cliente código',email:'codigo-ui@qa.local',password:'CodigoQA#2026!',consent:true}})).json();
 const created=await page.request.post('/api/me/business',{headers:{'X-CSRF-Token':account.csrf},data:{name:'Negócio código fácil',kind:'company',category_id:4,plan_id:4,interest_plan_id:4,description:'Serviço local criado para validar código e aprovação.',neighborhood:'Centro',phone:'31988887777',notification_channel:'email',publication_consent:true}});
 expect(created.ok()).toBeTruthy();const mine=await (await page.request.get('/api/me/business')).json();
 await page.goto('/#!/painel');const code='SZ-'+String(mine.id).padStart(6,'0');await expect(page.locator('.business-id-card')).toContainText(code);await expect(page.locator('.business-id-card')).toContainText('comprovantes');

 await page.request.post('/api/logout',{headers:{'X-CSRF-Token':account.csrf}});const adminSession=await (await page.request.get('/api/session')).json();const login=await (await page.request.post('/api/login',{headers:{'X-CSRF-Token':adminSession.csrf},data:{email:'admin@qa.local',password:'AdminQA#2026!'}})).json();expect(login.user.role).toBe('admin');
 const adminPage=page;await adminPage.goto('/?session=admin#!/admin');await adminPage.getByLabel('Buscar cadastros').fill('Negócio código fácil');await adminPage.getByRole('button',{name:'Ver cadastro →',exact:true}).click();
 const plan=adminPage.locator('select[ng-model="vm.activation.plan_id"]');await expect(plan).toContainText('Free');await expect(plan).toHaveValue('number:4');await expect(adminPage.getByRole('button',{name:'Ativar cadastro',exact:true})).toHaveCount(0);
 await adminPage.getByLabel('Motivo (obrigatório)').fill('Cadastro Free conferido');await adminPage.getByRole('button',{name:'Aprovar cadastro',exact:true}).click();await expect(adminPage.getByRole('status')).toContainText('Cadastro atualizado');
 expect((await page.request.post('/api/admin/businesses/'+mine.id+'/activate',{headers:{'X-CSRF-Token':login.csrf},data:{plan_id:1,days:30,reason:'Ativação paga para testar capa'}})).ok()).toBeTruthy();
 await page.request.post('/api/logout',{headers:{'X-CSRF-Token':login.csrf}});const customerSession=await (await page.request.get('/api/session')).json();const customerLogin=await (await page.request.post('/api/login',{headers:{'X-CSRF-Token':customerSession.csrf},data:{email:'codigo-ui@qa.local',password:'CodigoQA#2026!'}})).json();
 await page.request.post('/api/me/free-cover',{headers:{'X-CSRF-Token':customerLogin.csrf},data:{key:'pharmacy'}});await page.goto('/?session=customer#!/painel');await page.getByRole('button',{name:'Fotos e identidade'}).click();await expect(page.getByRole('heading',{name:'Escolha a imagem do seu negócio'})).toBeVisible();await expect(page.getByRole('button',{name:/Farmácia e saúde/})).toHaveAttribute('aria-pressed','true');
});
