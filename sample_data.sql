-- Sample data for restaurant delivery system

-- Clear existing data
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE users;
TRUNCATE TABLE menu_items;
TRUNCATE TABLE orders;
TRUNCATE TABLE order_items;
SET FOREIGN_KEY_CHECKS = 1;

-- Sample users
INSERT INTO users (username, email, password, role) VALUES
-- Password for all users is 'password123'
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'restaurant_manager'),
('manager', 'manager@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'order_manager'),
('delivery1', 'delivery1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'delivery_agent'),
('delivery2', 'delivery2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'delivery_agent'),
('john', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client'),
('jane', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client');

-- Sample menu items
INSERT INTO menu_items (name, description, price, category, image_url, is_available) VALUES
-- Appetizers
('Spring Rolls', 'Fresh vegetables wrapped in rice paper', 5.99, 'Appetizers', '/assets/images/menu/spring-rolls.jpg', 1),
('Buffalo Wings', 'Spicy chicken wings with blue cheese dip', 8.99, 'Appetizers', '/assets/images/menu/buffalo-wings.jpg', 1),
('Garlic Bread', 'Toasted bread with garlic butter', 4.99, 'Appetizers', '/assets/images/menu/garlic-bread.jpg', 1),

-- Main Courses
('Margherita Pizza', 'Classic tomato and mozzarella pizza', 12.99, 'Main Courses', '/assets/images/menu/margherita.jpg', 1),
('Beef Burger', 'Angus beef patty with lettuce and tomato', 11.99, 'Main Courses', '/assets/images/menu/burger.jpg', 1),
('Grilled Salmon', 'Fresh salmon with lemon butter sauce', 16.99, 'Main Courses', '/assets/images/menu/salmon.jpg', 1),
('Chicken Alfredo', 'Creamy pasta with grilled chicken', 13.99, 'Main Courses', '/assets/images/menu/alfredo.jpg', 1),

-- Salads
('Caesar Salad', 'Romaine lettuce with Caesar dressing', 7.99, 'Salads', '/assets/images/menu/caesar.jpg', 1),
('Greek Salad', 'Mixed vegetables with feta cheese', 8.99, 'Salads', '/assets/images/menu/greek-salad.jpg', 1),

-- Desserts
('Chocolate Cake', 'Rich chocolate layer cake', 6.99, 'Desserts', '/assets/images/menu/chocolate-cake.jpg', 1),
('Cheesecake', 'New York style cheesecake', 5.99, 'Desserts', '/assets/images/menu/cheesecake.jpg', 1),

-- Beverages
('Soft Drink', 'Choice of Coke, Sprite, or Fanta', 2.99, 'Beverages', '/assets/images/menu/soft-drink.jpg', 1),
('Iced Tea', 'Fresh brewed iced tea', 2.99, 'Beverages', '/assets/images/menu/iced-tea.jpg', 1);

-- Sample orders
INSERT INTO orders (client_id, status, total_amount, delivery_address, delivery_agent_id, created_at) VALUES
-- Completed orders
((SELECT id FROM users WHERE username = 'john'), 'delivered', 31.96, '123 Main St, Apt 4B, City', 
 (SELECT id FROM users WHERE username = 'delivery1'), DATE_SUB(NOW(), INTERVAL 2 HOUR)),
((SELECT id FROM users WHERE username = 'jane'), 'delivered', 25.97, '456 Oak Ave, Suite 7, City', 
 (SELECT id FROM users WHERE username = 'delivery2'), DATE_SUB(NOW(), INTERVAL 1 HOUR)),

-- Active orders
((SELECT id FROM users WHERE username = 'john'), 'preparing', 42.95, '123 Main St, Apt 4B, City', 
 NULL, DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
((SELECT id FROM users WHERE username = 'jane'), 'confirmed', 29.97, '456 Oak Ave, Suite 7, City', 
 NULL, DATE_SUB(NOW(), INTERVAL 15 MINUTE)),
((SELECT id FROM users WHERE username = 'john'), 'pending', 18.98, '123 Main St, Apt 4B, City', 
 NULL, NOW());

-- Sample order items
INSERT INTO order_items (order_id, menu_item_id, quantity, price_at_time)
SELECT 
    o.id,
    m.id,
    FLOOR(RAND() * 3) + 1,
    m.price
FROM orders o
CROSS JOIN menu_items m
WHERE m.id <= 5
GROUP BY o.id, m.id;

-- Add some order items for specific orders
INSERT INTO order_items (order_id, menu_item_id, quantity, price_at_time)
SELECT 
    o.id,
    m.id,
    1,
    m.price
FROM orders o
CROSS JOIN menu_items m
WHERE m.category = 'Beverages'
AND o.status IN ('pending', 'confirmed')
GROUP BY o.id, m.id;
