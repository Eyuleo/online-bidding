<?php
require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'head.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AuthController.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AuctionController.php';

$auth = new AuthController($pdo);
$auctionController = new AuctionController($pdo);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Only admin can perform POST actions (delete auction)
    if (!$auth->isAdmin()) {
        header('Location: login.php');
        exit();
    }

    $formType = $_POST['form_type'] ?? '';
    
    switch ($formType) {
        case 'delete_auction':
            $auctionId = $_POST['auction_id'] ?? null;
            if ($auctionId) {
                $result = $auctionController->deleteAuction($auctionId, $auth->getCurrentUser()['id']);
                if ($result['success']) {
                    header('Location: auctions.php?message=Auction deleted successfully');
                    exit();
                } else {
                    $error = $result['error'] ?? 'Failed to delete auction';
                }
            }
            break;
    }
}

// Get current user
$user = $auth->getCurrentUser();
$isAdmin = $auth->isAdmin();
$canBid = $auth->canPlaceBids();

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

// Get bids if admin
$bids = [];
if ($isAdmin) {
    $bids = $auctionController->getAuctionBids($auctionId);
}

require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'nav.php';
?>

<section class="bg-blue-50 px-4 py-10">
    <div class="container mx-auto max-w-7xl">
        <!-- Success/Error Messages -->
        <?php if (isset($_GET['message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6">
            <?= htmlspecialchars($_GET['message']) ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6">
            <?= htmlspecialchars($_GET['error']) ?>
        </div>
        <?php endif; ?>

        <!-- Auction Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <div class="flex items-center gap-4 mb-4">
                        <a href="auctions.php"
                            class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back to Auctions
                        </a>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($auction['title']) ?></h1>
                    <p class="text-gray-600"><?= nl2br(htmlspecialchars($auction['description'])) ?></p>
                </div>
                <?php if ($isAdmin): ?>
                <div class="flex gap-2">
                    <a href="/edit-auction.php?id=<?= $auction['id'] ?>"
                        class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm">
                        Edit
                    </a>
                    <button onclick="showDeleteModal()"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm cursor-pointer">
                        Delete
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div class="flex items-center text-gray-600">
                    <i class="fa-solid fa-calendar-days text-lg mr-2"></i>
                    <span>Starts: <?= date('M d, Y', strtotime($auction['start_date'])) ?></span>
                </div>
                <div class="flex items-center text-orange-700">
                    <i class="fa-solid fa-calendar-days text-lg mr-2"></i>
                    <span>Ends: <?= date('M d, Y', strtotime($auction['end_date'])) ?></span>
                </div>
                <div class="flex items-center text-gray-600">
                    <i class="fa-solid fa-box text-lg mr-2"></i>
                    <span><?= count($auction['items']) ?> item(s)</span>
                </div>
                <div class="flex items-center text-gray-600">
                    <i class="fa-solid fa-gavel text-lg mr-2"></i>
                    <span><?= $auction['auction_type'] === 'buy' ? 'Reverse Auction (Lowest Bid Wins)' : 'Regular Auction (Highest Bid Wins)' ?></span>
                </div>
            </div>

            <?php 
            $now = time();
            $startDate = strtotime($auction['start_date']);
            $endDate = strtotime($auction['end_date']);
            $auctionStatus = '';
            $statusColor = '';

            if ($now < $startDate) {
                $auctionStatus = 'Bidding starts ' . date('M d, Y', $startDate);
                $statusColor = 'yellow';
            } elseif ($now > $endDate) {
                $auctionStatus = 'Auction ended';
                $statusColor = 'red';
            } else {
                $auctionStatus = 'Bidding open';
                $statusColor = 'green';
            }
            ?>

            <div class="mt-4 bg-<?= $statusColor ?>-50 border-l-4 border-<?= $statusColor ?>-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-<?= $statusColor ?>-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-<?= $statusColor ?>-700">
                            <?= $auctionStatus ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!$auth->isLoggedIn()): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        Please <a href="/login.php" class="font-medium underline">log in</a> to place bids.
                    </p>
                </div>
            </div>
        </div>
        <?php elseif (!$isAdmin && isset($user['account_restricted_until']) && $user['account_restricted_until'] > date('Y-m-d H:i:s')): ?>
        <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700">
                        Your account is currently restricted from placing bids.
                        <?php if ($user['account_restricted_until']): ?>
                        This restriction will end on
                        <?= date('M d, Y', strtotime($user['account_restricted_until'])) ?>.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Items Table and Image Slideshow -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
            <!-- Image Slideshow -->
            <div id="imageSlideshow" class="relative h-64 bg-gray-100">
                <div class="absolute inset-0 flex items-center justify-center text-gray-500" id="noImageMessage">
                    No images available
                </div>
                <div class="hidden absolute inset-0" id="slideshowContainer">

                </div>
                <button onclick="previousSlide()"
                    class="absolute left-0 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-2 rounded-r">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button onclick="nextSlide()"
                    class="absolute right-0 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-2 rounded-l">
                    <i class="fas fa-chevron-right"></i>
                </button>
                <div class="absolute bottom-2 left-1/2 transform -translate-x-1/2 flex space-x-2" id="slideDots">

                </div>
            </div>

            <!-- Items Table -->
            <div class="p-6">
                <?php if ($auth->isLoggedIn() && !$isAdmin && $auth->canPlaceBids() && $now >= $startDate && $now <= $endDate): ?>
                <div class="mb-4 flex justify-end">
                    <button type="button" onclick="showBidModal()"
                        class="inline-flex items-center justify-center py-3 px-6 border border-transparent shadow-sm text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-gavel mr-2"></i>
                        Place Bid
                    </button>
                </div>
                <?php endif; ?>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox" id="selectAll"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Item
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Description
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <?= $auction['auction_type'] === 'buy' ? 'Lowest Bid' : 'Highest Bid' ?>
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Starting Price
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($auction['items'] as $item): 
                                $itemImages = [];
                                $stmt = $pdo->prepare('SELECT image_path FROM item_images WHERE item_id = ?');
                                $stmt->execute([$item['id']]);
                                $images = $stmt->fetchAll(PDO::FETCH_COLUMN);

                                // Get best bid for this item
                                $stmt = $pdo->prepare('SELECT ' . ($auction['auction_type'] === 'buy' ? 'MIN' : 'MAX') . '(amount) as best_bid FROM bids WHERE item_id = ?');
                                $stmt->execute([$item['id']]);
                                $bestBid = $stmt->fetch(PDO::FETCH_ASSOC)['best_bid'] ?? $item['starting_price'];

                                // Calculate bid difference percentage
                                $bidDiff = $bestBid - $item['starting_price'];
                                $bidDiffPercent = ($bidDiff / $item['starting_price']) * 100;
                                $isBetterBid = ($auction['auction_type'] === 'buy') ? ($bidDiff > 0) : ($bidDiff < 0);
                            ?>
                            <tr class="hover:bg-gray-50 cursor-pointer"
                                onclick="toggleItemSelection(<?= $item['id'] ?>)">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" name="selected_items[]" value="<?= $item['id'] ?>"
                                        class="item-checkbox rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        data-images='<?= htmlspecialchars(json_encode($images)) ?>'
                                        onclick="event.stopPropagation()">
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($item['name']) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($item['description']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-semibold text-indigo-600">
                                        $<?= number_format($bestBid, 2) ?>
                                        <?php if ($bestBid != $item['starting_price']): ?>
                                        <span
                                            class="text-xs <?= $isBetterBid ? 'text-red-600' : 'text-green-600' ?> ml-1">
                                            <i
                                                class="fas fa-arrow-<?= $isBetterBid ? ($auction['auction_type'] === 'buy' ? 'down' : 'up') : ($auction['auction_type'] === 'sell' ? 'up' : 'down') ?>"></i>
                                            <?= number_format(abs($bidDiffPercent), 1) ?>%
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        $<?= number_format($item['starting_price'], 2) ?></div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p class="text-sm text-gray-600 mt-4">
                        <i class="fas fa-info-circle mr-1"></i>
                        Select items to bid on multiple items at once
                    </p>
                </div>
            </div>
        </div>

        <?php if ($isAdmin && !empty($bids)): ?>
        <!-- Bids List -->
        <div class="bg-white rounded-lg shadow-md p-6 overflow-x-auto">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Auction Bids</h2>
            <table class="min-w-full table-auto">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Item
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Bidder
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Amount
                        </th>
                        <!-- <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th> -->
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Date
                        </th>
                        <!-- <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th> -->
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($bids as $bid): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?= htmlspecialchars($bid['item_name']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?= htmlspecialchars($bid['bidder_name']) ?> | <?= htmlspecialchars($bid['bidder_email']) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            $<?= number_format($bid['amount'], 2) ?>
                        </td>
                        <!-- <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?= $bid['status'] === 'accepted' ? 'bg-green-100 text-green-800' : 
                                    ($bid['status'] === 'rejected' ? 'bg-red-100 text-red-800' : 
                                    'bg-yellow-100 text-yellow-800') ?>">
                                <?= ucfirst($bid['status']) ?>
                            </span>
                        </td> -->
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?= date('M d, Y H:i', strtotime($bid['created_at'])) ?>
                        </td>
                        <!-- <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php if ($bid['status'] === 'pending'): ?>
                            <button onclick="handleBid(<?= $bid['id'] ?>, 'accept')"
                                class="text-green-600 hover:text-green-900 mr-2">Accept</button>
                            <button onclick="handleBid(<?= $bid['id'] ?>, 'reject')"
                                class="text-red-600 hover:text-red-900">Reject</button>
                            <?php endif; ?>
                        </td> -->
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Delete Auction Modal -->
<div id="deleteModal" class="hidden fixed z-10 inset-0 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="hideDeleteModal()"></div>
        <div class="relative bg-white rounded-lg max-w-md w-full p-6">
            <div class="mb-4">
                <h3 class="text-lg font-medium text-gray-900">Delete Auction</h3>
                <p class="mt-2 text-sm text-gray-500">
                    Are you absolutely sure you want to delete this auction? This action cannot be undone.
                    All related items and bids will be permanently removed.
                </p>
            </div>
            <form action="" method="POST" class="mt-4">
                <input type="hidden" name="form_type" value="delete_auction">
                <input type="hidden" name="auction_id" value="<?= $auction['id'] ?>">
                <div class="mt-5 flex justify-end space-x-3">
                    <button type="button" onclick="hideDeleteModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700">
                        Delete Auction
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bid Modal -->
<div id="bidModal" class="hidden fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
    aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
            onclick="hideBidModal()"></div>

        <div
            class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Place Bids
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Enter your bid amounts for the selected items. Each bid must be higher than the current
                                price.
                            </p>
                        </div>
                        <form id="bidModalForm" action="place-bid.php" method="POST" class="mt-4">
                            <div id="bidItemsContainer" class="space-y-4">
                                <!-- Bid inputs will be dynamically inserted here -->
                            </div>
                            <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                                <button type="submit"
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                                    Place Bids
                                </button>
                                <button type="button"
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm"
                                    onclick="hideBidModal()">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentSlide = 0;
let slides = [];

// Initialize slideshow with all images
function initializeSlideshowWithAllImages() {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    let allImages = [];

    checkboxes.forEach(checkbox => {
        const images = JSON.parse(checkbox.dataset.images);
        allImages = allImages.concat(images);
    });

    initializeSlideshow(allImages);
}

function showDeleteAccountModal() {
    document.getElementById('deleteAccountModal').classList.remove('hidden');
}

function hideDeleteAccountModal() {
    document.getElementById('deleteAccountModal').classList.add('hidden');
}

function initializeSlideshow(images) {
    slides = images;
    const container = document.getElementById('slideshowContainer');
    const noImageMessage = document.getElementById('noImageMessage');
    const dotsContainer = document.getElementById('slideDots');

    // Clear previous content
    container.innerHTML = '';
    dotsContainer.innerHTML = '';

    if (images.length === 0) {
        container.classList.add('hidden');
        noImageMessage.classList.remove('hidden');
        return;
    }

    container.classList.remove('hidden');
    noImageMessage.classList.add('hidden');

    // Create slide dots
    images.forEach((_, index) => {
        const dot = document.createElement('button');
        dot.className = 'h-2 w-2 rounded-full bg-white bg-opacity-50';
        dot.onclick = () => showSlide(index);
        dotsContainer.appendChild(dot);
    });

    showSlide(0);
}

function showSlide(index) {
    if (slides.length === 0) return;

    currentSlide = index;
    const container = document.getElementById('slideshowContainer');
    container.innerHTML = `<img src="${slides[index]}" class="absolute inset-0 w-full h-full object-contain">`;

    // Update dots
    const dots = document.getElementById('slideDots').children;
    for (let i = 0; i < dots.length; i++) {
        dots[i].className = i === index ? 'h-2 w-2 rounded-full bg-white' :
            'h-2 w-2 rounded-full bg-white bg-opacity-50';
    }
}

function nextSlide() {
    if (slides.length === 0) return;
    showSlide((currentSlide + 1) % slides.length);
}

function previousSlide() {
    if (slides.length === 0) return;
    showSlide((currentSlide - 1 + slides.length) % slides.length);
}

function toggleItemSelection(itemId) {
    const checkbox = document.querySelector(`input[value="${itemId}"]`);
    checkbox.checked = !checkbox.checked;
    updateSlideshow();
}

// Update slideshow to either show selected images or all images if none selected
function updateSlideshow() {
    const selectedCheckboxes = document.querySelectorAll('.item-checkbox:checked');

    if (selectedCheckboxes.length === 0) {
        // If no items selected, show all images
        initializeSlideshowWithAllImages();
    } else {
        // Show only selected items' images
        let selectedImages = [];
        selectedCheckboxes.forEach(checkbox => {
            const images = JSON.parse(checkbox.dataset.images);
            selectedImages = selectedImages.concat(images);
        });
        initializeSlideshow(selectedImages);
    }
}

// Initialize select all functionality
document.getElementById('selectAll').addEventListener('change', function(e) {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = e.target.checked);
    updateSlideshow();
});

// Initialize slideshow with all images when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeSlideshowWithAllImages();
});

function showBidModal() {
    const selectedItems = document.querySelectorAll('.item-checkbox:checked');
    if (selectedItems.length === 0) {
        alert('Please select at least one item to bid on');
        return;
    }

    // Check if auction has started
    const startDate = new Date('<?= $auction['start_date'] ?>');
    const now = new Date();
    if (startDate > now) {
        alert('Bidding has not started yet. Bidding starts on ' + startDate.toLocaleDateString());
        return;
    }

    // Check if auction has ended
    const endDate = new Date('<?= $auction['end_date'] ?>');
    if (now > endDate) {
        alert('This auction has ended');
        return;
    }

    const container = document.getElementById('bidItemsContainer');
    container.innerHTML = '';

    const isReverseAuction = <?= json_encode($auction['auction_type'] === 'buy') ?>;

    selectedItems.forEach(checkbox => {
        const tr = checkbox.closest('tr');
        const itemName = tr.querySelector('td:nth-child(2)').textContent.trim();
        const currentBid = parseFloat(tr.querySelector('td:nth-child(4)').textContent.replace('$', '').replace(
            ',', ''));
        const minBid = isReverseAuction ?
            (currentBid - 0.01).toFixed(2) : // For reverse auction, bid must be lower
            (currentBid + 0.01).toFixed(2); // For regular auction, bid must be higher

        const itemDiv = document.createElement('div');
        itemDiv.className = 'border-b border-gray-200 pb-4';
        itemDiv.innerHTML = `
            <input type="hidden" name="item_ids[]" value="${checkbox.value}">
            <div class="flex justify-between items-center mb-2">
                <label class="block text-sm font-medium text-gray-700">
                    ${itemName}
                </label>
                <span class="text-sm text-gray-500">
                    Current ${isReverseAuction ? 'Lowest' : 'Highest'} Bid: $${currentBid.toFixed(2)}
                </span>
            </div>
            <div class="mt-1 relative rounded-md shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-gray-500 sm:text-sm">$</span>
                </div>
                <input type="number" 
                       name="bid_amounts[]" 
                       required 
                       step="0.01"
                       ${isReverseAuction ? 'max="' + minBid + '"' : 'min="' + minBid + '"'}
                       value="${minBid}"
                       class="pl-7 block w-full pr-12 sm:text-sm border-gray-300 rounded-md" 
                       placeholder="0.00">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <span class="text-xs text-gray-500">${isReverseAuction ? 'Max' : 'Min'}: $${minBid}</span>
                </div>
            </div>
            <p class="mt-1 text-xs text-gray-500">Your bid must be ${isReverseAuction ? 'lower than' : 'at least'} $${minBid}</p>
        `;
        container.appendChild(itemDiv);
    });

    document.getElementById('bidModal').classList.remove('hidden');
}

function hideBidModal() {
    document.getElementById('bidModal').classList.add('hidden');
}

// Update form validation for multiple items
document.getElementById('bidModalForm').addEventListener('submit', function(e) {
    const amounts = Array.from(this.querySelectorAll('input[name="bid_amounts[]"]'));
    const itemIds = Array.from(this.querySelectorAll('input[name="item_ids[]"]'));
    let hasError = false;

    const isReverseAuction = <?= json_encode($auction['auction_type'] === 'sell') ?>;

    amounts.forEach(input => {
        const amount = parseFloat(input.value);
        const limit = parseFloat(isReverseAuction ? input.max : input.min);

        if (isNaN(amount) || (isReverseAuction ? amount > limit : amount < limit)) {
            hasError = true;
            input.classList.add('border-red-500');
            input.closest('.relative').querySelector('.text-xs').classList.add('text-red-500');
        } else {
            input.classList.remove('border-red-500');
            input.closest('.relative').querySelector('.text-xs').classList.remove('text-red-500');
        }
    });

    if (hasError) {
        e.preventDefault();
        alert(
            `All bid amounts must be ${isReverseAuction ? 'lower than their current lowest bids' : 'higher than their current highest bids'}`
        );
    }
});

function showDeleteModal() {
    document.getElementById('deleteModal').classList.remove('hidden');
}

function hideDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}
</script>

<?php require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'footer.php'; ?>