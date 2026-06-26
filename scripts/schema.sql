-- PostgreSQL Database Schema for Excel / CSV File Splitter

-- Create users table (optional, included for completeness)
CREATE TABLE IF NOT EXISTS users (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Create split_jobs table
CREATE TABLE IF NOT EXISTS split_jobs (
    id BIGSERIAL PRIMARY KEY,
    uuid CHAR(36) UNIQUE NOT NULL,
    user_id BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_path VARCHAR(512) NOT NULL,
    file_type VARCHAR(10) NOT NULL,
    total_rows INT NULL,
    chunk_size INT NOT NULL,
    has_header BOOLEAN NOT NULL DEFAULT TRUE,
    total_output_files INT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    output_zip_path VARCHAR(512) NULL,
    error_message TEXT NULL,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Indexes for split_jobs
CREATE INDEX IF NOT EXISTS idx_split_jobs_status ON split_jobs(status);
CREATE INDEX IF NOT EXISTS idx_split_jobs_uuid ON split_jobs(uuid);
CREATE INDEX IF NOT EXISTS idx_split_jobs_user ON split_jobs(user_id);

-- Create split_job_files table
CREATE TABLE IF NOT EXISTS split_job_files (
    id BIGSERIAL PRIMARY KEY,
    split_job_id BIGINT NOT NULL REFERENCES split_jobs(id) ON DELETE CASCADE,
    file_name VARCHAR(255) NOT NULL,
    row_count INT NOT NULL,
    file_path VARCHAR(512) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Indexes for split_job_files
CREATE INDEX IF NOT EXISTS idx_split_job_files_job ON split_job_files(split_job_id);
