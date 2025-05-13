# Restaurant Delivery System

A comprehensive web-based restaurant delivery management system built with PHP and MySQL, featuring multiple user roles and real-time order tracking.

## Features

### Client Features
- Browse restaurant menu
- Shopping cart functionality
- Place and track orders
- View order history
- Real-time order status updates

### Restaurant Manager Features
- Dashboard with order statistics
- Menu item management (CRUD operations)
- Image upload for menu items
- Order monitoring and management
- Category-based menu organization

### Order Manager Features
- Comprehensive order flow management
- Assign delivery agents to orders
- Monitor order statuses
- View delivery agent workload
- Real-time order statistics

### Delivery Agent Features
- View assigned deliveries
- Real-time delivery updates
- Mark orders as delivered
- Track earnings
- Google Maps integration for addresses

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser

## Installation

1. Clone the repository to your web server directory:
```bash
git clone [repository-url]
cd restaurant-delivery
```

2. Create a MySQL database:
```sql
CREATE DATABASE restaurant_delivery;
```

3. Import the database schema:
- The database schema will be automatically created when accessing the application for the first time
- Tables will be created through the database.php configuration file

4. Configure the database connection:
- Open `config/database.php`
- Update the database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'restaurant_delivery');
```

5. Set up the file permissions:
```bash
chmod 755 -R ./
chmod 777 -R assets/images/menu/
```

6. Create the first admin user:
- Register a new user through the registration form
- Manually update the user's role in the database:
```sql
UPDATE users SET role = 'restaurant_manager' WHERE username = 'your_username';
```

## Directory Structure

```
restaurant-delivery/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
│       └── menu/
├── auth/
│   ├── login.php
│   ├── register.php
│   └── logout.php
├── client/
│   ├── menu.php
│   ├── cart.php
│   └── orders.php
├── restaurant/
│   ├── dashboard.php
│   ├── menu.php
│   ├── orders.php
│   └── get_order_items.php
├── order-manager/
│   └── dashboard.php
├── delivery/
│   ├── dashboard.php
│   └── update_delivery.php
├── config/
│   └── database.php
├── includes/
│   └── functions.php
├── layouts/
│   ├── header.php
│   └── footer.php
├── index.php
└── 403.php
```

## Security Features

- CSRF Protection
- Input Sanitization
- Password Hashing
- Role-based Access Control
- Secure File Upload Handling
- SQL Injection Prevention
- XSS Protection

## User Roles

1. **Client**
   - Default role for new registrations
   - Can browse menu and place orders
   - Track their own orders

2. **Restaurant Manager**
   - Manage menu items
   - Monitor all orders
   - View sales statistics

3. **Order Manager**
   - Manage order flow
   - Assign delivery agents
   - Monitor delivery status

4. **Delivery Agent**
   - View assigned deliveries
   - Update delivery status
   - Track personal performance

## Development Guidelines

### Adding New Features

1. Follow the existing code structure
2. Implement proper security checks
3. Validate all user inputs
4. Use prepared statements for database queries
5. Maintain consistent error handling

### Code Style

- Use meaningful variable and function names
- Follow PSR-12 coding standards
- Comment complex logic
- Keep functions focused and modular
- Properly sanitize all outputs

## Testing

1. Register users with different roles
2. Test the order flow:
   - Place orders as a client
   - Confirm orders as restaurant manager
   - Assign delivery agents as order manager
   - Update delivery status as delivery agent

## Troubleshooting

### Common Issues

1. **Database Connection Errors**
   - Verify database credentials
   - Check database server status
   - Ensure proper permissions

2. **File Upload Issues**
   - Check directory permissions
   - Verify file size limits
   - Ensure proper directory structure

3. **Session Issues**
   - Check PHP session configuration
   - Clear browser cache
   - Verify cookie settings

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support and questions, please open an issue in the repository or contact the development team.
