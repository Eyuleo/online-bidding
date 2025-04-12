<?php
require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'head.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AuthController.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AuctionController.php';

$auth = new AuthController($pdo);
$auctionController = new AuctionController($pdo);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formType = $_POST['form_type'] ?? '';
    
    switch ($formType) {
        case 'delete_auction':
            if (!$auth->isAdmin()) {
                header('Location: login.php');
                exit();
            }
            
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

// Get auctions from database
$auctions = $auctionController->getAuctions('active');

require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'nav.php';
?>

<section class="bg-blue-50 px-4 py-10">
    <div class="container-xl lg:container m-auto">
        <!-- Success/Error Messages -->
        <?php if (isset($_GET['message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6">
            <?= htmlspecialchars($_GET['message']) ?>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <div class="flex justify-between items-center mb-6">
            <h2 class="text-3xl font-bold text-indigo-500">
                Browse Auctions
            </h2>
            <?php if ($isAdmin): ?>
            <a href="/create.php"
                class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                Create Auction
            </a>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach ($auctions as $auction): ?>
            <div class="bg-white rounded-xl shadow-md relative">
                <div class="p-4">
                    <div class="mb-4">
                        <h3 class="text-xl font-bold"><?= htmlspecialchars($auction['title']) ?></h3>
                    </div>

                    <div class="mb-5 text-gray-600">
                        <?= nl2br(htmlspecialchars($auction['description'])) ?>
                    </div>

                    <div class="mb-3">
                        <div class="flex items-center text-gray-600 mb-2">
                            <i class="fa-solid fa-calendar-days text-lg mr-2"></i>
                            <span>Starts: <?= date('M d, Y', strtotime($auction['start_date'])) ?></span>
                        </div>
                        <div class="flex items-center text-orange-700">
                            <i class="fa-solid fa-calendar-days text-lg mr-2"></i>
                            <span>Ends: <?= date('M d, Y', strtotime($auction['end_date'])) ?></span>
                        </div>
                    </div>

                    <div class="flex items-center text-sm text-gray-600 mb-5">
                        <i class="fa-solid fa-box text-lg mr-2"></i>
                        <span><?= $auction['item_count'] ?> item(s)</span>
                        <!-- <span>Starting from $<?= number_format($auction['min_price'], 2) ?></span> -->
                    </div>

                    <div class="border border-gray-100 mb-5"></div>

                    <div class="flex justify-between items-center">
                        <a href="/auction.php?id=<?= $auction['id'] ?>"
                            class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm">
                            View Details
                        </a>

                        <?php if ($isAdmin): ?>
                        <div class="flex gap-2">
                            <a href="/edit-auction.php?id=<?= $auction['id'] ?>"
                                class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm">
                                Edit
                            </a>
                            <button onclick="showDeleteModal(<?= $auction['id'] ?>)"
                                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm">
                                Delete
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
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
                <input type="hidden" name="auction_id" id="deleteAuctionId">
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

<script>
function showDeleteModal(auctionId) {
    document.getElementById('deleteAuctionId').value = auctionId;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function hideDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}
</script>

<?php require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'footer.php'; ?>