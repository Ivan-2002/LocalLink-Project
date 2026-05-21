<!-- Sperate admin login -->
<?php
// admin/index.php  (Admin Login)
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';

if (isLoggedIn() && isAdmin()) {
    redirect(BASE_URL . '../admin/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login — LocalLink</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body {
            background: #1a1d23;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .admin-card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
        }

        .admin-badge {
            display: inline-block;
            background: #dc3545;
            color: #fff;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            padding: 3px 10px;
            border-radius: 20px;
            margin-bottom: 12px;
        }

        h2 {
            font-weight: 800;
            color: #1a1d23;
        }

        .form-control:focus {
            border-color: #1a1d23;
            box-shadow: 0 0 0 3px rgba(26, 29, 35, 0.15);
        }

        .btn-admin {
            background: #1a1d23;
            color: #fff;
            font-weight: 700;
            border: none;
            padding: 12px;
            border-radius: 8px;
        }

        .btn-admin:hover {
            background: #333;
            color: #fff;
        }
    </style>
</head>

<body>

    <div class="admin-card">
        <span class="admin-badge">🔐 Admin Access Only</span>
        <h2 class="mb-1">Admin Portal</h2>
        <p class="text-muted mb-4" style="font-size:0.9rem">TownMarket administration panel</p>

        <div id="adminAlert" class="alert d-none" role="alert"></div>

        <form id="adminLoginForm">
            <div class="mb-3">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" name="email" class="form-control" placeholder="admin@c2c.com" required>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-admin w-100">Login to Admin Panel</button>
        </form>

        <p class="text-center mt-3 mb-0">
            <a href="<?= BASE_URL ?>login.php" class="text-muted" style="font-size:0.85rem">← Back to main site</a>
        </p>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $(function() {
            $('#adminLoginForm').on('submit', function(e) {
                e.preventDefault();
                const btn = $(this).find('button');
                btn.prop('disabled', true).text('Logging in...');

                $.post('../api/auth/login.php', $(this).serialize())
                    .done(function(res) {
                        if (res.success && res.role === 'admin') {
                            window.location.href = res.redirect;
                        } else if (res.success && res.role !== 'admin') {
                            showAlert('Access denied. Admin accounts only.');
                            btn.prop('disabled', false).text('Login to Admin Panel');
                        } else {
                            showAlert(res.error || 'Login failed.');
                            btn.prop('disabled', false).text('Login to Admin Panel');
                        }
                    })
                    .fail(function(xhr) {
                        showAlert(xhr.responseJSON?.error || 'Something went wrong.');
                        btn.prop('disabled', false).text('Login to Admin Panel');
                    });
            });

            function showAlert(msg) {
                $('#adminAlert').removeClass('d-none alert-success').addClass('alert-danger').text(msg);
            }
        });
    </script>
</body>

</html>