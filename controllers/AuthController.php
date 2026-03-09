<?php
class AuthController {
    private $userModel;
    public function __construct() { $this->userModel = new User(); }

    public function showLogin() {
        if (isLoggedIn()) redirect('dashboard');
        require __DIR__ . '/../views/auth/login.php';
    }

    public function login() {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        if ($email === '' || $password === '') { flash('error', 'Vui lòng nhập email và mật khẩu.'); redirect('login'); }
        $user = $this->userModel->verify($email, $password);
        if (!$user) { flash('error', 'Email hoặc mật khẩu không đúng.'); redirect('login'); }
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        redirect('dashboard');
    }

    public function showRegister() {
        if (isLoggedIn()) redirect('dashboard');
        require __DIR__ . '/../views/auth/register.php';
    }

    public function register() {
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['password_confirm'] ?? '';

        if ($name === '' || $email === '' || $password === '') {
            flash('error', 'Vui lòng điền đầy đủ thông tin.');
            redirect('register');
        }
        if (strlen($password) < 6) {
            flash('error', 'Mật khẩu tối thiểu 6 ký tự.');
            redirect('register');
        }
        if ($password !== $confirm) {
            flash('error', 'Mật khẩu xác nhận không khớp.');
            redirect('register');
        }
        if ($this->userModel->findByEmail($email)) {
            flash('error', 'Email đã được sử dụng.');
            redirect('register');
        }

        $id = $this->userModel->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => 'member'
        ]);

        // Auto-create a default project
        $proj = new Project();
        $proj->create([
            'name' => 'Dự án đầu tiên',
            'description' => 'Project mặc định - bạn có thể đổi tên hoặc xoá.',
            'color' => '#dc2626',
            'owner_id' => $id
        ]);

        $_SESSION['user_id'] = $id;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_role'] = 'member';

        flash('success', 'Đăng ký thành công! Chào mừng bạn đến với TaskFlow.');
        redirect('dashboard');
    }

    public function logout() { session_destroy(); redirect('login'); }

    public function listUsers() {
        if (!isAdmin()) redirect('dashboard');
        $users = $this->userModel->getAll();
        require __DIR__ . '/../views/admin/users.php';
    }

    public function createUser() {
        if (!isAdmin()) redirect('dashboard');
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'member';
        if ($name === '' || $email === '' || $password === '') { flash('error', 'Vui lòng điền đầy đủ.'); redirect('admin/users'); }
        if ($this->userModel->findByEmail($email)) { flash('error', 'Email đã tồn tại.'); redirect('admin/users'); }
        $this->userModel->create(['name'=>$name, 'email'=>$email, 'password'=>$password, 'role'=>$role]);
        flash('success', 'Tạo tài khoản thành công.');
        redirect('admin/users');
    }

    public function toggleUser($id) {
        if (!isAdmin()) redirect('dashboard');
        if ((int)$id === (int)$_SESSION['user_id']) { flash('error', 'Không thể vô hiệu hóa chính mình.'); redirect('admin/users'); }
        $this->userModel->toggleActive($id);
        flash('success', 'Cập nhật trạng thái thành công.');
        redirect('admin/users');
    }

    public function deleteUser($id) {
        if (!isAdmin()) redirect('dashboard');
        if ((int)$id === (int)$_SESSION['user_id']) { flash('error', 'Không thể xóa chính mình.'); redirect('admin/users'); }
        $this->userModel->delete($id);
        flash('success', 'Xóa tài khoản thành công.');
        redirect('admin/users');
    }
}
