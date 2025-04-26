<?php require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'head.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AuthController.php';

$auth = new AuthController($pdo);

// Check if user is already logged in
if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email)) {
        $errors['email'] = 'Email is required';
    }
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }

    if (empty($errors)) {
        $result = $auth->login($email, $password);
        
        if ($result['success']) {
            header('Location: index.php');
            exit();
        } else {
            $errors['login'] = $result['error'];
        }
    }
}
?>
<?php require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'nav.php';
 ?>

<main>
    <div class="flex min-h-full items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-md space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-bold tracking-tight text-gray-900">Log In</h2>
            </div>

            <form class="space-y-4" action="" method="POST">
                <div class="rounded-md shadow-sm">
                    <div class="mb-2">
                        <label for="email" class="sr-only">Email address</label>
                        <input id="email" name="email" type="email" autocomplete="email" required
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            class="relative block w-full appearance-none rounded border border-gray-300 px-3 py-2 text-gray-900 placeholder-gray-500 focus:z-10 focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                            placeholder="Email address">
                    </div>

                    <div>
                        <label for="password" class="sr-only">Password</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" required
                            class="relative block w-full appearance-none rounded border border-gray-300 px-3 py-2 text-gray-900 placeholder-gray-500 focus:z-10 focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                            placeholder="Password">
                    </div>
                </div>
                <?php if (isset($errors['login'])): ?>
                <li class="text-red-500 text-xs mt-2 list-none"><?= htmlspecialchars($errors['login']) ?></li>
                <?php endif; ?>
                <?php if (isset($errors['email'])): ?>
                <li class="text-red-500 text-xs mt-2 list-none"><?= htmlspecialchars($errors['email']) ?></li>
                <?php endif; ?>
                <?php if (isset($errors['password'])): ?>
                <li class="text-red-500 text-xs mt-2 list-none"><?= htmlspecialchars($errors['password']) ?></li>
                <?php endif; ?>
                <div class="flex items-center justify-between">
                    <div class="text-sm">
                        <a href="reset_password.php" class="font-medium text-indigo-600 hover:text-indigo-500">
                            Forgot password?
                        </a>
                    </div>
                    <!-- <div class="text-sm">
                        <a href="recover_account.php" class="font-medium text-indigo-600 hover:text-indigo-500">
                            Forgot email?
                        </a>
                    </div> -->
                </div>
                <div>
                    <button type="submit"
                        class="group cursor-pointer relative flex w-full justify-center rounded-md border border-transparent bg-indigo-700 py-2 px-4 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Log In
                    </button>
                </div>
            </form>

            <div class="text-center space-y-2">
                <p class="text-gray-600 text-sm">Don't have an account? <a class="text-indigo-500"
                        href="signup.php">Signup</a></p>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'footer.php';
 ?>