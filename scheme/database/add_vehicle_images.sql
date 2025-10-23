-- Add image column to vehicles table
ALTER TABLE vehicles ADD COLUMN image VARCHAR(255) DEFAULT NULL AFTER plate_number;

-- Add sample vehicle images (you'll replace these with actual image filenames)
UPDATE vehicles SET image = 'toyota-camry.jpg' WHERE brand = 'Toyota' AND model = 'Camry';
UPDATE vehicles SET image = 'honda-crv.jpg' WHERE brand = 'Honda' AND model = 'CR-V';
UPDATE vehicles SET image = 'ford-transit.jpg' WHERE brand = 'Ford' AND model = 'Transit';
UPDATE vehicles SET image = 'nissan-altima.jpg' WHERE brand = 'Nissan' AND model = 'Altima';
UPDATE vehicles SET image = 'toyota-corolla.jpg' WHERE brand = 'Toyota' AND model = 'Corolla';

-- Show updated vehicles table structure
DESCRIBE vehicles;

-- Show vehicles with image data
SELECT id, brand, model, year, plate_number, image, daily_rate, status FROM vehicles WHERE deleted_at IS NULL;