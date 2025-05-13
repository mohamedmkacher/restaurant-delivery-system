<?php
require_once __DIR__ . '/../includes/functions.php';

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add to cart functionality
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    validateToken($_POST['csrf_token']);
    
    $item_id = (int)$_POST['item_id'];
    $quantity = (int)$_POST['quantity'];
    
    if ($quantity > 0) {
        // Check if item exists and is available
        $conn = connectDB();
        $stmt = $conn->prepare("SELECT id, name, price, is_available FROM menu_items WHERE id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $item = $result->fetch_assoc();
            if ($item['is_available']) {
                // Add to cart or update quantity if already exists
                if (isset($_SESSION['cart'][$item_id])) {
                    $_SESSION['cart'][$item_id]['quantity'] += $quantity;
                } else {
                    $_SESSION['cart'][$item_id] = [
                        'name' => $item['name'],
                        'price' => $item['price'],
                        'quantity' => $quantity
                    ];
                }
                flash('Item added to cart successfully!', 'success');
            } else {
                flash('Sorry, this item is currently unavailable.', 'warning');
            }
        }
    }
}

// Get all available menu items
$conn = connectDB();
$stmt = $conn->prepare("
    SELECT id, name, description, price, category, image_url, is_available 
    FROM menu_items 
    WHERE is_available = 1 
    ORDER BY category, name
");
$stmt->execute();
$result = $stmt->get_result();

// Group items by category
$menu_items = [];
while ($item = $result->fetch_assoc()) {
    $menu_items[$item['category']][] = $item;
}

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="mb-3">Our Menu</h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="cart.php" class="btn btn-primary position-relative">
                <i class="fas fa-shopping-cart me-2"></i>View Cart
                <?php if (!empty($_SESSION['cart'])): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?php echo array_sum(array_column($_SESSION['cart'], 'quantity')); ?>
                    </span>
                <?php endif; ?>
            </a>
        </div>
    </div>

    <!-- Category Navigation -->
    <div class="category-nav mb-4">
        <div class="d-flex flex-wrap gap-2">
            <?php foreach (array_keys($menu_items) as $category): ?>
                <a href="#<?php echo htmlspecialchars($category); ?>" class="btn btn-outline-primary">
                    <?php echo htmlspecialchars($category); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Menu Items by Category -->
    <?php foreach ($menu_items as $category => $items): ?>
        <section id="<?php echo htmlspecialchars($category); ?>" class="mb-5">
            <h2 class="h3 mb-4"><?php echo htmlspecialchars($category); ?></h2>
            <div class="row g-4">
                <?php foreach ($items as $item): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100">
                            <?php if ($item['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                     class="card-img-top" alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     style="height: 200px; object-fit: cover;">
                            <?php endif; ?>
                            <div class="card-body">
                                <h3 class="card-title h5"><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p class="card-text text-muted"><?php echo htmlspecialchars($item['description']); ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="h5 mb-0">$<?php echo number_format($item['price'], 2); ?></span>
                                    <form method="POST" action="" class="d-flex gap-2">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateToken(); ?>">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <input type="number" name="quantity" value="1" min="1" max="10" 
                                               class="form-control form-control-sm" style="width: 70px;">
                                        <button type="submit" name="add_to_cart" class="btn btn-primary btn-sm">
                                            <i class="fas fa-plus me-1"></i>Add
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
</div>

<style>
.category-nav {
    position: sticky;
    top: 70px;
    z-index: 100;
    background-color: rgba(255, 255, 255, 0.9);
    padding: 10px 0;
    backdrop-filter: blur(5px);
}

.card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

@media (max-width: 768px) {
    .category-nav {
        top: 56px;
    }
}
</style>

<script>
// Smooth scroll to category sections
document.querySelectorAll('.category-nav a').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const section = document.querySelector(this.getAttribute('href'));
        const offset = 100; // Adjust based on header height
        const y = section.getBoundingClientRect().top + window.pageYOffset - offset;
        window.scrollTo({top: y, behavior: 'smooth'});
    });
});

// Highlight current category in navigation
window.addEventListener('scroll', function() {
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.category-nav a');
    
    sections.forEach(section => {
        const sectionTop = section.offsetTop - 120;
        const sectionHeight = section.offsetHeight;
        const scroll = window.pageYOffset;
        
        if (scroll >= sectionTop && scroll < sectionTop + sectionHeight) {
            navLinks.forEach(link => {
                link.classList.remove('btn-primary');
                link.classList.add('btn-outline-primary');
                if (link.getAttribute('href') === '#' + section.getAttribute('id')) {
                    link.classList.remove('btn-outline-primary');
                    link.classList.add('btn-primary');
                }
            });
        }
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
