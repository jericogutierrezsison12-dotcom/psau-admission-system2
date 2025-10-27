#!/bin/bash
# Auto Deploy Script for PSAU Admission System
# This script runs the database cleanup automatically on Render/Railway

echo "=== PSAU Admission System Auto Deploy ==="
echo "Starting deployment process..."

# Check if we're in production environment
if [ -n "$RENDER" ] || [ -n "$RAILWAY_ENVIRONMENT" ]; then
    echo "Production environment detected: $([ -n "$RENDER" ] && echo "Render" || echo "Railway")"
    
    # Run the PHP cleanup script
    echo "Running database cleanup..."
    php auto_cleanup_on_deploy.php
    
    if [ $? -eq 0 ]; then
        echo "✓ Database cleanup completed successfully"
    else
        echo "✗ Database cleanup failed"
        exit 1
    fi
else
    echo "Development environment detected - skipping cleanup"
fi

echo "=== Deployment completed ==="
