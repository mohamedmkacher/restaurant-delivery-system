<?php
require_once __DIR__ . '/../includes/functions.php';

// Ensure user is logged in and is a restaurant manager
checkRole(['restaurant_manager']);

$conn = connectDB();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateToken($_POST['csrf_token']);
    
    if (isset($_POST['add_item']) || isset($_POST['edit_item'])) {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $price = (float)$_POST['price'];
        $category = sanitize($_POST['category']);
        $is_available = isset($_POST['is_available']) ? 1 : 0;
        
        // Handle image upload
        $image_url = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../assets/images/menu/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $file_name = uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $image_url = '/assets/images/menu/' . $file_name;
                }
            }
        }
        
        if (isset($_POST['add_item'])) {
            $stmt = $conn->prepare("
                INSERT INTO menu_items (name, description, price, category, image_url, is_available)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("ssdssi", $name, $description, $price, $category, $image_url, $is_available);
            
            if ($stmt->execute()) {
                flash('Menu item added successfully!', 'success');
            } else {
                flash('Error adding menu item.', 'danger');
            }
        } else {
            $item_id = (int)$_POST['item_id'];
            
            if ($image_url) {
                $stmt = $conn->prepare("
                    UPDATE menu_items 
                    SET name = ?, description = ?, price = ?, category = ?, image_url = ?, is_available = ?
                    WHERE id = ?
                ");
                $stmt->bind_param("ssdssii", $name, $description, $price, $category, $image_url, $is_available, $item_id);
            } else {
                $stmt = $conn->prepare("
                    UPDATE menu_items 
                    SET name = ?, description = ?, price = ?, category = ?, is_available = ?
                    WHERE id = ?
                ");
                $stmt->bind_param("ssdsii", $name, $description, $price, $category, $is_available, $item_id);
            }
            
            if ($stmt->execute()) {
                flash('Menu item updated successfully!', 'success');
            } else {
                flash('Error updating menu item.', 'danger');
            }
        }
    } elseif (isset($_POST['delete_item'])) {
        $item_id = (int)$_POST['item_id'];
        
        // Check if item is used in any orders
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM order_items WHERE menu_item_id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        
        if ($count > 0) {
            // Just mark as unavailable instead of deleting
            $stmt = $conn->prepare("UPDATE menu_items SET is_available = 0 WHERE id = ?");
            $stmt->bind_param("i", $item_id);
            if ($stmt->execute()) {
                flash('Menu item marked as unavailable.', 'warning');
            }
        } else {
            // Safe to delete
            $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
            $stmt->bind_param("i", $item_id);
            if ($stmt->execute()) {
                flash('Menu item deleted successfully!', 'success');
            }
        }
    }
}

// Get all menu items
$menu_items = $conn->query("
    SELECT * FROM menu_items 
    ORDER BY category, name
");

// Get unique categories for filter
$categories = $conn->query("
    SELECT DISTINCT category 
    FROM menu_items 
    ORDER BY category
");

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Menu Management</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
            <i class="fas fa-plus me-2"></i>Add Menu Item
        </button>
    </div>

    <!-- Category Filter -->
    <div class="mb-4">
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-primary active" data-category="all">All</button>
            <?php while ($category = $categories->fetch_assoc()): ?>
                <button type="button" class="btn btn-outline-primary" 
                        data-category="<?php echo htmlspecialchars($category['category']); ?>">
                    <?php echo htmlspecialchars($category['category']); ?>
                </button>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Menu Items Grid -->
    <div class="row g-4">
        <?php while ($item = $menu_items->fetch_assoc()): ?>
            <div class="col-md-6 col-lg-4 menu-item" data-category="<?php echo htmlspecialchars($item['category']); ?>">
                <div class="card h-100">
                    <?php if ($item['image_url']): ?>
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                             class="card-img-top" alt="<?php echo htmlspecialchars($item['name']); ?>"
                             style="height: 200px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($item['name']); ?></h5>
                            <span class="badge bg-<?php echo $item['is_available'] ? 'success' : 'danger'; ?>">
                                <?php echo $item['is_available'] ? 'Available' : 'Unavailable'; ?>
                            </span>
                        </div>
                        <p class="card-text text-muted small mb-2"><?php echo htmlspecialchars($item['category']); ?></p>
                        <p class="card-text"><?php echo htmlspecialchars($item['description']); ?></p>
                        <p class="card-text"><strong>$<?php echo number_format($item['price'], 2); ?></strong></p>
                        
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-primary edit-item" 
                                    data-item='<?php echo json_encode($item); ?>'
                                    data-bs-toggle="modal" data-bs-target="#editItemModal">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" action="" class="d-inline" 
                                  onsubmit="return confirm('Are you sure you want to delete this item?');">
                                <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="delete_item" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title">Add Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="price" class="form-label">Price</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="price" name="price" 
                                   step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="category" class="form-label">Category</label>
                        <input type="text" class="form-control" id="category" name="category" 
                               list="categories" required>
                        <datalist id="categories">
                            <?php
                            $categories->data_seek(0);
                            while ($category = $categories->fetch_assoc()):
                            ?>
                                <option value="<?php echo htmlspecialchars($category['category']); ?>">
                            <?php endwhile; ?>
                        </datalist>
                    </div>
                    
                    <div class="mb-3">
                        <label for="image" class="form-label">Image</label>
                        <input type="file" class="form-control" id="image" name="image" 
                               accept="image/jpeg,image/png,image/gif">
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="is_available" 
                                   name="is_available" checked>
                            <label class="form-check-label" for="is_available">Available</label>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_item" class="btn btn-primary">Add Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Item Modal -->
<div class="modal fade" id="editItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                <input type="hidden" name="item_id" id="edit_item_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Edit Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_price" class="form-label">Price</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="edit_price" name="price" 
                                   step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_category" class="form-label">Category</label>
                        <input type="text" class="form-control" id="edit_category" name="category" 
                               list="categories" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_image" class="form-label">Image</label>
                        <input type="file" class="form-control" id="edit_image" name="image" 
                               accept="image/jpeg,image/png,image/gif">
                        <small class="form-text text-muted">Leave empty to keep current image</small>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="edit_is_available" 
                                   name="is_available">
                            <label class="form-check-label" for="edit_is_available">Available</label>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_item" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.card {
    transition: transform 0.2s ease;
}

.card:hover {
    transform: translateY(-5px);
}

.btn-group {
    opacity: 0.7;
    transition: opacity 0.2s ease;
}

.card:hover .btn-group {
    opacity: 1;
}
</style>

<script>
// Category filter
document.querySelectorAll('[data-category]').forEach(button => {
    button.addEventListener('click', function() {
        const category = this.dataset.category;
        
        // Update active button
        document.querySelectorAll('[data-category]').forEach(btn => {
            btn.classList.remove('active');
        });
        this.classList.add('active');
        
        // Filter items
        document.querySelectorAll('.menu-item').forEach(item => {
            if (category === 'all' || item.dataset.category === category) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
});

// Edit item modal
document.querySelectorAll('.edit-item').forEach(button => {
    button.addEventListener('click', function() {
        const item = JSON.parse(this.dataset.item);
        
        document.getElementById('edit_item_id').value = item.id;
        document.getElementById('edit_name').value = item.name;
        document.getElementById('edit_description').value = item.description;
        document.getElementById('edit_price').value = item.price;
        document.getElementById('edit_category').value = item.category;
        document.getElementById('edit_is_available').checked = item.is_available === 1;
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
