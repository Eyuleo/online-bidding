<?php
require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'head.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AuthController.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AuctionController.php';

$auth = new AuthController($pdo);
$auth->requireAdmin(); // This will redirect if not admin

$auctionController = new AuctionController($pdo);

// Get auction ID from URL
$auctionId = $_GET['id'] ?? null;
if (!$auctionId) {
    header('Location: auctions.php');
    exit();
}

// Get auction details
$auction = $auctionController->getAuctionDetails($auctionId);
if (!$auction) {
    header('Location: auctions.php');
    exit();
}

require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'nav.php';
?>

<section class="bg-indigo-50 min-h-screen py-12">
    <div class="container mx-auto max-w-4xl">
        <div class="bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="p-8">
                <h2 class="text-3xl font-bold text-center text-gray-900 mb-8">Edit Auction</h2>

                <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <?= htmlspecialchars($_GET['error']) ?>
                </div>
                <?php endif; ?>

                <form action="/process-auction-edit.php" method="POST" enctype="multipart/form-data" id="auctionForm">
                    <input type="hidden" name="auction_id" value="<?= $auction['id'] ?>">

                    <!-- Auction Details -->
                    <div class="mb-8 bg-gray-50 p-6 rounded-lg">
                        <h3 class="text-xl font-semibold text-gray-900 mb-4">Auction Details</h3>

                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2" for="auction_title">
                                    Auction Title
                                </label>
                                <input type="text" id="auction_title" name="auction_title"
                                    value="<?= htmlspecialchars($auction['title']) ?>"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Enter a title for your auction" required />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2" for="auction_description">
                                    Auction Description
                                </label>
                                <textarea id="auction_description" name="auction_description" rows="3"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Provide a general description for your auction"
                                    required><?= htmlspecialchars($auction['description']) ?></textarea>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2" for="start_date">
                                        Start Date
                                    </label>
                                    <input type="date" id="start_date" name="start_date"
                                        value="<?= date('Y-m-d', strtotime($auction['start_date'])) ?>"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2" for="end_date">
                                        End Date
                                    </label>
                                    <input type="date" id="end_date" name="end_date"
                                        value="<?= date('Y-m-d', strtotime($auction['end_date'])) ?>"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Items Section -->
                    <div class="mb-8 bg-gray-50 p-6 rounded-lg">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-semibold text-gray-900">Auction Items</h3>
                            <button type="button" onclick="addItem()"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                <i class="fas fa-plus mr-2"></i>
                                Add Item
                            </button>
                        </div>

                        <div id="itemsContainer">
                            <?php foreach ($auction['items'] as $index => $item): ?>
                            <div class="item-entry mb-6 p-4 border border-gray-200 rounded-lg" data-index="<?= $index ?>">
                                <input type="hidden" name="items[<?= $index ?>][id]" value="<?= $item['id'] ?>">

                                <div class="flex justify-between mb-4">
                                    <h4 class="text-lg font-medium text-gray-900">Item #<?= $index + 1 ?></h4>
                                    <button type="button" onclick="removeItem(this)"
                                        class="text-red-600 hover:text-red-800">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Item Name
                                        </label>
                                        <input type="text" name="items[<?= $index ?>][name]"
                                            value="<?= htmlspecialchars($item['name']) ?>"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            required />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Item Description
                                        </label>
                                        <textarea name="items[<?= $index ?>][description]" rows="2"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            required><?= htmlspecialchars($item['description']) ?></textarea>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Starting Price ($)
                                        </label>
                                        <input type="number" name="items[<?= $index ?>][price]" step="0.01" min="0"
                                            value="<?= $item['starting_price'] ?>"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            required />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Images
                                        </label>
                                        <div class="mb-2">
                                            <div class="grid grid-cols-4 gap-2">
                                                <?php
                                                $stmt = $pdo->prepare('SELECT id, image_path FROM item_images WHERE item_id = ?');
                                                $stmt->execute([$item['id']]);
                                                $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                                foreach ($images as $image): ?>
                                                <div class="relative group">
                                                    <img src="<?= htmlspecialchars($image['image_path']) ?>" alt="Item image"
                                                        class="w-full h-24 object-cover rounded">
                                                    <input type="hidden" name="items[<?= $index ?>][existing_images][]"
                                                        value="<?= htmlspecialchars($image['image_path']) ?>">
                                                    <input type="hidden" name="items[<?= $index ?>][existing_image_ids][]"
                                                        value="<?= htmlspecialchars($image['id']) ?>">
                                                    <button type="button" onclick="removeImage(this)"
                                                        class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <input type="file" name="items[<?= $index ?>][images][]" multiple
                                            accept="image/*"
                                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                                        <p class="mt-1 text-xs text-gray-500">Upload new images to add to existing ones.</p>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-4">
                        <a href="/auction.php?id=<?= $auction['id'] ?>"
                            class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit"
                            class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            Update Auction
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
function addItem() {
    const container = document.getElementById('itemsContainer');
    const itemCount = container.children.length;

    const itemHtml = `
        <div class="item-entry mb-6 p-4 border border-gray-200 rounded-lg" data-index="${itemCount}">
            <div class="flex justify-between mb-4">
                <h4 class="text-lg font-medium text-gray-900">Item #${itemCount + 1}</h4>
                <button type="button" onclick="removeItem(this)"
                    class="text-red-600 hover:text-red-800">
                    <i class="fas fa-trash"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Item Name
                    </label>
                    <input type="text" name="items[${itemCount}][name]"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Item Description
                    </label>
                    <textarea name="items[${itemCount}][description]" rows="2"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Starting Price ($)
                    </label>
                    <input type="number" name="items[${itemCount}][price]" step="0.01" min="0"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Images
                    </label>
                    <input type="file" name="items[${itemCount}][images][]" multiple accept="image/*"
                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                    <p class="mt-1 text-xs text-gray-500">Upload images for this item.</p>
                </div>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', itemHtml);
    updateItemNumbers();
}

function removeItem(button) {
    button.closest('.item-entry').remove();
    updateItemNumbers();
}

function updateItemNumbers() {
    const items = document.querySelectorAll('.item-entry');
    items.forEach((item, index) => {
        item.querySelector('h4').textContent = `Item #${index + 1}`;

        // Update input names
        const inputs = item.querySelectorAll('input, textarea');
        inputs.forEach(input => {
            if (input.name) {
                input.name = input.name.replace(/items\[\d+\]/, `items[${index}]`);
            }
        });
    });
}

function removeImage(button) {
    const imageContainer = button.closest('.relative');
    const imagePath = imageContainer.querySelector('input[type="hidden"]').value;
    
    // Add to deleted images array
    const itemIndex = imageContainer.closest('.item-entry').dataset.index;
    if (!window.deletedImages) {
        window.deletedImages = {};
    }
    if (!window.deletedImages[itemIndex]) {
        window.deletedImages[itemIndex] = [];
    }
    window.deletedImages[itemIndex].push(imagePath);
    
    // Remove the image container
    imageContainer.remove();
}

// Validate dates before form submission
document.getElementById('auctionForm').addEventListener('submit', function(e) {
    const startDate = new Date(document.getElementById('start_date').value);
    const endDate = new Date(document.getElementById('end_date').value);

    if (endDate <= startDate) {
        e.preventDefault();
        alert('End date must be after start date');
    }

    if (window.deletedImages) {
        Object.entries(window.deletedImages).forEach(([index, images]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `items[${index}][deleted_images][]`;
            input.value = JSON.stringify(images);
            this.appendChild(input);
        });
    }
});
</script>

<?php require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'footer.php'; ?>