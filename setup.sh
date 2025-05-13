#!/bin/bash

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Starting Restaurant Delivery System Setup...${NC}\n"

# Create necessary directories
echo "Creating directories..."
directories=(
    "assets/images/menu"
    "assets/css"
    "assets/js"
    "config"
    "includes"
    "layouts"
    "auth"
    "client"
    "restaurant"
    "order-manager"
    "delivery"
)

for dir in "${directories[@]}"; do
    if [ ! -d "$dir" ]; then
        mkdir -p "$dir"
        echo -e "${GREEN}✓${NC} Created directory: $dir"
    else
        echo -e "${YELLOW}!${NC} Directory already exists: $dir"
    fi
done

# Set directory permissions
echo -e "\nSetting directory permissions..."
find . -type d -exec chmod 755 {} \;
chmod 777 assets/images/menu
echo -e "${GREEN}✓${NC} Directory permissions set"

# Create empty .htaccess files to prevent directory listing
echo -e "\nSecuring directories..."
protected_dirs=(
    "config"
    "includes"
)

for dir in "${protected_dirs[@]}"; do
    if [ ! -f "$dir/.htaccess" ]; then
        echo "Deny from all" > "$dir/.htaccess"
        echo -e "${GREEN}✓${NC} Protected directory: $dir"
    fi
done

# Check for required PHP extensions
echo -e "\nChecking PHP extensions..."
required_extensions=(
    "mysqli"
    "pdo"
    "pdo_mysql"
    "gd"
    "json"
    "session"
    "fileinfo"
)

for ext in "${required_extensions[@]}"; do
    if php -r "exit(extension_loaded('$ext') ? 0 : 1);"; then
        echo -e "${GREEN}✓${NC} $ext extension is installed"
    else
        echo -e "${RED}✗${NC} $ext extension is missing"
    fi
done

# Create test database configuration
echo -e "\nCreating test database configuration..."
if [ ! -f "config/database.php" ]; then
    cat > config/database.php << EOF
<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'restaurant_delivery');
EOF
    echo -e "${GREEN}✓${NC} Created test database configuration"
else
    echo -e "${YELLOW}!${NC} Database configuration already exists"
fi

# Final instructions
echo -e "\n${YELLOW}Setup Complete!${NC}"
echo -e "\nNext steps:"
echo "1. Configure your database credentials in config/database.php"
echo "2. Access test_system.php in your browser to verify the installation"
echo "3. Register an admin user and update their role in the database"
echo "4. Remove setup.sh and test_system.php after successful setup"

# Make the script executable
chmod +x setup.sh

echo -e "\n${GREEN}Setup script is ready to use!${NC}"
