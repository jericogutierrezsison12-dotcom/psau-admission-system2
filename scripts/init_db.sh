#!/bin/bash
# Database initialization script for Render deployment

echo "Starting database initialization..."

# Wait for database to be ready
echo "Waiting for database connection..."
sleep 10

# Get database connection details from environment variables
DB_HOST=${DB_HOST}
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASSWORD=${DB_PASSWORD}
DB_PORT=${DB_PORT}

# Check if database exists and create if it doesn't
echo "Checking database connection..."
PGPASSWORD=$DB_PASSWORD psql -h $DB_HOST -p $DB_PORT -U $DB_USER -d postgres -c "SELECT 1;" > /dev/null 2>&1

if [ $? -eq 0 ]; then
    echo "Database connection successful"
    
    # Check if our database exists
    PGPASSWORD=$DB_PASSWORD psql -h $DB_HOST -p $DB_PORT -U $DB_USER -d postgres -c "SELECT 1 FROM pg_database WHERE datname = '$DB_NAME';" | grep -q 1
    
    if [ $? -ne 0 ]; then
        echo "Creating database: $DB_NAME"
        PGPASSWORD=$DB_PASSWORD psql -h $DB_HOST -p $DB_PORT -U $DB_USER -d postgres -c "CREATE DATABASE $DB_NAME;"
    else
        echo "Database $DB_NAME already exists"
    fi
    
    # Run the PostgreSQL schema
    echo "Running database schema..."
    PGPASSWORD=$DB_PASSWORD psql -h $DB_HOST -p $DB_PORT -U $DB_USER -d $DB_NAME -f database/psau_admission_postgresql.sql
    
    if [ $? -eq 0 ]; then
        echo "Database initialization completed successfully"
    else
        echo "Database initialization failed"
        exit 1
    fi
else
    echo "Database connection failed"
    exit 1
fi

echo "Database setup complete!"