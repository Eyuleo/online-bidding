<?php
require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'head.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AuthController.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'SellItemController.php';

$auth = new AuthController($pdo);
$auth->requireAdmin(); // This will redirect if not admin

$sellItemController = new SellItemController($pdo);
$sellItems = $sellItemController->getAvailableSellItems();

require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'nav.php';
?>

<section class="bg-indigo-50 min-h-screen py-12">
    <div class="container mx-auto max-w-4xl">
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="p-8">
                <h2 class="text-3xl font-bold text-center text-gray-900 mb-8">Create New Auction</h2>

                <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <?= htmlspecialchars($_GET['error']) ?>
                </div>
                <?php endif; ?>

                <form action="process-auction.php" method="POST" enctype="multipart/form-data" id="auctionForm">
                    <!-- Auction Details -->
                    <div class="mb-8 bg-gray-50 p-6 rounded-lg">
                        <h3 class="text-xl font-semibold text-gray-900 mb-4">Auction Details</h3>

                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2" for="auction_title">
                                    Auction Title
                                </label>
                                <input type="text" id="auction_title" name="auction_title"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Enter a title for your auction" required />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2" for="auction_description">
                                    Auction Description
                                </label>
                                <textarea id="auction_description" name="auction_description" rows="3"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Provide a general description for your auction" required></textarea>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2" for="start_date">
                                        Start Date
                                    </label>
                                    <input type="date" id="start_date" name="start_date"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2" for="end_date">
                                        End Date
                                    </label>
                                    <input type="date" id="end_date" name="end_date"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required />
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2" for="auction_type">
                                    Auction Type
                                </label>
                                <select id="auction_type" name="auction_type"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required>
                                    <option value="" selected>select auction type</option>
                                    <option value="sell">Regular Auction (Highest Bid Wins)</option>
                                    <option value="buy">Reverse Auction (Lowest Bid Wins)</option>
                                </select>
                                <p class="mt-1 text-sm text-gray-500">
                                    Regular auction is for selling items (highest bid wins).
                                    Reverse auction is for buying items (lowest bid wins).
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Items Section -->
                    <div class="mb-8">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-semibold text-gray-900">Auction Items</h3>
                            <button type="button" id="addItem"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Add Item
                            </button>
                        </div>

                        <!-- Sell Items Section (shown only for sell auctions) -->
                        <div id="sellItemsSection" class="mb-8 hidden">
                            <h4 class="text-lg font-medium text-gray-900 mb-4">Available Sell Items</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <?php foreach ($sellItems as $item): ?>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="flex items-center gap-4">
                                        <input type="checkbox" name="selected_items[]" value="<?= $item['id'] ?>"
                                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                                        <div class="flex-1">
                                            <h4 class="text-lg font-medium text-gray-900">
                                                <?= htmlspecialchars($item['name']) ?></h4>
                                            <p class="text-sm text-gray-500">$<?= number_format($item['price'], 2) ?>
                                            </p>
                                        </div>
                                    </div>
                                    <p class="mt-2 text-sm text-gray-600"><?= htmlspecialchars($item['description']) ?>
                                    </p>
                                    <?php if (!empty($item['images'])): ?>
                                    <div class="mt-2 grid grid-cols-2 gap-2">
                                        <?php foreach (explode(',', $item['images']) as $image): ?>
                                        <img src="<?= htmlspecialchars($image) ?>" alt="Item image"
                                            class="w-full h-24 object-cover rounded">
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div id="itemsContainer">
                            <!-- Template for item form -->
                            <div class="item-form bg-gray-50 p-6 rounded-lg mb-4 hidden">
                                <div class="flex justify-between items-center mb-4">
                                    <h4 class="text-lg font-medium text-gray-900 item-number">Item #1</h4>
                                    <button type="button" class="remove-item text-red-600 hover:text-red-800 hidden">
                                        Remove Item
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Item Name
                                        </label>
                                        <input type="text" name="items[0][name]"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="Enter item name" />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Item Description
                                        </label>
                                        <textarea name="items[0][description]" rows="2"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="Describe the item"></textarea>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Starting Price
                                        </label>
                                        <div class="relative rounded-md shadow-sm">
                                            <div
                                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                <span class="text-gray-500 sm:text-sm">$</span>
                                            </div>
                                            <input type="number" name="items[0][price]" step="0.01" min="0"
                                                class="block w-full rounded-md border-gray-300 pl-7 focus:border-indigo-500 focus:ring-indigo-500"
                                                placeholder="0.00" />
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Images
                                        </label>
                                        <div class="space-y-2">
                                            <div class="flex items-center gap-2">
                                                <input type="file" name="items[0][images][]" multiple accept="image/*"
                                                    class="hidden" />
                                                <button type="button"
                                                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 add-images-btn">
                                                    Add Images
                                                </button>
                                                <span class="text-sm text-gray-500 selected-count"></span>
                                            </div>
                                            <div
                                                class="image-preview-container grid grid-cols-2 md:grid-cols-4 gap-4 mt-2">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                            class="cursor-pointer inline-flex justify-center py-3 px-6 border border-transparent shadow-sm text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Create Auction
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const itemsContainer = document.getElementById('itemsContainer');
    const addItemButton = document.getElementById('addItem');
    const sellItemsSection = document.getElementById('sellItemsSection');
    const auctionType = document.getElementById('auction_type');
    const form = document.getElementById('auctionForm');
    let itemCount = 0;

    // Function to update item numbers
    function updateItemNumbers() {
        const itemForms = document.querySelectorAll('.item-form:not(.hidden)');
        itemForms.forEach((form, index) => {
            const numberElement = form.querySelector('.item-number');
            if (numberElement) {
                numberElement.textContent = `Item #${index + 1}`;
            }
        });
    }

    // Show/hide sell items section based on auction type
    function updateSellItemsSection() {
        if (auctionType.value === 'sell') {
            sellItemsSection.style.display = 'block';
        } else {
            sellItemsSection.style.display = 'none';
            // Clear all selected sell items
            document.querySelectorAll('input[name="selected_items[]"]').forEach(checkbox => {
                checkbox.checked = false;
            });
            // Remove all items from the form that were added from sell items
            document.querySelectorAll('.item-form').forEach(form => {
                if (form.querySelector('input[name$="[sell_item_id]"]')) {
                    form.remove();
                }
            });
        }
        updateItemNumbers();
    }

    auctionType.addEventListener('change', updateSellItemsSection);
    updateSellItemsSection();

    // Initialize image handling for first item
    initializeImageHandling(itemsContainer.querySelector('.item-form'));

    // Add date validation
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');

    startDate.min = new Date().toISOString().split('T')[0];
    endDate.min = new Date().toISOString().split('T')[0];

    startDate.addEventListener('change', function() {
        endDate.min = this.value;
        if (endDate.value && endDate.value < this.value) {
            endDate.value = this.value;
        }
    });

    // Handle sell item selection
    document.querySelectorAll('input[name="selected_items[]"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const itemCard = this.closest('.bg-gray-50');
            const itemId = this.value;
            const itemName = itemCard.querySelector('h4').textContent.trim();
            const itemPrice = parseFloat(itemCard.querySelector('p.text-gray-500').textContent
                .replace(/[^0-9.]/g, ''));
            const itemDescription = itemCard.querySelector('p.text-gray-600').textContent
                .trim();
            const itemImages = Array.from(itemCard.querySelectorAll('img')).map(img => img.src);

            if (this.checked) {
                addSellItemToForm(itemId, itemName, itemDescription, itemPrice, itemImages);
            } else {
                // Remove the item from the form if unchecked
                const itemToRemove = document.querySelector(
                    `input[name^="items"][name$="[sell_item_id]"][value="${itemId}"]`);
                if (itemToRemove) {
                    itemToRemove.closest('.item-form').remove();
                    updateItemNumbers();
                }
            }
        });
    });

    function addSellItemToForm(itemId, name, description, price, images) {
        const template = itemsContainer.querySelector('.item-form').cloneNode(true);
        template.classList.remove('hidden');

        // Update name attributes
        template.querySelectorAll('[name]').forEach(input => {
            const newName = input.name.replace('[0]', `[${itemCount}]`);
            input.name = newName;
            // Add required attribute for actual items
            if (input.name.includes('[name]') || input.name.includes('[price]')) {
                input.required = true;
            }
        });

        // Set values
        const nameInput = template.querySelector('input[name^="items"][name$="[name]"]');
        const descriptionInput = template.querySelector('textarea[name^="items"][name$="[description]"]');
        const priceInput = template.querySelector('input[name^="items"][name$="[price]"]');

        nameInput.value = name;
        descriptionInput.value = description;
        priceInput.value = price.toFixed(2);

        // Add hidden input for sell item ID
        const sellItemIdInput = document.createElement('input');
        sellItemIdInput.type = 'hidden';
        sellItemIdInput.name = `items[${itemCount}][sell_item_id]`;
        sellItemIdInput.value = itemId;
        template.appendChild(sellItemIdInput);

        // Add images
        const previewContainer = template.querySelector('.image-preview-container');
        previewContainer.innerHTML = ''; // Clear any existing images
        images.forEach(image => {
            const img = document.createElement('img');
            img.src = image;
            img.className = 'w-full h-24 object-cover rounded';
            previewContainer.appendChild(img);
        });

        // Show remove button
        const removeButton = template.querySelector('.remove-item');
        removeButton.classList.remove('hidden');
        removeButton.addEventListener('click', function() {
            template.remove();
            updateItemNumbers();
            // Uncheck the checkbox in the sell items section
            const checkbox = document.querySelector(
                `input[name="selected_items[]"][value="${itemId}"]`);
            if (checkbox) {
                checkbox.checked = false;
            }
        });

        itemsContainer.appendChild(template);
        itemCount++;
        updateItemNumbers();
    }

    addItemButton.addEventListener('click', function() {
        const template = itemsContainer.querySelector('.item-form').cloneNode(true);
        template.classList.remove('hidden');

        // Update name attributes
        template.querySelectorAll('[name]').forEach(input => {
            const newName = input.name.replace('[0]', `[${itemCount}]`);
            input.name = newName;
            // Add required attribute for actual items
            if (input.name.includes('[name]') || input.name.includes('[price]')) {
                input.required = true;
            }
            if (input.type !== 'file') {
                input.value = ''; // Clear values except file inputs
            }
        });

        // Clear file input and preview
        const fileInput = template.querySelector('input[type="file"]');
        fileInput.value = '';
        template.querySelector('.image-preview-container').innerHTML = '';
        template.querySelector('.selected-count').textContent = '';

        // Show remove button
        const removeButton = template.querySelector('.remove-item');
        removeButton.classList.remove('hidden');
        removeButton.addEventListener('click', function() {
            template.remove();
            updateItemNumbers();
        });

        // Initialize image handling for new item
        initializeImageHandling(template);

        itemsContainer.appendChild(template);
        itemCount++;
        updateItemNumbers();
    });

    function initializeImageHandling(itemForm) {
        const addImagesBtn = itemForm.querySelector('.add-images-btn');
        const fileInput = itemForm.querySelector('input[type="file"]');
        const previewContainer = itemForm.querySelector('.image-preview-container');
        const selectedCountSpan = itemForm.querySelector('.selected-count');

        addImagesBtn.addEventListener('click', function() {
            fileInput.click();
        });

        fileInput.addEventListener('change', function() {
            updateImagePreviews(this, previewContainer, selectedCountSpan);
        });
    }

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

    // Form validation and submission
    form.addEventListener('submit', function(e) {
        // Validate dates
        const startDate = new Date(document.getElementById('start_date').value);
        const endDate = new Date(document.getElementById('end_date').value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (startDate > endDate) {
            e.preventDefault();
            alert('End date must be after start date');
            return;
        }

        if (startDate < today) {
            e.preventDefault();
            alert('Start date cannot be in the past');
            return;
        }

        // Validate at least one item is selected
        const items = document.querySelectorAll('.item-form:not(.hidden)');
        if (items.length === 0) {
            e.preventDefault();
            alert('Please add at least one item to the auction');
            return;
        }

        // If all validations pass, allow form submission
        console.log('Form submitted');
    });
});
</script>

<?php require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'footer.php'; ?>