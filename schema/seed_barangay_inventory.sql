-- =========================================================================
-- Seed Data: Barangay Medicine Purchases (2023 & 2024 Tangcul)
-- Based on Barangay Health Station Log Sheets
-- =========================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Insert or ensure all medicines exist
INSERT INTO medicines (name, generic_name, formulation, strength, unit, reorder_level) VALUES
('Carbocisteine', 'Carbocisteine', 'Capsule', '500mg', 'box', 2),
('Mefenamic Acid', 'Mefenamic Acid', 'Capsule', '500mg', 'box', 2),
('Mefenamic Acid', 'Mefenamic Acid', 'Capsule', '250mg', 'box', 2),
('Metoprolol', 'Metoprolol Tartrate', 'Tablet', '50mg', 'box', 2),
('Amlodipine', 'Amlodipine Besilate', 'Tablet', '5mg', 'box', 3),
('Ascorbic Acid', 'Vitamin C', 'Tablet', '500mg', 'box', 2),
('Dehydrosol', 'Oral Rehydration Salts', 'Sachet', 'Standard ORS', 'box', 2),
('Lagundi', 'Vitex negundo L.', 'Tablet', '300mg', 'box', 2),
('Lagundi (Offlem)', 'Vitex negundo L.', 'Tablet', '600mg', 'box', 2),
('Lagundi (Offlem Forte)', 'Vitex negundo L.', 'Tablet', '600mg', 'box', 2),
('Lagundi Syrup', 'Vitex negundo L.', 'Syrup', '300mg/5mL 60mL', 'bottle', 5),
('Lagundi (Offlem) Syrup', 'Vitex negundo L.', 'Syrup', '300mg/5mL 60mL', 'bottle', 5),
('Clonidine', 'Clonidine Hydrochloride', 'Tablet', '75mcg', 'box', 1),
('Dicycloverine', 'Dicycloverine Hydrochloride', 'Tablet', '10mg', 'box', 2),
('Dicycloverine Syrup', 'Dicycloverine HCl', 'Syrup', '10mg/5mL 60mL', 'bottle', 3),
('Ferrous Sulfate', 'Ferrous Sulfate', 'Tablet', '500mg', 'box', 2),
('Iron + Folic Acid', 'Ferrous Fumarate + Folic Acid', 'Tablet', '60mg/250mcg', 'box', 2),
('Iron + Folic Acid', 'Ferrous Fumarate + Folic Acid', 'Tablet', '600mg', 'box', 2),
('Ascorbic Acid Syrup', 'Vitamin C', 'Syrup', '100mg/5mL 60mL', 'bottle', 5),
('Ascorbic Acid (Myrevit-C)', 'Vitamin C', 'Syrup', '100mg/5mL 60mL', 'bottle', 5),
('Ascorbic Acid Drops', 'Vitamin C', 'Drops', '100mg/mL 15mL', 'bottle', 5),
('Multivitamins Syrup', 'Multivitamins', 'Syrup', '60mL', 'bottle', 5),
('Multivitamins (Multilem PNT)', 'Multivitamins', 'Syrup', '60mL', 'bottle', 5),
('Multivitamins Drops', 'Multivitamins', 'Drops', '15mL', 'bottle', 5),
('Multivitamins', 'Multivitamins', 'Capsule', 'Multi', 'box', 2),
('Vitamin B1 + B6 + B12', 'Vitamin B-Complex', 'Tablet', 'B1+B6+B12', 'box', 2),
('Ancovit-B Vitamins', 'Vitamin B-Complex', 'Tablet', 'B1+B6+B12', 'box', 2),
('Paracetamol', 'Paracetamol', 'Tablet', '500mg', 'box', 3),
('Paracetamol (Rapidol)', 'Paracetamol', 'Tablet', '500mg', 'box', 3),
('Paracetamol (4Fever) Syrup', 'Paracetamol', 'Syrup', '120mg/5mL 60mL', 'bottle', 5),
('Paracetamol Syrup', 'Paracetamol', 'Syrup', '120mg/5mL 60mL', 'bottle', 5),
('Paracetamol (4Fever) Drops', 'Paracetamol', 'Drops', '100mg/mL 15mL', 'bottle', 5),
('Paracetamol Drops', 'Paracetamol', 'Drops', '100mg/mL 15mL', 'bottle', 5),
('Metoclopramide', 'Metoclopramide HCl', 'Tablet', '10mg', 'box', 2),
('Metoclopramide Hydrochloride Syrup', 'Metoclopramide HCl', 'Syrup', '5mg/5mL 60mL', 'bottle', 3),
('Cinnarizine', 'Cinnarizine', 'Tablet', '25mg', 'box', 2),
('Cetirizine', 'Cetirizine Hydrochloride', 'Tablet', '10mg', 'box', 2),
('Cetirizine Syrup', 'Cetirizine HCl', 'Syrup', '5mg/5mL 60mL', 'bottle', 5),
('Cetirizine Drops', 'Cetirizine HCl', 'Drops', '2.5mg/mL 10mL', 'bottle', 5),
('Carbocisteine Drops', 'Carbocisteine', 'Drops', '50mg/mL 15mL', 'bottle', 5),
('Losartan', 'Losartan Potassium', 'Tablet', '50mg', 'box', 3),
('Cefalexin', 'Cefalexin Monohydrate', 'Capsule', '500mg', 'box', 2),
('Amoxicillin Drops', 'Amoxicillin', 'Drops', '100mg/mL 10mL', 'bottle', 5),
('Amoxicillin Suspension', 'Amoxicillin', 'Suspension', '250mg/5mL 60mL', 'bottle', 5),
('Povidone Iodine', 'Povidone Iodine', 'Solution', '10% 30mL', 'bottle', 3),
('Gauze Sponge', 'Sterile Gauze Sponge', 'Supply', '4x4', 'box', 2),
('Micropore Tape', 'Surgical Paper Tape', 'Supply', '1 inch', 'roll', 2)
ON DUPLICATE KEY UPDATE name=VALUES(name);

SET FOREIGN_KEY_CHECKS = 1;
