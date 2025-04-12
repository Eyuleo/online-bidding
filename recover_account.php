<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'head.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AuthController.php';

$auth = new AuthController($pdo);
$errors = [];
$success = false;
$recoveredEmail = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recoveryContact = trim($_POST['recovery_contact'] ?? '');
    $recoveryType = trim($_POST['recovery_type'] ?? '');

    if (empty($recoveryContact)) {
        $errors['contact'] = 'Please provide your recovery contact';
    } else {
        $account = $auth->findAccountByRecoveryContact($recoveryContact);
        if ($account) {
            $recoveredEmail = $account['email'];
            $success = true;
        } else {
            $errors['contact'] = 'No account found with this recovery contact';
        }
    }
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'nav.php';
?>

<main>
    <div class="flex min-h-full items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-md space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-bold tracking-tight text-gray-900">
                    Recover Your Account
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Don't remember your email? We'll help you find it using your recovery contact.
                </p>
            </div>

            <?php if ($success && $recoveredEmail): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                <p>Your email address is: <strong><?= htmlspecialchars($recoveredEmail) ?></strong></p>
                <p class="mt-2">You can now <a href="login.php" class="font-medium text-green-700 underline">log in</a>
                    with this email.</p>
            </div>
            <?php else: ?>
            <form class="mt-8 space-y-6" action="" method="POST">
                <div class="space-y-4">
                    <div>
                        <label for="recovery_type" class="block text-sm font-medium text-gray-700">Recovery Contact
                            Type</label>
                        <select name="recovery_type" id="recovery_type"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="email" <?= ($_POST['recovery_type'] ?? '') === 'email' ? 'selected' : '' ?>>
                                Email</option>
                            <option value="phone" <?= ($_POST['recovery_type'] ?? '') === 'phone' ? 'selected' : '' ?>>
                                Phone Number</option>
                        </select>
                    </div>

                    <div>
                        <label for="recovery_contact" class="block text-sm font-medium text-gray-700">Recovery
                            Contact</label>
                        <input type="text" id="recovery_contact" name="recovery_contact"
                            value="<?= htmlspecialchars($_POST['recovery_contact'] ?? '') ?>"
                            class="relative block w-full rounded-md border-0 py-1.5 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:z-10 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 px-3"
                            placeholder="Enter your recovery email or phone number">
                    </div>

                    <?php if (isset($errors['contact'])): ?>
                    <div class="text-red-500 text-sm"><?= htmlspecialchars($errors['contact']) ?></div>
                    <?php endif; ?>

                    <div>
                        <button type="submit"
                            class="group relative flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            Find Account
                        </button>
                    </div>
                </div>
            </form>
            <?php endif; ?>

            <div class="text-center">
                <a href="login.php" class="text-sm text-gray-600 hover:text-gray-500">
                    Remember your email? Log in here
                </a>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'footer.php'; ?>