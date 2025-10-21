<?php
/**
 * PSAU Admission System - Setup Script
 * Helps with initial configuration and database setup
 */

// Check if .env file exists
if (!file_exists('.env')) {
    echo "Creating .env file from template...\n";
    copy('env.example', '.env');
    echo "Please edit .env file with your configuration.\n";
}

// Check if uploads directory exists and is writable
if (!is_dir('uploads')) {
    mkdir('uploads', 0755, true);
    echo "Created uploads directory.\n";
}

if (!is_dir('images')) {
    mkdir('images', 0755, true);
    echo "Created images directory.\n";
}

// Check if logo directory exists
if (!is_dir('logo')) {
    mkdir('logo', 0755, true);
    echo "Created logo directory.\n";
}

echo "Setup completed! Please configure your .env file and import the database schema.\n";
echo "Database schema file: database/psau_admission.sql\n";
echo "Environment template: env.example\n";
