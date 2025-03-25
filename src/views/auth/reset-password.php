<?php
/** @var $this \App\Core\View */
/** @var $token string */
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body p-5">
                    <h1 class="text-center mb-4">Сброс пароля</h1>
                    <p class="text-center mb-4">Введите новый пароль для вашей учетной записи.</p>
                    
                    <form action="/reset-password" method="post" id="resetPasswordForm">
                        <input type="hidden" name="csrf_token" value="<?= $this->csrf_token ?>">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Новый пароль</label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="8">
                            <div class="form-text">Пароль должен быть не менее 8 символов</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password_confirm" class="form-label">Подтвердите пароль</label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required minlength="8">
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">Сбросить пароль</button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4">
                        <p><a href="/login" class="text-decoration-none">Вернуться на страницу входа</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const password = document.getElementById('password').value;
        const passwordConfirm = document.getElementById('password_confirm').value;
        const token = document.querySelector('input[name="token"]').value;
        const csrfToken = document.querySelector('input[name="csrf_token"]').value;
        
        // Показать индикатор загрузки
        const submitBtn = document.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Обработка...';
        
        // Проверить совпадение паролей
        if (password !== passwordConfirm) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
            alert('Пароли не совпадают');
            return;
        }
        
        // Проверить сложность пароля
        if (password.length < 8) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
            alert('Пароль должен быть не менее 8 символов');
            return;
        }
        
        fetch('/reset-password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ 
                token: token,
                password: password,
                password_confirm: passwordConfirm 
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Произошла ошибка сервера: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Показать сообщение об успехе
                const formContainer = document.querySelector('.card-body');
                formContainer.innerHTML = `
                    <h1 class="text-center mb-4">Пароль изменен</h1>
                    <p class="text-center mb-4">Ваш пароль был успешно изменен. Теперь вы можете войти в систему с новым паролем.</p>
                    <div class="text-center mt-4">
                        <a href="/login" class="btn btn-primary">Войти в систему</a>
                    </div>
                `;
            } else {
                // Показать сообщение об ошибке
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                alert(data.message || 'Произошла ошибка при сбросе пароля. Пожалуйста, попробуйте снова.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
            alert('Произошла ошибка при отправке запроса. Пожалуйста, попробуйте снова.');
        });
    });
</script>
