const {test,expect}=require('@playwright/test');

const neighborhoods=[
 'Cachoeira','Santa Rita','Santo Antônio','Quintas da Lagoa','Jardim Anchieta','Riacho da Mata','Brasília',
 'Bairro Santa Rosa','Jardim Vera Cruz','Cinira de Freitas','Santa Rosa','São Pedro','Imaculada da Conceição',
 'Centro','São Joaquim','Serra Azul','Imaculada Conceição','Masterville','Santa Mônica','Manoel Pinheiro','Vila Satélite'
];

test('Filtro público oferece a lista completa de bairros de Sarzedo',async({page})=>{
 await page.goto('/');
 const filter=page.getByLabel('Filtrar por bairro');
 await expect(filter.locator('option')).toHaveCount(neighborhoods.length+1);
 await expect(filter.locator('option')).toHaveText(['Todos os bairros',...neighborhoods]);
 await filter.selectOption({label:'Vila Satélite'});
 await expect(filter).toHaveValue('Vila Satélite');
});
