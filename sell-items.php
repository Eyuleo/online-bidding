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

// Get available sell items
$sellItems = $sellItemController->getAvailableSellItems();

require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'nav.php';
?>

<section class="bg-indigo-50 min-h-screen py-12">
    <div class="container mx-auto max-w-4xl">
        <!-- Add New Item Form -->
        <div class="bg-white shadow-lg rounded-lg overflow-hidden mb-8">
            <div class="p-8">
                <h2 class="text-3xl font-bold text-center text-gray-900 mb-8">Add New Sell Item</h2>

                <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <?= htmlspecialchars($_GET['error']) ?>
                </div>
                <?php endif; ?>

                <form action="/process-sell-item.php" method="POST" enctype="multipart/form-data" id="sellItemForm">
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2" for="name">
                                Item Name
                            </label>
                            <input type="text" id="name" name="name"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Enter item name" required />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2" for="description">
                                Item Description
                            </label>
                            <textarea id="description" name="description" rows="3"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                placeholder="Describe the item" required></textarea>
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
                                    class="block w-full rounded-md border-gray-300 pl-7 focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="0.00" required />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Images
                            </label>
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <input type="file" name="images[]" multiple accept="image/*" class="hidden" />
                                    <button type="button"
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 add-images-btn">
                                        Add Images
                                    </button>
                                    <span class="text-sm text-gray-500 selected-count"></span>
                                </div>
                                <div class="image-preview-container grid grid-cols-2 md:grid-cols-4 gap-4 mt-2">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end mt-6">
                        <button type="submit"
                            class="inline-flex justify-center py-3 px-6 border border-transparent shadow-sm text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Add Item
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- List of Sell Items -->
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="p-8">
                <h2 class="text-3xl font-bold text-center text-gray-900 mb-8">Available Sell Items</h2>

                <?php if (empty($sellItems)): ?>
                <p class="text-center text-gray-500">No sell items available.</p>
                <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($sellItems as $item): 
                        $images = explode(',', $item['images']);
                        $primaryImage = $images[0] ?? null;
                    ?>
                    <div class="bg-gray-50 rounded-lg overflow-hidden">
                        <?php if ($primaryImage): ?>
                        <img src="<?= htmlspecialchars($primaryImage) ?>" alt="<?= htmlspecialchars($item['name']) ?>"
                            class="w-full h-48 object-cover">
                        <?php endif; ?>
                        <div class="p-4">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                <?= htmlspecialchars($item['name']) ?>
                            </h3>
                            <p class="text-gray-600 mb-4">
                                <?= htmlspecialchars($item['description']) ?>
                            </p>
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-bold text-indigo-600">
                                    $<?= number_format($item['price'], 2) ?>
                                </span>
                                <button type="button" onclick="selectItem(<?= $item['id'] ?>)"
                                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Select for Auction
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('sellItemForm');
    const addImagesBtn = form.querySelector('.add-images-btn');
    const fileInput = form.querySelector('input[type="file"]');
    const previewContainer = form.querySelector('.image-preview-container');
    const selectedCountSpan = form.querySelector('.selected-count');

    addImagesBtn.addEventListener('click', function() {
        fileInput.click();
    });

    fileInput.addEventListener('change', function() {
        updateImagePreviews(this, previewContainer, selectedCountSpan);
    });

    function updateImagePreviews(input, previewContainer, selectedCountSpan) {
        previewContainer.innerHTML = '';
        const files = Array.from(input.files);

        if (files.length > 0) {
            files.forEach((file, index) => {
                const previewWrapper = document.createElement('div');
                previewWrapper.className = 'relative group';

                const preview = document.createElement('div');
                preview.className =
                    'aspect-w-1 aspect-h-1 w-full overflow-hidden rounded-lg bg-gray-100';

                const img = document.createElement('img');
                img.className = 'object-cover w-full h-full';

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
                    } = input;

                    for (let i = 0; i < files.length; i++) {
                        if (i !== index) {
                            dt.items.add(files[i]);
                        }
                    }

                    input.files = dt.files;
                    updateImagePreviews(input, previewContainer, selectedCountSpan);
                };

                const reader = new FileReader();
                reader.onload = function(e) {
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);

                preview.appendChild(img);
                previewWrapper.appendChild(preview);
                previewWrapper.appendChild(removeBtn);
                previewContainer.appendChild(previewWrapper);
            });

            selectedCountSpan.textContent = `${files.length} image${files.length !== 1 ? 's' : ''} selected`;
        } else {
            selectedCountSpan.textContent = '';
        }
    }
});

function selectItem(itemId) {
    // Store the selected item ID in localStorage
    localStorage.setItem('selectedSellItem', itemId);
    // Redirect to create auction page
    window.location.href = 'create.php?type=sell';
}
</script>

<?php require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'footer.php'; ?>