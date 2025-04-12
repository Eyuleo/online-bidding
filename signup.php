<?php require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'head.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AuthController.php';

$auth = new AuthController($pdo);

// Check if user is already logged in
if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$errors = [];
$success = false;

// Get security questions
$securityQuestions = $auth->getSecurityQuestions();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $questionId = $_POST['question_id'] ?? '';
    $answer = trim($_POST['answer'] ?? '');

    // Validation
    if (empty($name)) {
        $errors['name'] = 'Name is required';
    } elseif (strlen($name) < 3) {
        $errors['name'] = 'Name must be at least 3 characters';
    }

    if (empty($email)) {
        $errors['email'] = 'Email is required';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    }

    if (empty($questionId)) {
        $errors['security'] = 'Security question is required';
    }

    if (empty($answer)) {
        $errors['security'] = 'Security answer is required';
    }

    if (empty($errors)) {
        $result = $auth->signup([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'security_question' => [
                'question_id' => $questionId,
                'answer' => $answer
            ]
        ]);

        if ($result['success']) {
            // Log the user in automatically
            $loginResult = $auth->login($email, $password);
            if ($loginResult['success']) {
                header('Location: index.php');
                exit();
            } else {
                $errors['signup'] = 'Account created but could not log in automatically. Please try logging in.';
                header('refresh:2;url=login.php');
            }
        } else {
            $errors['signup'] = $result['error'];
        }
    }
}
?>
<?php require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'nav.php'; ?>

<main>
    <div class="flex min-h-full items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-md space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-bold tracking-tight text-gray-900">Create an Account</h2>
            </div>

            <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">Account created successfully! Redirecting to login...</span>
            </div>
            <?php endif; ?>

            <form class="space-y-4" action="" method="POST">
                <div class="rounded-md shadow-sm">
                    <div class="mb-2">
                        <label for="name" class="sr-only">Username</label>
                        <input id="name" name="name" type="text" required
                            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                            class="relative block w-full appearance-none rounded border border-gray-300 px-3 py-2 text-gray-900 placeholder-gray-500 focus:z-10 focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                            placeholder="Name">
                        <?php if (isset($errors['name'])): ?>
                        <p class="text-red-500 text-xs mt-1"><?= htmlspecialchars($errors['name']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="mb-2">
                        <label for="email" class="sr-only">Email address</label>
                        <input id="email" name="email" type="email" autocomplete="email" required
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            class="relative block w-full appearance-none rounded border border-gray-300 px-3 py-2 text-gray-900 placeholder-gray-500 focus:z-10 focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                            placeholder="Email address">
                        <?php if (isset($errors['email'])): ?>
                        <p class="text-red-500 text-xs mt-1"><?= htmlspecialchars($errors['email']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="mb-2">
                        <label for="password" class="sr-only">Password</label>
                        <input id="password" name="password" type="password" required
                            class="relative block w-full appearance-none rounded border border-gray-300 px-3 py-2 text-gray-900 placeholder-gray-500 focus:z-10 focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                            placeholder="Password">
                        <?php if (isset($errors['password'])): ?>
                        <p class="text-red-500 text-xs mt-1"><?= htmlspecialchars($errors['password']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="mb-2">
                        <label for="security_question" class="sr-only">Security Question</label>
                        <select id="security_question" name="question_id" required
                            class="relative block w-full appearance-none rounded border border-gray-300 px-3 py-2 text-gray-900 placeholder-gray-500 focus:z-10 focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm">
                            <option value="">Select a Security Question</option>
                            <?php foreach ($securityQuestions as $question): ?>
                            <option value="<?= $question['id'] ?>"><?= htmlspecialchars($question['question']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label for="security_answer" class="sr-only">Security Answer</label>
                        <input id="security_answer" name="answer" type="text" required
                            value="<?= htmlspecialchars($_POST['answer'] ?? '') ?>"
                            class="relative block w-full appearance-none rounded border border-gray-300 px-3 py-2 text-gray-900 placeholder-gray-500 focus:z-10 focus:border-indigo-500 focus:outline-none focus:ring-indigo-500 sm:text-sm"
                            placeholder="Answer to Security Question">
                        <?php if (isset($errors['security'])): ?>
                        <p class="text-red-500 text-xs mt-1"><?= htmlspecialchars($errors['security']) ?></p>
                        <?php endif; ?>
                    </div>

                </div>

                <?php if (isset($errors['signup'])): ?>
                <div class="text-red-500 text-sm text-center"><?= htmlspecialchars($errors['signup']) ?></div>
                <?php endif; ?>

                <div>
                    <button type="submit"
                        class="group cursor-pointer relative flex w-full justify-center rounded-md border border-transparent bg-indigo-700 py-2 px-4 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Create Account
                    </button>
                </div>
            </form>

            <p class="text-gray-600 text-sm text-center">Already have an account? <a class="text-indigo-500"
                    href="login.php">Login</a></p>
        </div>
    </div>
</main>

<?php require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'footer.php'; ?>