-- Add payment_token column to orders table for Midtrans integration
ALTER TABLE orders ADD COLUMN payment_token VARCHAR(255) NULL;