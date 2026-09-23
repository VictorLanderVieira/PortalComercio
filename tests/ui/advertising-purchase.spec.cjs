const {test,expect}=require('@playwright/test');

test('Divulgue aqui exige conta e apresenta Pix antes do envio do banner',async({page})=>{
 await page.goto('/');
 const cta=page.getByRole('button',{name:/Anunciar por R\$/});
 await expect(cta).toBeVisible();
 await cta.click();
 const auth=page.getByRole('dialog',{name:/Entre e fique à vontade/});
 await expect(auth).toBeVisible();
 await expect(page.getByRole('link',{name:/Enviar material pelo WhatsApp/})).toHaveCount(0);
});
