ALTER TABLE businesses ADD COLUMN approval_status VARCHAR(20) NOT NULL DEFAULT 'pending';
INSERT INTO categories(id,name,icon) VALUES (10,'Transporte','transport'),(11,'Mecânica','tool'),(12,'Studio de Tatuagem','sparkles'),(13,'Foto e filmagem','camera');
