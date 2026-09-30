const {test,expect}=require('@playwright/test');

test('Página de ofertas é compartilhável e legível no celular',async({page})=>{
 const errors=[];page.on('pageerror',error=>errors.push(error.message));
 await page.goto('/');
 await expect(page.locator('.home-discovery-top .advertising-rail')).toBeVisible();
 await expect(page.locator('.search-ad-layout .offers-rail')).toBeVisible();
 await page.locator('.home-discovery-top').screenshot({path:'test-results/home-ads-offers-desktop.png'});
 await page.setViewportSize({width:390,height:844});
 expect(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth)).toBeTruthy();
 await page.locator('.home-discovery-top').screenshot({path:'test-results/home-ad-mobile.png'});
 await page.getByRole('link',{name:'Ver todas as ofertas de Sarzedo'}).click();
 await expect(page).toHaveURL(/#!\/ofertas$/);
 await expect(page.getByRole('heading',{name:'Achados para aproveitar por perto.'})).toBeVisible();
 for(const label of ['Aberto agora','Faz entrega','Atende em domicílio'])await expect(page.getByLabel(label,{exact:true})).toBeVisible();
 const share=new URL(await page.getByRole('link',{name:'Compartilhar página no WhatsApp'}).getAttribute('href'));
 expect(share.hostname).toBe('wa.me');expect(share.searchParams.get('text')).toContain('/ofertas');
 await page.getByLabel('Buscar oferta ou empresa').fill('termo que não existe nesta cidade');
 await page.getByRole('button',{name:'Buscar',exact:true}).click();
 await expect(page.getByText('Nenhuma oferta encontrada agora.')).toBeVisible();
 await page.getByRole('button',{name:'Limpar filtros'}).click();
 await page.setViewportSize({width:390,height:844});
 expect(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth)).toBeTruthy();
 await page.screenshot({path:'test-results/offers-mobile.png',fullPage:true});
 expect(errors).toEqual([]);
});
