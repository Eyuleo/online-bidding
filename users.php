<?php
require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'head.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AuthController.php';

$auth = new AuthController($pdo);
$auth->requireAdmin(); // This will redirect if not admin

$errors = [];
$success = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'delete_user':
            $userId = $_POST['user_id'] ?? '';
            
            if ($userId) {
                $stmt = $pdo->prepare('DELETE FROM users WHERE id = ? AND role != "admin"');
                if ($stmt->execute([$userId])) {
                    $success['delete'] = 'User deleted successfully';
                } else {
                    $errors['delete'] = 'Failed to delete user';
                }
            }
            break;

        case 'toggle_status':
            $userId = $_POST['user_id'] ?? '';
            $isActive = $_POST['is_active'] ?? '0';
            
            if ($userId) {
                $stmt = $pdo->prepare('UPDATE users SET is_active = ? WHERE id = ? AND role != "admin"');
                if ($stmt->execute([$isActive, $userId])) {
                    $success['status'] = 'User status updated successfully';
                } else {
                    $errors['status'] = 'Failed to update user status';
                }
            }
            break;

        case 'restrict_user':
            $userId = $_POST['user_id'] ?? '';
            $days = intval($_POST['restrict_days'] ?? '0');
            
            if ($userId && $days > 0) {
                $restrictUntil = (new DateTime())->modify("+{$days} days")->format('Y-m-d H:i:s');
                $stmt = $pdo->prepare('UPDATE users SET account_restricted_until = ? WHERE id = ? AND role != "admin"');
                if ($stmt->execute([$restrictUntil, $userId])) {
                    $success['restrict'] = 'User restricted successfully';
                } else {
                    $errors['restrict'] = 'Failed to restrict user';
                }
            }
            break;

        case 'unrestrict_user':
            $userId = $_POST['user_id'] ?? '';
            
            if ($userId) {
                $stmt = $pdo->prepare('UPDATE users SET account_restricted_until = NULL WHERE id = ? AND role != "admin"');
                if ($stmt->execute([$userId])) {
                    $success['restrict'] = 'User unrestricted successfully';
                } else {
                    $errors['restrict'] = 'Failed to unrestrict user';
                }
            }
            break;
    }
}

// Fetch all users except current admin
$stmt = $pdo->prepare('SELECT * FROM users WHERE id != ? ORDER BY created_at DESC');
$stmt->execute([$_SESSION['user_id']]);
$users = $stmt->fetchAll();
?>
<?php require __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'nav.php'; ?>

<main class="py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">User Management</h1>

            <!-- Users List -->
            <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Users List</h2>
                <?php if (isset($success['status']) || isset($success['restrict']) || isset($success['delete'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    <?= htmlspecialchars($success['status'] ?? $success['restrict'] ?? $success['delete']) ?>
                </div>
                <?php endif; ?>
                <?php if (isset($errors['status']) || isset($errors['restrict']) || isset($errors['delete'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <?= htmlspecialchars($errors['status'] ?? $errors['restrict'] ?? $errors['delete']) ?>
                </div>
                <?php endif; ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th
                                    class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Name</th>
                                <th
                                    class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Email</th>
                                <th
                                    class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Role</th>
                                <th
                                    class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($user['name']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($user['email']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($user['role']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php if (!$user['is_active']): ?>
                                    <span class="text-red-600">Inactive</span>
                                    <?php elseif ($user['account_restricted_until'] && new DateTime($user['account_restricted_until']) > new DateTime()): ?>
                                    <span class="text-yellow-600">Restricted until
                                        <?= (new DateTime($user['account_restricted_until']))->format('Y-m-d H:i') ?></span>
                                    <?php else: ?>
                                    <span class="text-green-600">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php if ($user['role'] !== 'admin'): ?>
                                    <form action="" method="POST" class="inline-block">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="is_active"
                                            value="<?= $user['is_active'] ? '0' : '1' ?>">
                                        <button type="submit"
                                            class="text-indigo-600 hover:text-indigo-900 cursor-pointer"><?= $user['is_active'] ? 'Deactivate' : 'Activate' ?></button>
                                    </form>
                                    <span class="text-gray-300 mx-2">|</span>
                                    <?php if ($user['account_restricted_until'] && new DateTime($user['account_restricted_until']) > new DateTime()): ?>
                                    <form action="" method="POST" class="inline-block">
                                        <input type="hidden" name="action" value="unrestrict_user">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit"
                                            class="text-green-600 hover:text-green-900 cursor-pointer">Unrestrict</button>
                                    </form>
                                    <?php else: ?>
                                    <form action="" method="POST" class="inline-block">
                                        <input type="hidden" name="action" value="restrict_user">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="number" name="restrict_days" min="1" max="30"
                                            class="w-24 inline-block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                            placeholder="Days">
                                        <button type="submit"
                                            class="text-yellow-600 hover:text-yellow-900 cursor-pointer">Restrict</button>
                                    </form>
                                    <?php endif; ?>
                                    <span class="text-gray-300 mx-2">|</span>
                                    <button
                                        onclick="showDeleteModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')"
                                        class="text-red-600 hover:text-red-900 cursor-pointer">Delete</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="hidden fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
    aria-modal="true">
    <div class="flex items-center justify-center min-h-screen p-4 text-center sm:block sm:p-0">
        <!-- This element is to trick the browser into centering the modal contents. -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"
            onclick="hideDeleteModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div
            class="relative inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div
                        class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Delete User</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">Are you sure you want to delete this user? This action
                                cannot be undone.</p>
                            <p class="text-sm text-gray-500 mt-2">User: <span id="deleteUserName"
                                    class="font-semibold"></span></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <form id="deleteForm" action="" method="POST">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <button type="submit"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">Delete</button>
                </form>
                <button type="button" onclick="hideDeleteModal()"
                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancel</button>
            </div>
        </div>
    </div>
</div>

<script>
function showDeleteModal(userId, userName) {
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteUserId').value = userId;
    document.getElementById('deleteUserName').textContent = userName;
}

function hideDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . 'footer.php'; ?>