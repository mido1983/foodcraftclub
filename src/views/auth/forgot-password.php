<?php
/** @var $this \App\Core\View */
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body p-5">
                    <h1 class="text-center mb-4">Восстановление пароля</h1>
                    <p class="text-center mb-4">Введите ваш email, и мы отправим вам ссылку для восстановления пароля.</p>
                    
                    <form action="/forgot-password" method="post" id="forgotPasswordForm">
                        <input type="hidden" name="csrf_token" value="<?= $this->csrf_token ?>">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">Отправить</button>
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
    document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const email = document.getElementById('email').value;
        const csrfToken = document.querySelector('input[name="csrf_token"]').value;
        
        // Показываем индикатор загрузки
        const submitBtn = document.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Отправка...';
        
        fetch('/forgot-password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ email: email })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Ошибка сервера: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Показать сообщение об успехе
                const formContainer = document.querySelector('.card-body');
                formContainer.innerHTML = `
                    <h1 class="text-center mb-4">Проверьте почту</h1>
                    <p class="text-center mb-4">Если указанный email зарегистрирован в системе, мы отправили на него инструкции по восстановлению пароля.</p>
                    <div class="text-center mt-4">
                        <p><a href="/login" class="text-decoration-none">Вернуться на страницу входа</a></p>
                    </div>
                `;
            } else {
                // Показать сообщение об ошибке
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                alert(data.message || 'Произошла ошибка. Пожалуйста, попробуйте снова.');
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
