# Regra permanente de preservação dos bancos

Os bancos do projeto nunca devem ser apagados, recriados, substituídos ou esvaziados durante desenvolvimento, teste, atualização ou publicação.

## Banco local

- O arquivo permanente é `storage/portal.sqlite`.
- Migrações alteram esse banco de forma incremental e preservam os registros.
- Antes de uma migração local relevante, criar uma cópia em `storage/backups`.
- Testes só podem excluir bancos temporários dentro de `test-results`; há uma trava no preparador dos testes que rejeita `portal.sqlite` e qualquer caminho fora dessa pasta.
- `storage/portal.sqlite` e os backups permanecem ignorados pelo Git e fora dos pacotes de publicação.

## Banco de produção

- O MySQL `u211282174_portal_prod` é permanente.
- Uma atualização usa apenas `scripts/migrate.php` e migrações versionadas incrementais.
- O instalador cria um backup privado antes de aplicar qualquer migração. Se o backup falhar, a publicação é interrompida.
- O verificador bloqueia migrações que contenham `DROP DATABASE`, `DROP SCHEMA`, `DROP TABLE`, `DROP COLUMN`, `TRUNCATE`, `DELETE FROM` ou `REPLACE INTO`.
- Restaurações devem ser feitas em uma base vazia separada para conferência. Nunca restaurar por cima da produção.
- Alterar código e trocar a versão publicada não substitui o banco, os uploads ou o arquivo privado `.env`.

Exclusões funcionais solicitadas por um usuário, como remover uma foto ou promoção específica pela interface, não apagam a base. Elas continuam limitadas ao registro pertencente ao usuário autenticado.
