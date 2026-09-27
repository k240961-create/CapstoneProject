<?php
require_once file_exists(__DIR__ . '/../../includes/session.php') ? __DIR__ . '/../../includes/session.php' : __DIR__ . '/../includes/session.php';
mff_require_role(['admin', 'inventory_manager']);

$pdo = mff_db();
$errors = [];

// 1. Handle Mutations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Create New Product
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);
        $originalPrice = !empty($_POST['original_price']) ? (float)$_POST['original_price'] : null;
        $isSpecial = isset($_POST['is_special']) ? 1 : ($originalPrice && $originalPrice > $price ? 1 : 0);
        $stock = (int) ($_POST['stock'] ?? 0);
        $badge = trim($_POST['badge'] ?? '');
        if ($isSpecial && empty($badge)) {
            $badge = 'Special Deal';
        }
        $imageUrl = trim($_POST['image_url'] ?? '');
        $description = trim($_POST['description'] ?? '');

        // Validation (TC-INV-03, TC-INV-05)
        if ($name === '') $errors[] = 'Product name is required.';
        if ($price <= 0) $errors[] = 'Selling price must be greater than zero ($0.00).';
        if ($originalPrice !== null && $originalPrice <= 0) $errors[] = 'Original price must be greater than zero.';
        if ($stock < 0) $errors[] = 'Stock quantity cannot be negative.';

        if (empty($errors) && $pdo !== null) {
            $stmt = $pdo->prepare(
                'INSERT INTO products (name, category, price, original_price, is_special, stock, low_stock_threshold, badge, image_url, description)
                 VALUES (:name, :category, :price, :original_price, :is_special, :stock, 10, :badge, :image_url, :description)'
            );
            $stmt->execute([
                'name' => $name, 'category' => $category, 'price' => $price,
                'original_price' => $originalPrice, 'is_special' => $isSpecial,
                'stock' => $stock, 'badge' => $badge, 'image_url' => $imageUrl,
                'description' => $description,
            ]);
            mff_set_flash('success', 'Product "' . htmlspecialchars($name) . '" added to the catalogue.');
            header('Location: ' . BASE_URL . '/admin/manage_products.php');
            exit;
        }
    }

    // Update Existing Product (TC-INV-04)
    if ($action === 'update') {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);
        $originalPrice = !empty($_POST['original_price']) ? (float)$_POST['original_price'] : null;
        $isSpecial = isset($_POST['is_special']) ? 1 : ($originalPrice && $originalPrice > $price ? 1 : 0);
        $stock = (int) ($_POST['stock'] ?? 0);
        $badge = trim($_POST['badge'] ?? '');
        if ($isSpecial && empty($badge)) {
            $badge = 'Special Deal';
        }
        $imageUrl = trim($_POST['image_url'] ?? '');
        $description = trim($_POST['description'] ?? '');

        // Validation (TC-INV-03, TC-INV-05)
        if ($productId <= 0) $errors[] = 'Invalid product selected for update.';
        if ($name === '') $errors[] = 'Product name is required.';
        if ($price <= 0) $errors[] = 'Selling price must be greater than zero ($0.00).';
        if ($originalPrice !== null && $originalPrice <= 0) $errors[] = 'Original price must be greater than zero.';
        if ($stock < 0) $errors[] = 'Stock quantity cannot be negative.';

        if (empty($errors) && $pdo !== null) {
            $stmt = $pdo->prepare(
                'UPDATE products SET name = :name, category = :category, price = :price, original_price = :original_price,
                                      is_special = :is_special, stock = :stock, badge = :badge, image_url = :image_url,
                                      description = :description WHERE id = :id'
            );
            $stmt->execute([
                'name' => $name, 'category' => $category, 'price' => $price,
                'original_price' => $originalPrice, 'is_special' => $isSpecial,
                'stock' => $stock, 'badge' => $badge, 'image_url' => $imageUrl,
                'description' => $description, 'id' => $productId,
            ]);
            mff_set_flash('success', 'Product "' . htmlspecialchars($name) . '" updated successfully.');
            header('Location: ' . BASE_URL . '/admin/manage_products.php');
            exit;
        }
    }

    // Quick Restock Product (TC-INV-08)
    if ($action === 'restock') {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $addQuantity = (int) ($_POST['add_stock'] ?? 0);

        if ($productId > 0 && $addQuantity > 0 && $pdo !== null) {
            $stmt = $pdo->prepare('UPDATE products SET stock = stock + :add WHERE id = :id');
            $stmt->execute(['add' => $addQuantity, 'id' => $productId]);
            mff_set_flash('success', 'Replenished +' . $addQuantity . ' units to product stock.');
        } else {
            mff_set_flash('error', 'Please enter a valid stock replenishment amount.');
        }
        header('Location: ' . BASE_URL . '/admin/manage_products.php');
        exit;
    }

    // Add New Category (TC-INV-06 / SRS FR8)
    if ($action === 'add_category') {
        $catName = trim($_POST['category_name'] ?? '');
        $catDesc = trim($_POST['category_desc'] ?? '');

        if ($catName === '') {
            $errors[] = 'Category name is required.';
        } else {
            if (mff_add_category($catName, $catDesc)) {
                mff_set_flash('success', 'New category "' . htmlspecialchars($catName) . '" created successfully.');
            } else {
                $errors[] = 'Category already exists or could not be saved.';
            }
        }
        if (empty($errors)) {
            header('Location: ' . BASE_URL . '/admin/manage_products.php');
            exit;
        }
    }

    // Delete Product
    if ($action === 'delete') {
        $productId = (int) ($_POST['product_id'] ?? 0);
        if ($pdo !== null && $productId > 0) {
            $stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
            $stmt->execute(['id' => $productId]);
            mff_set_flash('success', 'Product removed from catalogue.');
        }
        header('Location: ' . BASE_URL . '/admin/manage_products.php');
        exit;
    }
}

// 2. Editing State
$editingProduct = null;
if (!empty($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $editingProduct = mff_get_product($editId);
}

// 3. Fetch products (including out-of-stock for inventory management) & categories
$products = mff_get_products(true);
$categories = mff_get_categories();

$pageTitle = 'Manage Products & Stock';
$activeNav = 'admin_products';
require file_exists(__DIR__ . '/../../includes/header.php') ? __DIR__ . '/../../includes/header.php' : __DIR__ . '/../includes/header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:1.25rem;margin-bottom:1.5rem;">
  <div>
    <p class="section-eyebrow">Product &amp; Inventory Management</p>
    <h1 class="section-title">Product Catalog &amp; Restock</h1>
    <p style="font-size:0.9rem;color:#56715f;margin-top:0.25rem;">Add, edit details, replenish stock, and manage product departments.</p>
  </div>
  <div style="display:flex;gap:0.75rem;">
    <button type="button" onclick="document.getElementById('add-category-box').scrollIntoView({behavior:'smooth'});" class="btn-outline" style="font-size:0.85rem;">
      + Add Department / Category
    </button>
  </div>
</div>

<?php if (!empty($errors)): ?>
  <div class="flash flash--error" style="margin-top:1rem;max-width:none;">
    <ul style="margin:0;padding-left:1.1rem;">
      <?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<!-- Product Form (Add OR Edit) -->
<div class="card-artisan" id="product-form-box" style="margin-top:1rem;border:<?= $editingProduct ? '2px solid var(--leaf)' : '1px solid var(--line)' ?>;">
  <div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--line);padding-bottom:0.75rem;margin-bottom:1.25rem;">
    <div>
      <h2 style="font-size:1.35rem;font-weight:700;margin:0;color:var(--ink);">
        <?= $editingProduct ? '✏️ Edit Product: ' . htmlspecialchars($editingProduct['name']) : '➕ Add New Product / Special Deal' ?>
      </h2>
      <p style="font-size:0.825rem;color:#56715f;margin:0.2rem 0 0;">
        <?= $editingProduct ? 'Update price, stock levels, promotional discounts, and metadata.' : 'Fill in the grocery product specifications and inventory quantities.' ?>
      </p>
    </div>
    <?php if ($editingProduct): ?>
      <a href="<?= BASE_URL ?>/admin/manage_products.php" class="btn-outline" style="font-size:0.8rem;padding:0.3rem 0.75rem;">
        ✕ Cancel Edit (Add New Instead)
      </a>
    <?php endif; ?>
  </div>

  <form method="post" action="<?= BASE_URL ?>/admin/manage_products.php" class="form-grid">
    <input type="hidden" name="action" value="<?= $editingProduct ? 'update' : 'create' ?>">
    <?php if ($editingProduct): ?>
      <input type="hidden" name="product_id" value="<?= (int) $editingProduct['id'] ?>">
    <?php endif; ?>

    <div class="form-field">
      <label for="p-name">Product Name *</label>
      <input id="p-name" name="name" required type="text" value="<?= htmlspecialchars($_POST['name'] ?? $editingProduct['name'] ?? '') ?>" placeholder="e.g. Organic Strawberries 250g">
    </div>

    <div class="form-field">
      <label for="p-category">Market Department / Category *</label>
      <select id="p-category" name="category" required>
        <?php foreach ($categories as $cat): ?>
          <?php $selected = (($_POST['category'] ?? $editingProduct['category'] ?? '') === $cat) ? 'selected' : ''; ?>
          <option value="<?= htmlspecialchars($cat) ?>" <?= $selected ?>><?= htmlspecialchars($cat) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-field">
      <label for="p-price">Selling Price ($ AUD) *</label>
      <input id="p-price" name="price" required type="number" step="0.01" min="0.01" value="<?= htmlspecialchars($_POST['price'] ?? $editingProduct['price'] ?? '') ?>" placeholder="4.99">
      <p style="font-size:0.75rem;color:#56715f;margin-top:0.2rem;">Must be greater than $0.00.</p>
    </div>

    <div class="form-field">
      <label for="p-original-price">Was / Original Price ($) <span style="font-weight:400;color:#56715f;">(optional discount)</span></label>
      <input id="p-original-price" name="original_price" type="number" step="0.01" min="0.01" value="<?= htmlspecialchars($_POST['original_price'] ?? $editingProduct['original_price'] ?? '') ?>" placeholder="e.g. 6.90">
    </div>

    <div class="form-field">
      <label for="p-stock">Stock Quantity (Units) *</label>
      <input id="p-stock" name="stock" required type="number" min="0" value="<?= htmlspecialchars($_POST['stock'] ?? $editingProduct['stock'] ?? '25') ?>">
      <p style="font-size:0.75rem;color:#56715f;margin-top:0.2rem;">Setting to 0 will automatically hide this product from customers.</p>
    </div>

    <div class="form-field">
      <label for="p-badge">Promotional Badge <span style="font-weight:400;color:#56715f;">(optional)</span></label>
      <input id="p-badge" name="badge" type="text" value="<?= htmlspecialchars($_POST['badge'] ?? $editingProduct['badge'] ?? '') ?>" placeholder="e.g. 25% OFF, Farm Fresh, Organic">
    </div>

    <div class="form-field form-field--full">
      <label for="p-image">Product Image URL</label>
      <input id="p-image" name="image_url" type="url" value="<?= htmlspecialchars($_POST['image_url'] ?? $editingProduct['image_url'] ?? '') ?>" placeholder="https://images.unsplash.com/photo-...">
    </div>

    <div class="form-field form-field--full">
      <label for="p-description">Product Description</label>
      <textarea id="p-description" name="description" rows="2" placeholder="Brief product overview, sourcing details, organic certifications..."><?= htmlspecialchars($_POST['description'] ?? $editingProduct['description'] ?? '') ?></textarea>
    </div>

    <div class="form-field form-field--full" style="display:flex;align-items:center;gap:.5rem;">
      <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-weight:600;">
        <?php $specialChecked = (!empty($_POST['is_special']) || !empty($editingProduct['is_special'])) ? 'checked' : ''; ?>
        <input type="checkbox" name="is_special" value="1" <?= $specialChecked ?>>
        <span>Feature as 🔥 Weekly Special / Promotional Sale</span>
      </label>
    </div>

    <div class="form-field--full" style="display:flex;gap:0.75rem;">
      <button type="submit" class="btn-tomato">
        <?= $editingProduct ? '✓ Update Product' : '+ Add Product to Market' ?>
      </button>
      <?php if ($editingProduct): ?>
        <a href="<?= BASE_URL ?>/admin/manage_products.php" class="btn-outline">Cancel</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- Category Addition Card (TC-INV-06 / SRS FR8) -->
<div class="card-artisan" id="add-category-box" style="margin-top:1.5rem;background:#fdfcf9;border:1px dashed var(--leaf);">
  <h3 style="font-size:1.15rem;font-weight:700;margin:0 0 0.5rem;color:var(--leaf);">
    📂 Add New Department / Category
  </h3>
  <p style="font-size:0.85rem;color:#56715f;margin:0 0 1rem;">
    Extend store departments (e.g. <em>"Fruits &amp; Vegetables"</em>, <em>"Gluten-Free &amp; Organic"</em>). Once added, it will immediately appear across category filters.
  </p>
  <form method="post" action="<?= BASE_URL ?>/admin/manage_products.php" style="display:grid;grid-template-columns:1fr 1.5fr auto;gap:0.75rem;align-items:end;">
    <input type="hidden" name="action" value="add_category">
    <div class="form-field">
      <label for="cat-name">Category Name *</label>
      <input id="cat-name" name="category_name" required type="text" placeholder="e.g. Fruits &amp; Vegetables">
    </div>
    <div class="form-field">
      <label for="cat-desc">Description (Optional)</label>
      <input id="cat-desc" name="category_desc" type="text" placeholder="e.g. Fresh seasonal organic farm fruits and greens">
    </div>
    <button type="submit" class="btn-ink" style="padding:0.5rem 1.25rem;">
      + Save Category
    </button>
  </form>
</div>

<!-- Live Search and Filter Bar for Admin (TC-INV-09) -->
<div style="margin-top:2.5rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
  <div>
    <h2 style="font-size:1.35rem;font-weight:700;margin:0;color:var(--ink);">📦 All Products Catalogue (<?= count($products) ?> Items)</h2>
    <p style="font-size:0.85rem;color:#56715f;margin:0.2rem 0 0;">Filter by department or search products by name</p>
  </div>
  <div style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
    <input 
      id="admin-product-search" 
      type="search" 
      placeholder="🔍 Search products..." 
      style="padding:0.45rem 0.85rem;border-radius:8px;border:1px solid var(--line);font-size:0.875rem;min-width:220px;"
      oninput="filterAdminProducts()">
    <select id="admin-category-filter" style="padding:0.45rem 0.85rem;border-radius:8px;border:1px solid var(--line);font-size:0.875rem;background:#fff;" onchange="filterAdminProducts()">
      <option value="">All Departments</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= htmlspecialchars(strtolower($cat)) ?>"><?= htmlspecialchars($cat) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<!-- Products Table with Quick Edit and Restock (TC-INV-04, TC-INV-08) -->
<div class="data-table-wrap" style="margin-top:1rem;">
  <table class="data-table" id="admin-products-table">
    <caption class="sr-only">Product catalogue</caption>
    <thead>
      <tr>
        <th>Product</th>
        <th>Department</th>
        <th>Price</th>
        <th>Stock Level</th>
        <th>Promotions / Status</th>
        <th style="min-width:200px;">Actions &amp; Restock</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($products as $product): ?>
        <?php 
          $isLow = $product['stock'] <= ($product['low_stock_threshold'] ?? 10); 
          $isOutOfStock = $product['stock'] <= 0;
          $isSpecial = !empty($product['is_special']) || (!empty($product['original_price']) && $product['original_price'] > $product['price']);
          $searchData = strtolower($product['name'] . ' ' . $product['category'] . ' ' . ($product['sku'] ?? ''));
        ?>
        <tr class="admin-prod-row" data-search="<?= htmlspecialchars($searchData) ?>" data-category="<?= htmlspecialchars(strtolower($product['category'])) ?>">
          <td style="font-weight:700;">
            <div style="display:flex;align-items:center;gap:0.6rem;">
              <?php if (!empty($product['image_url'])): ?>
                <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="" style="width:36px;height:36px;object-fit:cover;border-radius:6px;" loading="lazy">
              <?php endif; ?>
              <div>
                <span><?= htmlspecialchars($product['name']) ?></span>
                <?php if ($isSpecial): ?>
                  <span class="product-card__badge product-card__badge--sale" style="position:static;display:inline-block;margin-left:.35rem;font-size:.65rem;padding:.15rem .45rem;">SALE</span>
                <?php endif; ?>
              </div>
            </div>
          </td>
          <td><?= htmlspecialchars($product['category']) ?></td>
          <td>
            <strong><?= mff_money($product['price']) ?></strong>
            <?php if (!empty($product['original_price']) && $product['original_price'] > $product['price']): ?>
              <span style="text-decoration:line-through;color:#8ba593;font-size:.8rem;margin-left:.25rem;"><?= mff_money($product['original_price']) ?></span>
            <?php endif; ?>
          </td>
          <td>
            <strong style="color:<?= $isOutOfStock ? 'var(--tomato)' : ($isLow ? '#d97706' : '#166534') ?>;">
              <?= (int) $product['stock'] ?> units
            </strong>
          </td>
          <td>
            <?php if ($isOutOfStock): ?>
              <span class="status" style="background:#fee2e2;color:#991b1b;font-weight:700;">Out of Stock (Hidden)</span>
            <?php elseif ($isLow): ?>
              <span class="status status-low">Low Stock Alert</span>
            <?php else: ?>
              <span class="status status-instock">Healthy Stock</span>
            <?php endif; ?>
          </td>
          <td>
            <div style="display:flex;align-items:center;gap:0.4rem;flex-wrap:wrap;">
              <!-- 1. Edit Link (TC-INV-04) -->
              <a href="<?= BASE_URL ?>/admin/manage_products.php?edit=<?= (int)$product['id'] ?>" class="btn-outline" style="font-size:0.775rem;padding:0.25rem 0.6rem;" title="Edit product information">
                ✏️ Edit
              </a>

              <!-- 2. Quick Restock Button (TC-INV-08) -->
              <form method="post" action="<?= BASE_URL ?>/admin/manage_products.php" style="display:inline-flex;align-items:center;gap:0.2rem;">
                <input type="hidden" name="action" value="restock">
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <input type="hidden" name="add_stock" value="25">
                <button type="submit" class="btn-outline" style="font-size:0.775rem;padding:0.25rem 0.6rem;color:var(--leaf);border-color:#bbf7d0;" title="Instantly top-up +25 units">
                  +25 Stock
                </button>
              </form>

              <!-- 3. Delete Form -->
              <form method="post" action="<?= BASE_URL ?>/admin/manage_products.php" onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($product['name'])) ?> from catalogue?');" style="display:inline;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <button type="submit" class="btn-outline" style="font-size:0.775rem;padding:0.25rem 0.6rem;color:var(--tomato);border-color:#fecdd3;" title="Delete product">
                  🗑️
                </button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
function filterAdminProducts() {
  const query = document.getElementById('admin-product-search').value.toLowerCase().trim();
  const cat = document.getElementById('admin-category-filter').value.toLowerCase().trim();
  const rows = document.querySelectorAll('.admin-prod-row');

  rows.forEach(row => {
    const searchData = row.dataset.search || '';
    const rowCat = row.dataset.category || '';
    const matchQuery = searchData.includes(query);
    const matchCat = (cat === '' || rowCat === cat);

    if (matchQuery && matchCat) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
}
</script>

<?php require file_exists(__DIR__ . '/../../includes/footer.php') ? __DIR__ . '/../../includes/footer.php' : __DIR__ . '/../includes/footer.php'; ?>
