const {test,expect}=require('@playwright/test');

test('Novidade usa miniatura ampliável e morador encontra cadastro para avaliar',async({page})=>{
  await page.route('**/api/businesses/1',async route=>{
    const response=await route.fetch();const business=await response.json();
    business.posts=[{id:999,title:'Novidade ilustrada',body:'Uma novidade para a vizinhança.',image_url:'/assets/cafe.svg',images:[{id:1,url:'/assets/cafe.svg'}],created_at:'2026-09-23 10:00:00'}];
    await route.fulfill({response,json:business});
  });
  await page.goto('/#!/empresa/1');
  const thumbnail=page.getByRole('button',{name:'Ver anúncio Novidade ilustrada'});
  await expect(thumbnail).toBeVisible();
  await thumbnail.click();
  await expect(page.getByRole('dialog',{name:'Anúncio Novidade ilustrada'})).toBeVisible();
  await page.keyboard.press('Escape');
  await expect(page.getByRole('dialog',{name:'Anúncio Novidade ilustrada'})).toHaveCount(0);
  await page.getByRole('button',{name:'Criar conta para avaliar'}).click();
  await expect(page.getByRole('dialog',{name:'Sua história começa aqui.'})).toContainText('Cadastre-se como morador');
});
