<?php require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'head.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AuctionController.php';

$auctionController = new AuctionController($pdo);
// Get latest auctions and limit to 3 first
$auctions = $auctionController->getAuctions('');
$auctions = array_slice($auctions, 0, 3);

// Filter to only include open and upcoming auctions
$latestAuctions = array_filter($auctions, function($auction) {
    $now = time();
    $startDate = strtotime($auction['start_date']);
    $endDate = strtotime($auction['end_date']);
    
    // Only include auctions that are either open or upcoming
    return ($now >= $startDate && $now <= $endDate) || // Open auctions
           ($now < $startDate); // Upcoming auctions
});

// Sort by creation date and get top 6
usort($latestAuctions, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
$latestAuctions = array_slice($latestAuctions, 0, 6);

require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'nav.php';
?>

<section class="bg-indigo-700 py-20 mb-4">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col items-center">
        <div class="text-center">
            <h1 class="text-4xl font-extrabold text-white sm:text-5xl md:text-6xl">
                Online Bidding
            </h1>
            <p class="my-4 text-xl text-white">
                Find the latest auctions and bid on your favorite items
            </p>
        </div>
    </div>
</section>

<section class="px-6 py-10">
    <div class="container-xl lg:container m-auto">
        <h2 class="text-3xl font-bold text-indigo-500 mb-6 text-center">
            Latest Auctions
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php if (empty($latestAuctions)): ?>
            <div class="col-span-3 text-center py-12">
                <h3 class="text-lg font-medium text-gray-900 mb-2">No active or upcoming auctions</h3>
                <p class="text-gray-500">Check back later for new auctions.</p>
            </div>
            <?php else: ?>
            <?php foreach ($latestAuctions as $auction): ?>
            <div
                class="bg-white rounded-xl shadow-lg relative ring-1 ring-gray-300 hover:ring-indigo-500 transition-all duration-300">
                <div class="p-4">
                    <div class="mb-6 flex justify-between items-center">
                        <h3 class="text-xl font-bold"><?= htmlspecialchars($auction['title']) ?></h3>
                        <?php if (strtotime($auction['updated_at']) > strtotime($auction['created_at'])): ?>
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 mt-2">
                            Recently Updated
                        </span>
                        <?php endif; ?>

                        <?php
                        // Determine auction status
                        $now = time();
                        $startDate = strtotime($auction['start_date']);
                        $endDate = strtotime($auction['end_date']);
                        $statusClass = '';
                        $statusText = '';
                        
                        if ($now < $startDate) {
                            $statusClass = 'bg-blue-100 text-blue-800';
                            $statusText = 'Upcoming';
                        } elseif ($now > $endDate) {
                            $statusClass = 'bg-red-100 text-red-800';
                            $statusText = 'Ended';
                        } else {
                            $statusClass = 'bg-green-100 text-green-800';
                            $statusText = 'Open';
                        }
                        ?>
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusClass ?> mt-2 ml-2">
                            <?= $statusText ?>
                        </span>
                    </div>

                    <div class="mb-5">
                        <?= htmlspecialchars(substr($auction['description'], 0, 100)) ?>...
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
                    </div>

                    <div class="border border-gray-100 mb-5"></div>

                    <div class="flex flex-col lg:flex-row justify-between mb-4">
                        <a href="/auction.php?id=<?= $auction['id'] ?>"
                            class="h-[36px] bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-lg text-center text-sm">
                            View Details
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="text-center mt-8">
            <a href="/auctions.php"
                class="inline-flex items-center px-4 py-2 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                View All Auctions
            </a>
        </div>
    </div>
</section>

<?php require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'footer.php'; ?>