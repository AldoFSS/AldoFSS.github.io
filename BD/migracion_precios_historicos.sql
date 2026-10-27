-- Aplicar una vez. Los pedidos nuevos conservan el precio al momento de comprar.
-- Los detalles antiguos quedan NULL: no es posible reconstruir su precio original.
ALTER TABLE detallesorden ADD COLUMN Precio_Unitario DECIMAL(10,2) NULL;
