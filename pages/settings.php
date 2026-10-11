<?php
// pages/settings.php
$user = getUserByUsername($_SESSION['username'] ?? '');
$success_message = $_SESSION['flash_message'] ?? '';
$success_type = $_SESSION['flash_type'] ?? 'success';
if (!empty($success_message)) {
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}
?>

<div class="max-w-4xl mx-auto space-y-6">
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
            <div>
                <h2 class="text-xl font-bold text-gray-800"><i class="fas fa-user-cog text-blue-600 mr-2"></i>Profile & Security</h2>
                <p class="text-sm text-gray-500">Update your profile details and change your password.</p>
            </div>
        </div>

        <?php if (!empty($success_message)): ?>
        <div class="mb-4 rounded-lg border px-4 py-3 text-sm <?php echo $success_type === 'error' ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'; ?>">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
        <?php endif; ?>

        <form action="actions/update_profile.php" method="POST" enctype="multipart/form-data" class="space-y-5">
            <div class="border-b pb-5">
                <label class="form-label">Profile Photo</label>
                <div class="flex items-center gap-4 flex-wrap">
                    <div class="w-20 h-20 rounded-full overflow-hidden bg-slate-800 text-white flex items-center justify-center font-bold text-2xl shrink-0">
                        <?php if (!empty($user['photo'])): ?>
                            <img id="profilePhotoPreview" src="<?php echo htmlspecialchars($user['photo']); ?>" alt="Current profile photo" class="w-full h-full object-cover" />
                        <?php else: ?>
                            <img id="profilePhotoPreview" src="" alt="Profile photo preview" class="w-full h-full object-cover hidden" />
                            <span id="profilePhotoInitial"><?php echo htmlspecialchars(strtoupper(substr($user['full_name'] ?? 'U', 0, 1))); ?></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="profilePhoto" class="inline-flex items-center gap-2 cursor-pointer bg-blue-50 text-blue-700 px-3 py-2 rounded-lg text-sm font-semibold hover:bg-blue-100">
                            <i class="fas fa-image"></i> Choose Photo
                        </label>
                        <input type="file" id="profilePhoto" name="profile_photo" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden" />
                        <button type="submit" name="remove_profile_photo" value="1" class="ml-2 inline-flex items-center gap-2 text-sm text-red-600 border border-red-200 px-3 py-2 rounded-lg hover:bg-red-50">
                            <i class="fas fa-trash-alt"></i> Remove Photo
                        </button>
                        <p class="text-xs text-gray-500 mt-1">Choose a JPG, PNG, GIF, or WebP photo. Maximum size: 2 MB.</p>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required />
                </div>
                <div>
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required />
                </div>
                <div>
                    <label class="form-label">Department</label>
                    <select name="department" class="form-control">
                        <?php $currentDepartment = $user['department'] ?? ''; ?>
                        <option value="">Select department</option>
                        <?php if ($currentDepartment !== '' && !in_array($currentDepartment, getDepartments(), true)): ?>
                            <option value="<?php echo htmlspecialchars($currentDepartment); ?>" selected><?php echo htmlspecialchars($currentDepartment); ?> (current)</option>
                        <?php endif; ?>
                        <?php foreach (getDepartments() as $department): ?>
                            <option value="<?php echo htmlspecialchars($department); ?>" <?php echo $currentDepartment === $department ? 'selected' : ''; ?>><?php echo htmlspecialchars($department); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" readonly />
                </div>
            </div>

            <div class="border-t pt-4">
                <h3 class="font-semibold text-gray-800 mb-3"><i class="fas fa-lock text-slate-500 mr-2"></i>Change Password</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" placeholder="Enter your current password" />
                    </div>
                    <div>
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" placeholder="Enter a new password" />
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <button type="submit" class="btn-success"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('profilePhoto')?.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const preview = document.getElementById('profilePhotoPreview');
    const initial = document.getElementById('profilePhotoInitial');
    preview.src = URL.createObjectURL(file);
    preview.classList.remove('hidden');
    if (initial) initial.classList.add('hidden');
});
</script>
