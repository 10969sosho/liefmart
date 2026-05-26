SELECT id, name, sku, barcode, initial_price
FROM products
WHERE sku IN ('VV1247', 'VV1244', 'VV1243')
   OR barcode IN ('VV1247', 'VV1244', 'VV1243');

UPDATE products
SET initial_price = 45000.00
WHERE sku IN ('VV1247', 'VV1244', 'VV1243')
   OR barcode IN ('VV1247', 'VV1244', 'VV1243');

SELECT id, name, sku, barcode, initial_price
FROM products
WHERE sku IN ('VV1247', 'VV1244', 'VV1243')
   OR barcode IN ('VV1247', 'VV1244', 'VV1243');
