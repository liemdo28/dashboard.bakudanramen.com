<?php

function available_locales() {
    return ['vi', 'en'];
}

function normalize_locale($locale) {
    $locale = strtolower(trim((string) $locale));
    return in_array($locale, available_locales(), true) ? $locale : 'vi';
}

function set_locale($locale) {
    $locale = normalize_locale($locale);
    $_SESSION['locale'] = $locale;
    setcookie('taskflow_locale', $locale, time() + (86400 * 365), '/');
    return $locale;
}

function current_locale() {
    static $locale = null;
    if ($locale !== null) {
        return $locale;
    }

    if (!empty($_SESSION['locale'])) {
        $locale = normalize_locale($_SESSION['locale']);
        return $locale;
    }

    if (!empty($_COOKIE['taskflow_locale'])) {
        $locale = normalize_locale($_COOKIE['taskflow_locale']);
        $_SESSION['locale'] = $locale;
        return $locale;
    }

    $locale = 'vi';
    $_SESSION['locale'] = $locale;
    return $locale;
}

function locale_label($locale) {
    $locale = normalize_locale($locale);
    return $locale === 'en' ? 'English (US)' : 'Tiếng Việt';
}

function language_switch_url($locale, $redirect = null) {
    $locale = normalize_locale($locale);
    $redirect = $redirect ?? ($_SERVER['REQUEST_URI'] ?? '/');
    return APP_URL . '/language/' . $locale . '?redirect=' . rawurlencode($redirect);
}

function tf_translations() {
    static $translations = null;
    if ($translations !== null) {
        return $translations;
    }

    $translations = [
        'vi' => [
            'app.name' => 'TaskFlow',
            'lang.vi' => 'Tiếng Việt',
            'lang.en' => 'English (US)',
            'nav.dashboard' => 'Dashboard',
            'nav.inbox' => 'Inbox',
            'nav.my_tasks' => 'Task của tôi',
            'nav.calendar' => 'Calendar',
            'nav.bills' => 'Tracking Bill',
            'nav.projects' => 'Projects',
            'nav.projects_section' => 'Dự án',
            'nav.admin' => 'Admin',
            'nav.users' => 'Quản lý Users',
            'nav.vendors' => 'Vendor List',
            'action.logout' => 'Đăng xuất',
            'header.notifications' => 'Thông báo',
            'header.read_all' => 'Đọc hết',
            'header.loading' => 'Đang tải...',
            'header.language' => 'Ngôn ngữ',
            'page.dashboard' => 'Dashboard',
            'page.my_tasks' => 'Task của tôi',
            'page.calendar' => 'Calendar',
            'page.login' => 'Đăng nhập',
            'page.register' => 'Đăng ký',
            'auth.secure_access' => 'Truy cập bảo mật',
            'auth.sign_in_continue' => 'Đăng nhập để tiếp tục',
            'auth.email' => 'Email',
            'auth.password' => 'Mật khẩu',
            'auth.sign_in' => 'Đăng nhập',
            'auth.no_account' => 'Chưa có tài khoản?',
            'auth.register_now' => 'Đăng ký ngay',
            'auth.create_account' => 'Tạo tài khoản mới',
            'auth.full_name' => 'Họ tên',
            'auth.confirm_password' => 'Xác nhận mật khẩu',
            'auth.password_min' => 'Tối thiểu 6 ký tự',
            'auth.reenter_password' => 'Nhập lại mật khẩu',
            'auth.register' => 'Đăng ký',
            'auth.have_account' => 'Đã có tài khoản?',
            'auth.invalid_credentials' => 'Email hoặc mật khẩu không đúng.',
            'auth.enter_credentials' => 'Vui lòng nhập email và mật khẩu.',
            'auth.fill_required' => 'Vui lòng điền đầy đủ thông tin.',
            'auth.password_short' => 'Mật khẩu tối thiểu 6 ký tự.',
            'auth.password_mismatch' => 'Mật khẩu xác nhận không khớp.',
            'auth.email_used' => 'Email đã được sử dụng.',
            'auth.register_success' => 'Đăng ký thành công! Chào mừng bạn đến với TaskFlow.',
            'auth.fill_all_short' => 'Vui lòng điền đầy đủ.',
            'auth.email_exists' => 'Email đã tồn tại.',
            'auth.user_create_success' => 'Tạo tài khoản thành công.',
            'auth.cannot_disable_self' => 'Không thể vô hiệu hóa chính mình.',
            'auth.user_status_updated' => 'Cập nhật trạng thái thành công.',
            'auth.cannot_delete_self' => 'Không thể xóa chính mình.',
            'auth.user_deleted' => 'Xóa tài khoản thành công.',
            'dashboard.projects' => 'Projects',
            'dashboard.total_tasks' => 'Tổng Tasks',
            'dashboard.completed' => 'Hoàn thành',
            'dashboard.members' => 'Thành viên',
            'dashboard.task_distribution' => 'Phân bố Tasks',
            'dashboard.overdue_tasks' => 'Tasks quá hạn',
            'dashboard.no_overdue' => 'Không có task quá hạn 🎉',
            'dashboard.my_tasks' => 'Tasks của tôi',
            'dashboard.no_tasks' => 'Chưa có task',
            'dashboard.no_assigned_tasks' => 'Bạn chưa được gán task nào',
            'dashboard.recent_projects' => 'Projects gần đây',
            'dashboard.create_project' => '+ Tạo Project',
            'dashboard.complete_percent' => '% hoàn thành',
            'dashboard.status.todo' => 'To Do',
            'dashboard.status.in_progress' => 'In Progress',
            'dashboard.status.review' => 'Review',
            'dashboard.status.done' => 'Done',
            'dashboard.google_connected' => '✅ Google Connected',
            'dashboard.connect_google' => 'Connect Google Calendar',
            'tasks.overdue' => 'Quá hạn',
            'tasks.all_assigned' => 'Tất cả tasks được giao',
            'tasks.none' => 'Chưa có task nào',
            'tasks.none_assigned' => 'Bạn chưa được gán task nào',
            'tasks.task' => 'Task',
            'tasks.project' => 'Project',
            'tasks.priority' => 'Ưu tiên',
            'tasks.status' => 'Trạng thái',
            'tasks.due' => 'Hạn',
            'common.today' => 'Hôm nay',
            'common.no_due' => 'Không có hạn',
            'common.overdue_days' => 'Quá hạn :days ngày',
            'common.due_today' => 'Đến hạn hôm nay',
            'common.due_in_days' => 'Đến hạn sau :days ngày',
            'common.untitled' => 'Chưa có mô tả',
            'seed.first_project' => 'Dự án đầu tiên',
            'seed.first_project_desc' => 'Project mặc định - bạn có thể đổi tên hoặc xoá.',
        ],
        'en' => [
            'app.name' => 'TaskFlow',
            'lang.vi' => 'Vietnamese',
            'lang.en' => 'English (US)',
            'nav.dashboard' => 'Dashboard',
            'nav.inbox' => 'Inbox',
            'nav.my_tasks' => 'My Tasks',
            'nav.calendar' => 'Calendar',
            'nav.bills' => 'Bill Tracking',
            'nav.projects' => 'Projects',
            'nav.projects_section' => 'Projects',
            'nav.admin' => 'Admin',
            'nav.users' => 'User Management',
            'nav.vendors' => 'Vendor List',
            'action.logout' => 'Log out',
            'header.notifications' => 'Notifications',
            'header.read_all' => 'Mark all read',
            'header.loading' => 'Loading...',
            'header.language' => 'Language',
            'page.dashboard' => 'Dashboard',
            'page.my_tasks' => 'My Tasks',
            'page.calendar' => 'Calendar',
            'page.login' => 'Sign In',
            'page.register' => 'Register',
            'auth.secure_access' => 'Secure Access',
            'auth.sign_in_continue' => 'Sign in to continue',
            'auth.email' => 'Email',
            'auth.password' => 'Password',
            'auth.sign_in' => 'Sign In',
            'auth.no_account' => 'Don\'t have an account?',
            'auth.register_now' => 'Register now',
            'auth.create_account' => 'Create a new account',
            'auth.full_name' => 'Full name',
            'auth.confirm_password' => 'Confirm password',
            'auth.password_min' => 'At least 6 characters',
            'auth.reenter_password' => 'Re-enter password',
            'auth.register' => 'Register',
            'auth.have_account' => 'Already have an account?',
            'auth.invalid_credentials' => 'Incorrect email or password.',
            'auth.enter_credentials' => 'Please enter your email and password.',
            'auth.fill_required' => 'Please fill in all required fields.',
            'auth.password_short' => 'Password must be at least 6 characters.',
            'auth.password_mismatch' => 'Password confirmation does not match.',
            'auth.email_used' => 'This email is already in use.',
            'auth.register_success' => 'Registration successful! Welcome to TaskFlow.',
            'auth.fill_all_short' => 'Please complete all fields.',
            'auth.email_exists' => 'Email already exists.',
            'auth.user_create_success' => 'User account created successfully.',
            'auth.cannot_disable_self' => 'You cannot disable your own account.',
            'auth.user_status_updated' => 'User status updated successfully.',
            'auth.cannot_delete_self' => 'You cannot delete your own account.',
            'auth.user_deleted' => 'User account deleted successfully.',
            'dashboard.projects' => 'Projects',
            'dashboard.total_tasks' => 'Total Tasks',
            'dashboard.completed' => 'Completed',
            'dashboard.members' => 'Members',
            'dashboard.task_distribution' => 'Task Distribution',
            'dashboard.overdue_tasks' => 'Overdue Tasks',
            'dashboard.no_overdue' => 'No overdue tasks 🎉',
            'dashboard.my_tasks' => 'My Tasks',
            'dashboard.no_tasks' => 'No tasks yet',
            'dashboard.no_assigned_tasks' => 'You have not been assigned any tasks yet.',
            'dashboard.recent_projects' => 'Recent Projects',
            'dashboard.create_project' => '+ Create Project',
            'dashboard.complete_percent' => '% complete',
            'dashboard.status.todo' => 'To Do',
            'dashboard.status.in_progress' => 'In Progress',
            'dashboard.status.review' => 'Review',
            'dashboard.status.done' => 'Done',
            'dashboard.google_connected' => '✅ Google Connected',
            'dashboard.connect_google' => 'Connect Google Calendar',
            'tasks.overdue' => 'Overdue',
            'tasks.all_assigned' => 'All assigned tasks',
            'tasks.none' => 'No tasks found',
            'tasks.none_assigned' => 'No tasks have been assigned to you yet.',
            'tasks.task' => 'Task',
            'tasks.project' => 'Project',
            'tasks.priority' => 'Priority',
            'tasks.status' => 'Status',
            'tasks.due' => 'Due',
            'common.today' => 'Today',
            'common.no_due' => 'No due date',
            'common.overdue_days' => 'Overdue by :days day(s)',
            'common.due_today' => 'Due today',
            'common.due_in_days' => 'Due in :days day(s)',
            'common.untitled' => 'No description yet',
            'seed.first_project' => 'My First Project',
            'seed.first_project_desc' => 'Default project - you can rename or delete it.',
        ],
    ];

    return $translations;
}

function t($key, $replace = []) {
    $translations = tf_translations();
    $locale = current_locale();
    $text = $translations[$locale][$key] ?? $translations['vi'][$key] ?? $key;

    foreach ($replace as $name => $value) {
        $text = str_replace(':' . $name, (string) $value, $text);
    }

    return $text;
}
