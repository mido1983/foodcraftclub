<?php
/** @var $this \App\Core\View */
/** @var $token string */
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body p-5">
                    <h1 class="text-center mb-4">u0421u0431u0440u043eu0441 u043fu0430u0440u043eu043bu044a</h1>
                    <p class="text-center mb-4">u0412u0432u0435u0434u0438u0442u0435 u043du043eu0432u044bu0439 u043fu0430u0440u043eu043bu044c u0434u043bu044f u0432u0430u0448u0435u0439 u0443u0447u0435u0442u043du043eu0439 u0437u0430u043fu0438u0441u0438.</p>
                    
                    <form action="/reset-password" method="post" id="resetPasswordForm">
                        <input type="hidden" name="csrf_token" value="<?= $this->csrf_token ?>">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">u041du043eu0432u044bu0439 u043fu0430u0440u043eu043bu044c</label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="8">
                            <div class="form-text">u041fu0430u0440u043eu043bu044c u0434u043eu043bu0436u0435u043d u0431u044bu0442u044c u043du0435 u043cu0435u043du0435u0435 8 u0441u0438u043cu0432u043eu043bu043eu0432</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password_confirm" class="form-label">u041fu043eu0434u0442u0432u0435u0440u0434u0438u0442u0435 u043fu0430u0440u043eu043bu044c</label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required minlength="8">
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">u0421u0431u0440u043eu0441u0438u0442u044c u043fu0430u0440u043eu043bu044c</button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4">
                        <p><a href="/login" class="text-decoration-none">u0412u0435u0440u043du0443u0442u044cu0441u044f u043du0430 u0441u0442u0440u0430u043du0438u0446u0443 u0432u0445u043eu0434u0430</a></p>
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
        
        // u041fu043eu043au0430u0437u044bu0432u0430u0435u043c u0438u043du0434u0438u043au0430u0442u043eu0440 u0437u0430u0433u0440u0443u0437u043au0438
        const submitBtn = document.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> u041eu0431u0440u0430u0431u043eu0442u043au0430...';
        
        // u041fu0440u043eu0432u0435u0440u043au0430 u0441u043eu0432u043fu0430u0434u0435u043du0438u044f u043fu0430u0440u043eu043bu0435u0439
        if (password !== passwordConfirm) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
            alert('u041fu0430u0440u043eu043bu0438 u043du0435 u0441u043eu0432u043fu0430u0434u0430u044eu0442');
            return;
        }
        
        // u041fu0440u043eu0432u0435u0440u043au0430 u0441u043lu043eu0436u043du043eu0441u0442u0438 u043fu0430u0440u043eu043bu044a
        if (password.length < 8) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
            alert('u041fu0430u0440u043eu043bu044c u0434u043eu043bu0436u0435u043d u0431u044bu0442u044c u043du0435 u043cu0435u043du0435u0435 8 u0441u0438u043cu0432u043eu043bu043eu0432');
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
                throw new Error('u041fu0440u043eu0438u0437u043eu0448u043bu0430 u043eu0448u0438u0431u043au0430 u0441u0435u0440u0432u0435u0440u0430: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // u041fu043eu043au0430u0437u0430u0442u044c u0441u043eu043eu0431u0449u0435u043du0438u0435 u043eu0431 u0443u0441u043fu0435u0445u0435
                const formContainer = document.querySelector('.card-body');
                formContainer.innerHTML = `
                    <h1 class="text-center mb-4">u041fu0430u0440u043eu043bu044c u0438u0437u043cu0435u043du0435u043d</h1>
                    <p class="text-center mb-4">u0412u0430u0448 u043fu0430u0440u043eu043bu044c u0431u044bu043b u0443u0441u043fu0435u0448u043du043e u0438u0437u043cu0435u043du0435u043d. u0422u0435u043fu0435u0440u044c u0432u044b u043cu043eu0436u0435u0442u0435 u0432u043eu0439u0442u0438 u0432 u0441u0438u0441u0442u0435u043cu0443 u0441 u043du043eu0432u044bu043c u043fu0430u0440u043eu043bu044a.</p>
                    <div class="text-center mt-4">
                        <a href="/login" class="btn btn-primary">u0412u043eu0439u0442u0438 u0432 u0441u0438u0441u0442u0435u043cu0443</a>
                    </div>
                `;
            } else {
                // u041fu043eu043au0430u0437u0430u0442u044c u0441u043eu043eu0431u0449u0435u043du0438u0435 u043eu0431 u043eu0448u0438u0431u043au0435
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                alert(data.message || 'u041fu0440u043eu0438u0437u043eu0448u043bu0430 u043eu0448u0438u0431u043au0430 u043fu0440u0438 u0441u0431u0440u043eu0441u0435 u043fu0430u0440u043eu043bu044a. u041fu043eu0436u0430u043bu0443u0439u0441u0442u0430, u043fu043eu043fu0440u043eu0431u0443u0439u0442u0435 u0441u043du043eu0432u0430.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
            alert('u041fu0440u043eu0438u0437u043eu0448u043bu0430 u043eu0448u0438u0431u043au0430 u043fu0440u0438 u043eu0442u043fu0440u0430u0432u043au0435 u0437u0430u043fu0440u043eu0441u0430. u041fu043eu0436u0430u043bu0443u0439u0441u0442u0430, u043fu043eu043fu0440u043eu0431u0443u0439u0442u0435 u0441u043du043eu0432u0430.');
        });
    });
</script>
