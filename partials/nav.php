<?php 
require $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'functions.php';
?>
<nav class="bg-indigo-700 border-b border-indigo-500">
    <div class="mx-auto max-w-7xl px-2 sm:px-6 lg:px-8">
        <div class="flex h-20 items-center justify-evenly">
            <div class="flex flex-1 items-center justify-between md:items-stretch md:justify-start">
                <!-- Logo -->
                <div>
                    <a class="flex flex-shrink-0 items-center mr-4" href="/">
                        <span class="hidden md:block text-white text-2xl font-bold ml-2">Online bidding</span>
                    </a>
                </div>
                <div class="md:ml-auto">
                    <div class="flex space-x-2">
                        <a href="/"
                            class="text-white <?= urlIs('/') ? 'bg-black' : '' ?> hover:bg-gray-900 hover:text-white rounded-md px-3 py-2">Home</a>
                        <a href="/auctions.php"
                            class="text-white <?= urlIs('/auctions.php') ? 'bg-black' : '' ?> hover:bg-gray-900 hover:text-white rounded-md px-3 py-2">Auctions</a>
                        <?php if($_SESSION['user'] ?? false) : ?>
                        <?php if(($_SESSION['user']['role'] ?? '') === 'admin') : ?>
                        <a href="/create.php"
                            class="text-white <?= urlIs('/create.php') ? 'bg-black' : '' ?> hover:bg-gray-900 hover:text-white rounded-md px-3 py-2">Create
                            auction</a>
                        <a href="/users.php"
                            class="text-white <?= urlIs('/users.php') ? 'bg-black' : '' ?> hover:bg-gray-900 hover:text-white rounded-md px-3 py-2">Users</a>
                        <?php endif; ?>
                        <a href="/account.php"
                            class="text-white <?= urlIs('/account.php') ? 'bg-black' : '' ?> hover:bg-gray-900 hover:text-white rounded-md px-3 py-2">My
                            Account</a>
                        <a href="/logout.php"
                            class="text-white hover:bg-gray-900 hover:text-white rounded-md px-3 py-2">Logout</a>
                        <?php else: ?>
                        <a href="/login.php"
                            class="text-white <?= urlIs('/login.php') ? 'bg-black' : '' ?> hover:bg-gray-900 hover:text-white rounded-md px-3 py-2">Login</a>
                        <a href="/signup.php"
                            class="text-white <?= urlIs('/signup.php') ? 'bg-black' : '' ?> hover:bg-gray-900 hover:text-white rounded-md px-3 py-2">Signup</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>