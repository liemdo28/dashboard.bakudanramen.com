# TaskFlow v2 - Hướng dẫn Deploy

## Thay đổi so với v1

### Tính năng mới:
- ✅ **Đăng ký tài khoản** - người dùng tự đăng ký (không cần admin tạo)
- ✅ **Calendar view** - lịch tháng giống Asana, hiện task theo ngày due date
- ✅ **Inbox** - thông báo khi: được giao task, có comment, task sắp/quá hạn
- ✅ **Thông báo email** - gửi email tự động cho mỗi notification
- ✅ **PWA** - cài app trên Desktop, Android, iOS
- ✅ **Dark theme** - giao diện tối chuyên nghiệp
- ✅ **My Tasks** - trang riêng xem tất cả task được giao

### Giao diện mới:
- Dark theme chuyên nghiệp (giống Linear/Asana)
- Font Inter
- Responsive mobile
- Notification bell dropdown

---

## Bước 1: Upload files

Upload toàn bộ nội dung thư mục này lên hosting qua **FTP** hoặc **File Manager** trong cPanel,
thay thế các file cũ trong `dashboard.bakudanramen.com/`.

**Cấu trúc file:**
```
dashboard.bakudanramen.com/
├── index.php            ← Router (đã update)
├── .htaccess            ← Giữ nguyên
├── cron.php             ← MỚI: cron job
├── config/database.php  ← Giữ nguyên config
├── assets/
│   ├── css/style.css    ← MỚI: dark theme
│   ├── js/app.js        ← MỚI: notifications
│   └── icons/           ← MỚI: PWA icons
├── controllers/
│   ├── AuthController.php    ← UPDATE: có register
│   ├── DashboardController.php ← UPDATE: calendar, inbox
│   ├── TaskController.php    ← UPDATE: notifications
│   └── CommentController.php ← UPDATE: notifications
├── models/
│   ├── Notification.php ← MỚI
│   └── ... (giữ nguyên)
├── views/
│   ├── auth/
│   │   ├── login.php    ← UPDATE
│   │   └── register.php ← MỚI
│   ├── layouts/main.php ← UPDATE
│   ├── dashboard/
│   │   ├── index.php    ← UPDATE
│   │   └── my_tasks.php ← MỚI
│   ├── calendar/index.php ← MỚI
│   ├── inbox/index.php    ← MỚI
│   └── ... (update dark theme)
└── sql/schema_v2.sql    ← MỚI: chạy SQL này
```

## Bước 2: Chạy SQL migration

Vào **phpMyAdmin** trên hosting, chọn database `taskflow_db`, tab **SQL**, paste nội dung file `sql/schema_v2.sql` và chạy.

Hoặc vào terminal MySQL:
```
mysql -u liemdo -p taskflow_db < sql/schema_v2.sql
```

## Bước 3: Cài Cron Job (thông báo email)

Vào **cPanel → Cron Jobs**, thêm cron chạy mỗi giờ:

```
0 * * * * /usr/local/bin/php /home/YOUR_USERNAME/dashboard.bakudanramen.com/cron.php
```

Thay `YOUR_USERNAME` bằng username cPanel của bạn.

Cron job sẽ:
- Kiểm tra task sắp đến hạn → gửi thông báo
- Kiểm tra task quá hạn → gửi thông báo
- Gửi email từ queue

## Bước 4: Cài PWA (app trên điện thoại/desktop)

### Android:
1. Mở Chrome → vào `dashboard.bakudanramen.com`
2. Đăng nhập
3. Chrome sẽ hiện popup "Add to Home screen" → nhấn **Install**
4. App TaskFlow sẽ xuất hiện trên màn hình chính

### iOS (iPhone/iPad):
1. Mở Safari → vào `dashboard.bakudanramen.com`
2. Nhấn nút **Share** (hình vuông mũi tên lên)
3. Chọn **"Thêm vào Màn hình chính"** (Add to Home Screen)
4. Đặt tên "TaskFlow" → nhấn **Thêm**

### Desktop (Windows/Mac):
1. Mở Chrome → vào `dashboard.bakudanramen.com`
2. Click icon **Install** (⊕) trên thanh address bar
3. Hoặc: Menu Chrome → "Install TaskFlow"
4. App sẽ mở như ứng dụng desktop riêng

**Lưu ý:** PWA yêu cầu HTTPS. Nếu chưa có SSL, vào cPanel → SSL/TLS → cài Let's Encrypt miễn phí.

---

## Config

File `config/database.php` - giữ nguyên, không cần thay đổi.

Nếu muốn đổi secret key cho cron, sửa biến `$expectedKey` trong `cron.php`.

## Test

Sau khi upload:
1. Vào `dashboard.bakudanramen.com` → thấy trang login dark theme
2. Click "Đăng ký ngay" → tạo tài khoản mới
3. Vào Dashboard → thấy stats, tasks
4. Click "Calendar" sidebar → thấy lịch tháng
5. Tạo task, giao cho người khác → họ nhận thông báo trong Inbox
6. Cài PWA trên điện thoại
