const {test,expect}=require('@playwright/test');

test('Configuração Asaas mostra validação e confirma salvamento',async({page})=>{
 await page.goto('/#!/admin');
 await page.getByRole('button',{name:'Entrar',exact:true}).last().click();
 const login=page.getByRole('dialog');
 await login.getByLabel('E-mail',{exact:true}).fill('admin@qa.local');
 await login.getByLabel('Senha',{exact:true}).fill('AdminQA#2026!');
 await login.getByRole('button',{name:'Entrar na minha conta'}).click();
 const section=page.locator('.asaas-settings-admin');
 await expect(section).toBeVisible();
 await page.getByRole('button',{name:'Configurar Asaas',exact:true}).click();
 await expect.poll(async()=>section.evaluate(el=>Math.round(el.getBoundingClientRect().top))).toBeLessThan(220);
 await page.setViewportSize({width:390,height:844});
 await page.getByRole('button',{name:'Configurar Asaas',exact:true}).click();
 await expect.poll(async()=>section.evaluate(el=>Math.round(el.getBoundingClientRect().top))).toBeLessThan(220);
 await section.getByRole('button',{name:'Salvar configuração Asaas'}).click();
 await expect(section.getByRole('alert')).toContainText('Informe sua senha de administrador');
 await section.getByLabel('Confirme sua senha de administrador').fill('AdminQA#2026!');
 await section.getByLabel('Usar Asaas nas novas cobranças mensais').check();
 await section.getByRole('button',{name:'Salvar configuração Asaas'}).click();
 await expect(section.getByRole('alert')).toContainText('Confirme que o webhook está ativo');
 await section.getByLabel('Usar Asaas nas novas cobranças mensais').uncheck();
 await section.getByRole('button',{name:'Salvar configuração Asaas'}).click();
 await expect(page.getByRole('status')).toContainText('Configuração Asaas salva');
});
