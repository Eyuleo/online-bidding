<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'head.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'PasswordResetController.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AuthController.php';

$controller = new PasswordResetController($pdo);

// Check if user is already logged in
$auth = new AuthController($pdo);
if ($auth->isLoggedIn()) {
    header('Location: account.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    // First step - validate email
    if (!isset($_POST['answer'])) {
        if (empty($email)) {
            $_SESSION['error'] = 'Email is required.';
            header('Location: reset_password.php');
            exit;
        }
        
        // Check if user exists and has security question
        $user = $controller->getUserByEmail($email);
        if (!$user) {
            $_SESSION['error'] = 'Email not found.';
            header('Location: reset_password.php');
            exit;
        }
        
        $securityQuestion = $controller->getUserSecurityQuestion($user['id']);
        if (!$securityQuestion) {
            $_SESSION['error'] = 'No security question set for this account. Please contact support.';
            header('Location: reset_password.php');
            exit;
        }
        
        // If we get here, the email is valid and has a security question
        // The form will show the security question and password fields
    } else {
        // Second step - validate answer and password
        $answer = $_POST['answer'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($email) || empty($answer) || empty($newPassword) || empty($confirmPassword)) {
            $_SESSION['error'] = 'All fields are required.';
            header('Location: reset_password.php');
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            $_SESSION['error'] = 'Passwords do not match.';
            header('Location: reset_password.php');
            exit;
        }

        // Get user and verify security answer
        $user = $controller->getUserByEmail($email);
        if (!$user || !$controller->verifySecurityAnswer($user['id'], $answer)) {
            $_SESSION['error'] = 'Incorrect security answer.';
            header('Location: reset_password.php');
            exit;
        }

        // Update password
        if ($controller->updateUserPassword($user['id'], $newPassword)) {
            $_SESSION['success'] = 'Password has been reset successfully.';
            header('Location: login.php');
            exit;
        } else {
            $_SESSION['error'] = 'Failed to reset password. Please try again.';
            header('Location: reset_password.php');
            exit;
        }
    }
}

// Include navigation
require_once __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'nav.php';
?>

<main>
    <div class="flex min-h-full items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-md space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-bold tracking-tight text-gray-900">Reset Password</h2>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
            <?php endif; ?>

            <form class="mt-8 space-y-6" action="reset_password.php" method="POST">
                <div class="space-y-4 rounded-md shadow-sm">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="email" name="email" required
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            class="relative block w-full rounded-md border-0 py-1.5 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:z-10 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 px-3">
                    </div>

                    <?php 
                    if (isset($_POST['email'])) {
                        $user = $controller->getUserByEmail($_POST['email']);
                        if ($user) {
                            $securityQuestion = $controller->getUserSecurityQuestion($user['id']);
                            if ($securityQuestion):
                    ?>
                    <div>
                        <label for="security_answer" class="block text-sm font-medium text-gray-700">
                            <?= htmlspecialchars($securityQuestion['question']) ?>
                        </label>
                        <input type="text" id="security_answer" name="answer" required
                            class="relative block w-full rounded-md border-0 py-1.5 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:z-10 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 px-3">
                    </div>

                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                        <input type="password" id="new_password" name="new_password" required
                            class="relative block w-full rounded-md border-0 py-1.5 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:z-10 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 px-3">
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New
                            Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required
                            class="relative block w-full rounded-md border-0 py-1.5 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:z-10 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 px-3">
                    </div>
                    <?php 
                            endif;
                        }
                    }
                    ?>
                </div>

                <div>
                    <button type="submit"
                        class="group relative flex w-full justify-center rounded-md bg-indigo-600 py-2 px-3 text-sm font-semibold text-white hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                        <?php if (!isset($_POST['email'])): ?>
                        Continue
                        <?php else: ?>
                        Reset Password
                        <?php endif; ?>
                    </button>
                </div>
            </form>

            <div class="text-center">
                <p class="text-sm text-gray-600">
                    Remember your password? <a href="login.php"
                        class="font-medium text-indigo-600 hover:text-indigo-500">Log in</a>
                </p>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'footer.php'; ?>