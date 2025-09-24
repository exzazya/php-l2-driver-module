-- Migration: Add completed_at column to trips table
-- Run this script to update existing database

USE logi_L2;

-- Add completed_at column to trips table
ALTER TABLE trips 
ADD COLUMN completed_at DATETIME NULL 
AFTER status;

-- Update existing completed trips to set completed_at timestamp
UPDATE trips SET completed_at = updated_at WHERE status = 'completed' AND completed_at IS NULL;
