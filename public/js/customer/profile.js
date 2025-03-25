/**
 * JavaScript для страницы профиля пользователя
 * Обрабатывает все формы и взаимодействие с сервером через AJAX
 */

document.addEventListener('DOMContentLoaded', function() {
    // CSRF токен для AJAX запросов
    const csrfToken = document.querySelector('input[name="csrf_token"]').value;
    
    // Инициализация обработчиков событий
    initProfileForm();
    initPasswordForm();
    initAddressManagement();
    initAvatarUpload();
    initPasswordToggle();
    
    /**
     * Инициализация формы профиля
     */
    function initProfileForm() {
        const profileForm = document.getElementById('profileForm');
        const saveProfileBtn = document.getElementById('saveProfileBtn');
        
        if (!profileForm || !saveProfileBtn) return;
        
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveProfileBtn.disabled = true;
            saveProfileBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Сохранение...';
            
            const formData = new FormData(profileForm);
            
            fetch('/customer/profile/update', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-Token': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('success', data.message || 'Профиль успешно обновлен');
                } else {
                    showNotification('error', data.message || 'Ошибка при обновлении профиля');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('error', 'Произошла ошибка при обновлении профиля');
            })
            .finally(() => {
                saveProfileBtn.disabled = false;
                saveProfileBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Сохранить изменения';
            });
        });
    }
    
    /**
     * Инициализация формы изменения пароля
     */
    function initPasswordForm() {
        const passwordForm = document.getElementById('passwordForm');
        const savePasswordBtn = document.getElementById('savePasswordBtn');
        
        if (!passwordForm || !savePasswordBtn) return;
        
        passwordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirm').value;
            
            if (password !== passwordConfirm) {
                showNotification('error', 'Пароли не совпадают');
                return;
            }
            
            savePasswordBtn.disabled = true;
            savePasswordBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Обновление...';
            
            const formData = new FormData(passwordForm);
            
            fetch('/customer/profile/update', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-Token': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('success', data.message || 'Пароль успешно обновлен');
                    passwordForm.reset();
                } else {
                    showNotification('error', data.message || 'Ошибка при обновлении пароля');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('error', 'Произошла ошибка при обновлении пароля');
            })
            .finally(() => {
                savePasswordBtn.disabled = false;
                savePasswordBtn.innerHTML = '<i class="bi bi-lock me-1"></i> Обновить пароль';
            });
        });
    }
    
    /**
     * Инициализация управления адресами
     */
    function initAddressManagement() {
        // Кнопки и формы для работы с адресами
        const saveAddressBtn = document.getElementById('saveAddressBtn');
        const updateAddressBtn = document.getElementById('updateAddressBtn');
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        const addAddressForm = document.getElementById('addAddressForm');
        const editAddressForm = document.getElementById('editAddressForm');
        const deleteAddressForm = document.getElementById('deleteAddressForm');
        
        // Модальные окна
        const addAddressModal = document.getElementById('addAddressModal');
        const editAddressModal = document.getElementById('editAddressModal');
        const deleteAddressModal = document.getElementById('deleteAddressModal');
        
        // Обработчики для модальных окон Bootstrap
        if (addAddressModal) {
            addAddressModal.addEventListener('hidden.bs.modal', function() {
                addAddressForm.reset();
            });
        }
        
        // Сохранение нового адреса
        if (saveAddressBtn && addAddressForm) {
            saveAddressBtn.addEventListener('click', function() {
                if (!addAddressForm.checkValidity()) {
                    addAddressForm.reportValidity();
                    return;
                }
                
                saveAddressBtn.disabled = true;
                saveAddressBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Сохранение...';
                
                const formData = new FormData(addAddressForm);
                
                fetch('/customer/profile/address/add', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-Token': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('success', data.message || 'Адрес успешно добавлен');
                        // Перезагрузка страницы для отображения нового адреса
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        showNotification('error', data.message || 'Ошибка при добавлении адреса');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('error', 'Произошла ошибка при добавлении адреса');
                })
                .finally(() => {
                    saveAddressBtn.disabled = false;
                    saveAddressBtn.innerHTML = 'Сохранить';
                    // Закрытие модального окна
                    const modal = bootstrap.Modal.getInstance(addAddressModal);
                    if (modal) modal.hide();
                });
            });
        }
        
        // Открытие модального окна редактирования адреса
        const editAddressBtns = document.querySelectorAll('.edit-address-btn');
        editAddressBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const addressId = this.getAttribute('data-address-id');
                const title = this.getAttribute('data-title');
                const recipient = this.getAttribute('data-recipient');
                const phone = this.getAttribute('data-phone');
                const country = this.getAttribute('data-country');
                const city = this.getAttribute('data-city');
                const street = this.getAttribute('data-street');
                const house = this.getAttribute('data-house');
                const apartment = this.getAttribute('data-apartment');
                const postal = this.getAttribute('data-postal');
                const isDefault = this.getAttribute('data-default') === '1';
                
                // Заполнение формы данными
                document.getElementById('edit_address_id').value = addressId;
                document.getElementById('edit_title').value = title;
                document.getElementById('edit_recipient_name').value = recipient;
                document.getElementById('edit_phone').value = phone;
                document.getElementById('edit_country').value = country;
                document.getElementById('edit_city').value = city;
                document.getElementById('edit_street').value = street;
                document.getElementById('edit_house').value = house;
                document.getElementById('edit_apartment').value = apartment;
                document.getElementById('edit_postal_code').value = postal;
                document.getElementById('edit_is_default').checked = isDefault;
                
                // Открытие модального окна
                const modal = new bootstrap.Modal(editAddressModal);
                modal.show();
            });
        });
        
        // Обновление адреса
        if (updateAddressBtn && editAddressForm) {
            updateAddressBtn.addEventListener('click', function() {
                if (!editAddressForm.checkValidity()) {
                    editAddressForm.reportValidity();
                    return;
                }
                
                updateAddressBtn.disabled = true;
                updateAddressBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Сохранение...';
                
                const formData = new FormData(editAddressForm);
                
                fetch('/customer/profile/address/update', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-Token': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('success', data.message || 'Адрес успешно обновлен');
                        // Перезагрузка страницы для отображения обновленного адреса
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        showNotification('error', data.message || 'Ошибка при обновлении адреса');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('error', 'Произошла ошибка при обновлении адреса');
                })
                .finally(() => {
                    updateAddressBtn.disabled = false;
                    updateAddressBtn.innerHTML = 'Сохранить';
                    // Закрытие модального окна
                    const modal = bootstrap.Modal.getInstance(editAddressModal);
                    if (modal) modal.hide();
                });
            });
        }
        
        // Открытие модального окна подтверждения удаления
        const deleteAddressBtns = document.querySelectorAll('.delete-address-btn');
        deleteAddressBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const addressId = this.getAttribute('data-address-id');
                document.getElementById('delete_address_id').value = addressId;
                
                // Открытие модального окна
                const modal = new bootstrap.Modal(deleteAddressModal);
                modal.show();
            });
        });
        
        // Удаление адреса
        if (confirmDeleteBtn && deleteAddressForm) {
            confirmDeleteBtn.addEventListener('click', function() {
                confirmDeleteBtn.disabled = true;
                confirmDeleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Удаление...';
                
                const formData = new FormData(deleteAddressForm);
                
                fetch('/customer/profile/address/delete', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-Token': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('success', data.message || 'Адрес успешно удален');
                        // Перезагрузка страницы для обновления списка адресов
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        showNotification('error', data.message || 'Ошибка при удалении адреса');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('error', 'Произошла ошибка при удалении адреса');
                })
                .finally(() => {
                    confirmDeleteBtn.disabled = false;
                    confirmDeleteBtn.innerHTML = 'Удалить';
                    // Закрытие модального окна
                    const modal = bootstrap.Modal.getInstance(deleteAddressModal);
                    if (modal) modal.hide();
                });
            });
        }
    }
    
    /**
     * Инициализация загрузки аватара
     */
    function initAvatarUpload() {
        const avatarUpload = document.getElementById('avatarUpload');
        
        if (!avatarUpload) return;
        
        avatarUpload.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;
            
            // Проверка типа файла
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                showNotification('error', 'Разрешены только изображения в форматах JPG, PNG или GIF');
                this.value = '';
                return;
            }
            
            // Проверка размера файла (макс. 2MB)
            if (file.size > 2 * 1024 * 1024) {
                showNotification('error', 'Размер файла не должен превышать 2MB');
                this.value = '';
                return;
            }
            
            // Предпросмотр изображения
            const reader = new FileReader();
            reader.onload = function(e) {
                const avatarContainer = document.querySelector('.avatar-container');
                if (avatarContainer) {
                    avatarContainer.innerHTML = `<img src="${e.target.result}" alt="Аватар" class="img-fluid" style="width: 100%; height: 100%; object-fit: cover;">`;
                }
            };
            reader.readAsDataURL(file);
        });
    }
    
    /**
     * Инициализация переключателей видимости пароля
     */
    function initPasswordToggle() {
        const toggleButtons = document.querySelectorAll('.toggle-password');
        
        toggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const passwordInput = document.getElementById(targetId);
                
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    this.innerHTML = '<i class="bi bi-eye-slash"></i>';
                } else {
                    passwordInput.type = 'password';
                    this.innerHTML = '<i class="bi bi-eye"></i>';
                }
            });
        });
    }
    
    /**
     * Отображение уведомления
     */
    function showNotification(type, message) {
        // Проверка существования функции Toast из Bootstrap
        if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
            // Создание элемента toast
            const toastEl = document.createElement('div');
            toastEl.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0`;
            toastEl.setAttribute('role', 'alert');
            toastEl.setAttribute('aria-live', 'assertive');
            toastEl.setAttribute('aria-atomic', 'true');
            
            toastEl.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Закрыть"></button>
                </div>
            `;
            
            // Добавление toast в контейнер
            let toastContainer = document.querySelector('.toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
                document.body.appendChild(toastContainer);
            }
            
            toastContainer.appendChild(toastEl);
            
            // Инициализация и отображение toast
            const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
            toast.show();
            
            // Удаление toast после скрытия
            toastEl.addEventListener('hidden.bs.toast', function() {
                toastEl.remove();
            });
        } else {
            // Fallback для случая, если Bootstrap Toast недоступен
            alert(`${type === 'success' ? 'Успех' : 'Ошибка'}: ${message}`);
        }
    }
});
