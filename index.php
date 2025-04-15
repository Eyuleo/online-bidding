<?php require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'head.php';
 ?>
<?php require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'nav.php';
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

<section class="px-6 py-10 ">
    <div class="container-xl lg:container m-auto">
        <h2 class="text-3xl font-bold text-indigo-500 mb-6 text-center">
            Latest Auctions
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white border border-gray-100 rounded shadow-lg ring-1 ring-gray-200 relative">
                <div class="p-4">
                    <div class="mb-6">
                        <h3 class="text-xl font-bold">Used Car Auction</h3>
                    </div>

                    <div class="mb-5">
                        Bid on a used car in good condition starting at 20,000.
                    </div>
                    <div class="border border-gray-100 mb-5"></div>

                    <div class="flex flex-col lg:flex-row justify-between mb-4">
                        <a href="/auction?id=1"
                            class="h-[36px] bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-lg text-center text-sm">
                            Read More
                        </a>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-gray-100 rounded shadow-lg ring-1 ring-gray-200 relative">
                <div class="p-4">
                    <div class="mb-6">
                        <h3 class="text-xl font-bold">Office Furniture Auction</h3>
                    </div>

                    <div class="mb-5">
                        We are looking to buy office furniture in bulk, starting at 500.
                    </div>
                    <div class="border border-gray-100 mb-5"></div>

                    <div class="flex flex-col lg:flex-row justify-between mb-4">
                        <a href="/auction?id=2"
                            class="h-[36px] bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-lg text-center text-sm">
                            Read More
                        </a>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-gray-100 rounded shadow-lg ring-1 ring-gray-200 relative">
                <div class="p-4">
                    <div class="mb-6">
                        <h3 class="text-xl font-bold">Computers and Electronics</h3>
                    </div>

                    <div class="mb-5">
                        Looking for computers and electronics in bulk, starting at 1,000.
                    </div>
                    <div class="border border-gray-100 mb-5"></div>

                    <div class="flex flex-col lg:flex-row justify-between mb-4">
                        <a href="/auction?id=3"
                            class="h-[36px] bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-lg text-center text-sm">
                            Read More
                        </a>
                    </div>
                </div>

            </div>
</section>

<?php require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'footer.php';
 ?>