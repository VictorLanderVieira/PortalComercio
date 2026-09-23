const {test,expect}=require('@playwright/test');
test('Estrelas clicáveis na busca e na empresa publicam a nota escolhida',async({page})=>{
 await page.goto('/');await expect(page.locator('.business-card').first()).toBeVisible();
 const card=page.locator('.business-card').filter({has:page.locator('.card-title',{hasText:'Café da Praça'})});
 await card.getByRole('radio',{name:'4 estrelas',exact:true}).check();await expect(card.locator('.star-options .selected')).toHaveCount(4);
 await card.getByRole('radio',{name:'1 estrela',exact:true}).check();await expect(card.locator('.star-options .selected')).toHaveCount(1);
 await card.getByRole('radio',{name:'2 estrelas',exact:true}).check();await card.getByRole('button',{name:'Entrar para avaliar'}).click();
 const modal=page.getByRole('dialog');await modal.getByLabel('E-mail',{exact:true}).fill('admin@qa.local');await modal.getByLabel('Senha',{exact:true}).fill('AdminQA#2026!');await modal.getByRole('button',{name:'Entrar na minha conta'}).click();
 await card.getByLabel('Conte sua experiência').fill('Atendimento gentil e uma experiência de teste.');await card.getByRole('button',{name:'Publicar avaliação'}).click();
 await expect(card.locator('.rating-stars')).toHaveAttribute('aria-label','3,5 de 5 estrelas');
 await page.locator('.card-title').filter({hasText:'Studio Bela'}).click();const form=page.locator('.review-form');await form.getByRole('radio',{name:'5 estrelas',exact:true}).check();await expect(form.locator('.selected')).toHaveCount(5);await form.getByRole('radio',{name:'3 estrelas',exact:true}).check();await expect(form.locator('.selected')).toHaveCount(3);await form.getByLabel('Conte para a gente').fill('Uma experiência para verificar a avaliação pelas estrelas.');await form.getByRole('button',{name:'Publicar avaliação'}).click();await expect(page.getByText('Uma experiência para verificar a avaliação pelas estrelas.')).toBeVisible();
});
test('Localização pública na busca e perfil; promoções avançam a cada cinco segundos',async({page})=>{
 await page.route('**/api/businesses**',async route=>{const response=await route.fetch();const data=await response.json();if(Array.isArray(data)){data[0].maps_url='https://maps.app.goo.gl/localTeste';data.slice(1).forEach(b=>b.maps_url='');}else data.maps_url='https://maps.app.goo.gl/localTeste';await route.fulfill({response,json:data});});
 await page.route('**/api/promotions',route=>route.fulfill({json:[1,2].map(id=>({id,business_id:1,title:'Oferta teste '+id,description:'Condições da oferta',price_cents:2500,image_url:'/assets/cafe.svg',business_name:'Café da Praça'}))}));
 await page.route('**/api/session',async route=>{const response=await route.fetch();const data=await response.json();data.settings.maps_embed_key='AIza12345678901234567890123456789012345';await route.fulfill({response,json:data});});
 await page.route('https://www.google.com/maps/embed/**',route=>route.fulfill({contentType:'text/html',body:'<p>Mapa de teste</p>'}));
 await page.clock.install();await page.clock.pauseAt(new Date());await page.goto('/');await expect(page.locator('.promo-slide')).toContainText('Oferta teste 1');await expect(page.locator('.map-link')).toHaveCount(1);await page.locator('.map-link').click();const map=page.locator('.map-modal');await expect(map).toBeVisible();await expect(map.locator('iframe')).toHaveAttribute('src',/www\.google\.com\/maps\/embed\/v1\/place\?key=/);await expect(map.getByRole('link',{name:'Abrir localização informada'})).toHaveAttribute('href','https://maps.app.goo.gl/localTeste');await map.getByRole('button',{name:'Voltar ao portal'}).click();await expect(map).toHaveCount(0);
 await page.clock.runFor(4999);await expect(page.locator('.promo-slide')).toContainText('Oferta teste 1');await page.clock.runFor(1);await expect(page.locator('.promo-slide')).toContainText('Oferta teste 2');await page.clock.runFor(5000);await expect(page.locator('.promo-slide')).toContainText('PLANO DESTAQUE');
 await page.getByRole('button',{name:'Pausar promoções automáticas'}).click();await page.clock.runFor(10000);await expect(page.locator('.promo-slide')).toContainText('PLANO DESTAQUE');await page.getByRole('button',{name:'Retomar promoções automáticas'}).click();await page.clock.runFor(5000);await expect(page.locator('.promo-slide')).toContainText('PLANO PREMIUM');
 await page.setViewportSize({width:390,height:844});await expect.poll(()=>page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth)).toBeTruthy();await page.screenshot({path:'test-results/improvements-mobile.png',fullPage:true});await page.locator('.card-title').first().click();await page.getByRole('link',{name:'Ver localização',exact:true}).click();await expect(page.locator('.map-modal')).toBeVisible();
});


