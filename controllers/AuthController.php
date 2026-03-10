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
        if ($email === '' || $password === '') { flash('error', t('auth.enter_credentials')); redirect('login'); }
        $user = $this->userModel->verify($email, $password);
        if (!$user) { flash('error', t('auth.invalid_credentials')); redirect('login'); }
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
            flash('error', t('auth.fill_required'));
            redirect('register');
        }
        if (strlen($password) < 6) {
            flash('error', t('auth.password_short'));
            redirect('register');
        }
        if ($password !== $confirm) {
            flash('error', t('auth.password_mismatch'));
            redirect('register');
        }
        if ($this->userModel->findByEmail($email)) {
            flash('error', t('auth.email_used'));
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
            'name' => t('seed.first_project'),
            'description' => t('seed.first_project_desc'),
            'color' => '#dc2626',
            'owner_id' => $id
        ]);

        $_SESSION['user_id'] = $id;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_role'] = 'member';

        flash('success', t('auth.register_success'));
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
        if ($name === '' || $email === '' || $password === '') { flash('error', t('auth.fill_all_short')); redirect('admin/users'); }
        if ($this->userModel->findByEmail($email)) { flash('error', t('auth.email_exists')); redirect('admin/users'); }
        $this->userModel->create(['name'=>$name, 'email'=>$email, 'password'=>$password, 'role'=>$role]);
        flash('success', t('auth.user_create_success'));
        redirect('admin/users');
    }

    public function toggleUser($id) {
        if (!isAdmin()) redirect('dashboard');
        if ((int)$id === (int)$_SESSION['user_id']) { flash('error', t('auth.cannot_disable_self')); redirect('admin/users'); }
        $this->userModel->toggleActive($id);
        flash('success', t('auth.user_status_updated'));
        redirect('admin/users');
    }

    public function deleteUser($id) {
        if (!isAdmin()) redirect('dashboard');
        if ((int)$id === (int)$_SESSION['user_id']) { flash('error', t('auth.cannot_delete_self')); redirect('admin/users'); }
        $this->userModel->delete($id);
        flash('success', t('auth.user_deleted'));
        redirect('admin/users');
    }
}
