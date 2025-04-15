<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'head.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AuthController.php';

$auth = new AuthController($pdo);

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user = $auth->getCurrentUser();
$errors = [];
$success = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formType = $_POST['form_type'] ?? '';

    switch ($formType) {
        case 'profile':
            $result = $auth->updateProfile($user['id'], [
                'name' => trim($_POST['name'] ?? ''),
                'email' => trim($_POST['email'] ?? '')
            ]);
            
            if (is_array($result) && !$result['success']) {
                $errors['profile'] = $result['error'];
            } else if ($result) {
                $success['profile'] = 'Profile updated successfully';
                // Refresh user data
                $user = $auth->getCurrentUser();
            } else {
                $errors['profile'] = 'Failed to update profile';
            }
            break;

        case 'security':
            $questionId = $_POST['question_id'] ?? '';
            $answer = $_POST['answer'] ?? '';
            
            if (empty($questionId) || empty($answer)) {
                $errors['security'] = 'Both question and answer are required';
            } else {
                $result = $auth->updateSecurityAnswers($user['id'], $questionId, $answer);
                
                if ($result) {
                    $success['security'] = 'Security question updated successfully';
                } else {
                    $errors['security'] = 'Failed to update security question';
                }
            }
            break;

        case 'password':
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($currentPassword)) {
                $errors['password'] = 'Current password is required';
            } elseif (empty($newPassword)) {
                $errors['password'] = 'New password is required';
            } elseif (strlen($newPassword) < 8) {
                $errors['password'] = 'New password must be at least 8 characters';
            } elseif ($newPassword !== $confirmPassword) {
                $errors['password'] = 'New passwords do not match';
            } else {
                $result = $auth->updatePassword($user['id'], $currentPassword, $newPassword);
                if ($result) {
                    $success['password'] = 'Password updated successfully';
                } else {
                    $errors['password'] = 'Current password is incorrect';
                }
            }
            break;

        case 'delete_account':
            $password = $_POST['confirm_password'] ?? '';
            if (empty($password)) {
                $errors['delete_account'] = 'Password is required to delete account';
            } else {
                $result = $auth->deleteAccount($user['id'], $password);
                if ($result['success']) {
                    header('Location: login.php');
                    exit();
                } else {
                    $errors['delete_account'] = $result['error'];
                }
            }
            break;

        case 'recovery':
            $recoveryContact = trim($_POST['recovery_contact'] ?? '');
            $recoveryType = trim($_POST['recovery_type'] ?? '');
            
            if (empty($recoveryContact)) {
                $errors['recovery'] = 'Recovery contact is required';
            } else {
                // Validate contact format based on type
                $isValid = true;
                if ($recoveryType === 'email' && !filter_var($recoveryContact, FILTER_VALIDATE_EMAIL)) {
                    $errors['recovery'] = 'Invalid email format';
                    $isValid = false;
                } elseif ($recoveryType === 'phone' && !preg_match('/^\+?[1-9]\d{1,14}$/', $recoveryContact)) {
                    $errors['recovery'] = 'Invalid phone number format';
                    $isValid = false;
                }

                if ($isValid) {
                    $result = $auth->updateRecoveryContacts($user['id'], $recoveryContact, $recoveryType);
                    
                    if ($result) {
                        $success['recovery'] = 'Recovery contact updated successfully';
                    } else {
                        $errors['recovery'] = 'Failed to update recovery contact';
                    }
                }
            }
            break;
    }
}

// Get security questions and current answer
$securityQuestions = $auth->getSecurityQuestions();
$userSecurityQuestion = $auth->getUserSecurityQuestion($user['id']);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'nav.php';
?>

<main class="py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">Account Settings</h1>

            <?php if ($user['is_restricted']): ?>
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            Your account is currently restricted until
                            <?= htmlspecialchars($user['restriction_end']) ?>.
                            During this period, you can browse but cannot place bids.
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Profile Section -->
            <div class="bg-white shadow rounded-lg mb-6 p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Profile Information</h2>
                <?php if (isset($success['profile'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    <?= htmlspecialchars($success['profile']) ?>
                </div>
                <?php endif; ?>
                <?php if (isset($errors['profile'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <?= htmlspecialchars($errors['profile']) ?>
                </div>
                <?php endif; ?>
                <form action="" method="POST">
                    <input type="hidden" name="form_type" value="profile">
                    <div class="space-y-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Username</label>
                            <input type="text" name="name" id="name" required
                                value="<?= htmlspecialchars($user['name']) ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" name="email" id="email" required
                                value="<?= htmlspecialchars($user['email']) ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <button type="submit"
                                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Update Profile
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Security Question -->
            <div class="bg-white shadow rounded-lg mb-6 p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Security Question</h2>
                <?php if (isset($success['security'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    <?= htmlspecialchars($success['security']) ?>
                </div>
                <?php endif; ?>
                <?php if (isset($errors['security'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <?= htmlspecialchars($errors['security']) ?>
                </div>
                <?php endif; ?>
                <form action="" method="POST">
                    <input type="hidden" name="form_type" value="security">
                    <div class="space-y-4">
                        <div>
                            <label for="security_question" class="block text-sm font-medium text-gray-700">
                                Select Security Question
                            </label>
                            <select name="question_id" id="security_question" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <?php foreach ($securityQuestions as $question): ?>
                                <option value="<?= $question['id'] ?>"
                                    <?= ($userSecurityQuestion && $userSecurityQuestion['id'] == $question['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($question['question']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="security_answer" class="block text-sm font-medium text-gray-700">
                                Answer
                            </label>
                            <input type="text" name="answer" id="security_answer" required
                                value="<?= htmlspecialchars($userSecurityQuestion['answer'] ?? '') ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <button type="submit"
                                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Update Security Question
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Recovery Contact Information -->
            <div class="bg-white shadow rounded-lg mb-6 p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Recovery Email</h2>
                <?php if (empty($user['recovery_contact'])): ?>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                No recovery email set. We strongly recommend adding a recovery email to help you
                                regain access to your account if you forget your login email.
                            </p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (isset($success['recovery'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    <?= htmlspecialchars($success['recovery']) ?>
                </div>
                <?php endif; ?>
                <?php if (isset($errors['recovery'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <?= htmlspecialchars($errors['recovery']) ?>
                </div>
                <?php endif; ?>
                <form action="" method="POST">
                    <input type="hidden" name="form_type" value="recovery">
                    <input type="hidden" name="recovery_type" value="email">
                    <div class="space-y-4">
                        <div>
                            <label for="recovery_contact" class="block text-sm font-medium text-gray-700">Recovery
                                Email</label>
                            <input type="email" name="recovery_contact" id="recovery_contact"
                                value="<?= htmlspecialchars($user['recovery_contact'] ?? '') ?>"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="Enter your recovery email">
                            <p class="mt-1 text-sm text-gray-500">This will be used to recover your account if you
                                forget your login email.</p>
                            <?php if (!empty($user['recovery_contact'])): ?>
                            <p class="mt-1 text-sm text-green-600">✓ Recovery email is set</p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <button type="submit"
                                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Update Recovery Email
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Password Change -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Change Password</h2>
                <?php if (isset($success['password'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    <?= htmlspecialchars($success['password']) ?>
                </div>
                <?php endif; ?>
                <?php if (isset($errors['password'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <?= htmlspecialchars($errors['password']) ?>
                </div>
                <?php endif; ?>
                <form action="" method="POST">
                    <input type="hidden" name="form_type" value="password">
                    <div class="space-y-4">
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700">Current
                                Password</label>
                            <input type="password" name="current_password" id="current_password" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700">New
                                Password</label>
                            <input type="password" name="new_password" id="new_password" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New
                                Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <button type="submit"
                                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Change Password
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Account Deletion -->
            <?php if (!$auth->isAdmin()): ?>
            <div class="bg-white shadow rounded-lg p-6 mt-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Delete Account</h2>
                <div class="text-gray-600 mb-4">
                    <p>Once you delete your account, there is no going back. Please be certain.</p>
                </div>
                <button onclick="showDeleteAccountModal()"
                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Delete Account
                </button>
            </div>
            <?php endif; ?>

            <!-- Delete Account Modal -->
            <div id="deleteAccountModal" class="hidden fixed z-10 inset-0 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                        onclick="hideDeleteAccountModal()"></div>
                    <div class="relative bg-white rounded-lg max-w-md w-full p-6">
                        <div class="mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Delete Account</h3>
                            <p class="mt-2 text-sm text-gray-500">
                                Are you absolutely sure you want to delete your account? This action cannot be undone.
                                All your data will be permanently removed.
                            </p>
                        </div>
                        <form action="" method="POST" class="mt-4">
                            <input type="hidden" name="form_type" value="delete_account">
                            <div class="mb-4">
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700">
                                    Please enter your password to confirm
                                </label>
                                <input type="password" name="confirm_password" id="confirm_password" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm">
                            </div>
                            <div class="mt-5 flex justify-end space-x-3">
                                <button type="button" onclick="hideDeleteAccountModal()"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700">
                                    Delete Account
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function showDeleteAccountModal() {
    document.getElementById('deleteAccountModal').classList.remove('hidden');
}

function hideDeleteAccountModal() {
    document.getElementById('deleteAccountModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'footer.php'; ?>