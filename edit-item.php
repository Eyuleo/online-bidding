<?php
require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'head.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AuthController.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'SellItemController.php';

$auth = new AuthController($pdo);
$sellItemController = new SellItemController($pdo);

// Check if user is logged in
if (!$auth->isAdmin()) {
    header('Location: /');
    exit();
}

// Get item ID from URL
$itemId = $_GET['id'] ?? null;
if (!$itemId) {
    header('Location: sell-items.php?error=Item ID is required');
    exit();
}

// Get item details
$item = $sellItemController->getSellItemDetails($itemId);
if (!$item) {
    header('Location: sell-items.php?error=Item not found');
    exit();
}

require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'nav.php';
?>

<section class="bg-indigo-50 min-h-screen py-12">
    <div class="container mx-auto max-w-4xl">
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="p-8">
                <div class="flex justify-between items-center mb-8">
                    <h2 class="text-3xl font-bold text-gray-900">Edit Item</h2>
                    <a href="sell-items.php" class="text-indigo-600 hover:text-indigo-900">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </a>
                </div>

                <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <?= htmlspecialchars($_GET['error']) ?>
                </div>
                <?php endif; ?>

                <form action="process-sell-item.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="item_id" value="<?= htmlspecialchars($item['id']) ?>">

                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2" for="name">
                                Item Name
                            </label>
                            <input type="text" id="name" name="name" value="<?= htmlspecialchars($item['name']) ?>"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2" for="description">
                                Item Description
                            </label>
                            <textarea id="description" name="description" rows="4"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required><?= htmlspecialchars($item['description']) ?></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2" for="price">
                                Price
                            </label>
                            <div class="relative rounded-md shadow-sm">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-gray-500 sm:text-sm">$</span>
                                </div>
                                <input type="number" id="price" name="price" step="0.01" min="0"
                                    value="<?= htmlspecialchars($item['price']) ?>"
                                    class="block w-full rounded-md border-gray-300 pl-7 focus:border-indigo-500 focus:ring-indigo-500"
                                    required />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Current Images
                            </label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                <?php 
                                if (isset($item['images']) && is_array($item['images'])) {
                                    foreach ($item['images'] as $image): 
                                ?>
                                <div class="relative group">
                                    <img src="<?= htmlspecialchars($image['image_path']) ?>" alt="Item image"
                                        class="w-full h-48 object-cover rounded-lg">
                                </div>
                                <?php 
                                    endforeach;
                                }
                                ?>
                            </div>

                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Add New Images
                            </label>
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <input type="file" name="images[]" multiple accept="image/*" class="hidden"
                                        id="imageInput" />
                                    <button type="button"
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                        onclick="document.getElementById('imageInput').click()">
                                        Add Images
                                    </button>
                                    <span class="text-sm text-gray-500" id="selectedCount"></span>
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-2" id="imagePreview">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end mt-6 gap-3">
                        <a href="sell-items.php"
                            class="inline-flex justify-center py-3 px-6 border border-gray-300 shadow-sm text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Cancel
                        </a>
                        <button type="submit" name="submit"
                            class="inline-flex justify-center py-3 px-6 border border-transparent shadow-sm text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const imageInput = document.getElementById('imageInput');
    const imagePreview = document.getElementById('imagePreview');
    const selectedCount = document.getElementById('selectedCount');

    imageInput.addEventListener('change', function() {
        imagePreview.innerHTML = '';
        const files = Array.from(this.files);

        if (files.length > 0) {
            files.forEach((file, index) => {
                const previewWrapper = document.createElement('div');
                previewWrapper.className = 'relative group';

                const preview = document.createElement('div');
                preview.className =
                    'aspect-w-1 aspect-h-1 w-full overflow-hidden rounded-lg bg-gray-100';

                const img = document.createElement('img');
                img.className = 'object-cover w-full h-48';

                const removeBtn = document.createElement('button');
                removeBtn.className =
                    'absolute top-0 right-0 hidden group-hover:flex items-center justify-center w-6 h-6 rounded-full bg-red-500 text-white text-xs m-1';
                removeBtn.innerHTML = '×';
                removeBtn.type = 'button';

                removeBtn.onclick = function(e) {
                    e.stopPropagation();
                    const dt = new DataTransfer();
                    const {
                        files
                    } = imageInput;

                    for (let i = 0; i < files.length; i++) {
                        if (i !== index) {
                            dt.items.add(files[i]);
                        }
                    }

                    imageInput.files = dt.files;
                    imageInput.dispatchEvent(new Event('change'));
                };

                const reader = new FileReader();
                reader.onload = function(e) {
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);

                preview.appendChild(img);
                previewWrapper.appendChild(preview);
                previewWrapper.appendChild(removeBtn);
                imagePreview.appendChild(previewWrapper);
            });

            selectedCount.textContent =
                `${files.length} image${files.length !== 1 ? 's' : ''} selected`;
        } else {
            selectedCount.textContent = '';
        }
    });
});
</script>

<?php require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'footer.php'; ?>