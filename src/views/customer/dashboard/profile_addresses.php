<?php
/** @var $this \App\Core\View */
/** @var $user \App\Models\User */
/** @var $addresses array */
?>

<!-- Секция с адресами доставки -->
<div class="col-12 mb-4">
    <div class="bg-white shadow-sm rounded p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 mb-0">Адреса доставки</h2>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                <i class="bi bi-plus-lg me-1"></i> Добавить адрес
            </button>
        </div>
        
        <div class="address-list">
            <?php if (empty($addresses)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-geo-alt fs-1 mb-2"></i>
                    <p>У вас пока нет сохраненных адресов доставки</p>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($addresses as $address): ?>
                        <div class="col-md-6" data-address-id="<?= $address->id ?>">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title mb-2"><?= $this->escape($address->title) ?></h5>
                                    <p class="card-text mb-1"><strong>Получатель:</strong> <?= $this->escape($address->recipient_name) ?></p>
                                    <p class="card-text mb-1"><strong>Телефон:</strong> <?= $this->escape($address->phone) ?></p>
                                    <p class="card-text mb-1">
                                        <?= $this->escape($address->country) ?>, 
                                        <?= $this->escape($address->city) ?>, 
                                        <?= $this->escape($address->postal_code) ?>
                                    </p>
                                    <p class="card-text">
                                        <?= $this->escape($address->street) ?>, 
                                        д. <?= $this->escape($address->house) ?>
                                        <?= $address->apartment ? ', кв. ' . $this->escape($address->apartment) : '' ?>
                                    </p>
                                </div>
                                <div class="card-footer bg-transparent d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary edit-address-btn" 
                                            data-address-id="<?= $address->id ?>" 
                                            data-title="<?= $this->escape($address->title) ?>" 
                                            data-recipient="<?= $this->escape($address->recipient_name) ?>" 
                                            data-phone="<?= $this->escape($address->phone) ?>" 
                                            data-country="<?= $this->escape($address->country) ?>" 
                                            data-city="<?= $this->escape($address->city) ?>" 
                                            data-street="<?= $this->escape($address->street) ?>" 
                                            data-house="<?= $this->escape($address->house) ?>" 
                                            data-apartment="<?= $this->escape($address->apartment) ?>" 
                                            data-postal="<?= $this->escape($address->postal_code) ?>" 
                                            data-default="<?= $address->is_default ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger delete-address-btn"
                                            data-address-id="<?= $address->id ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Модальное окно добавления адреса -->
<div class="modal fade" id="addAddressModal" tabindex="-1" aria-labelledby="addAddressModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAddressModalLabel">Добавление нового адреса</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <form id="addAddressForm" method="post" action="/customer/address/add">
                    <input type="hidden" name="csrf_token" value="<?= $this->csrf_token ?>">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="title" class="form-label">Название адреса</label>
                            <input type="text" class="form-control" id="title" name="title" placeholder="Например: Дом, Работа" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="recipient_name" class="form-label">Получатель</label>
                            <input type="text" class="form-control" id="recipient_name" name="recipient_name" placeholder="ФИО получателя" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="address_phone" class="form-label">Телефон</label>
                            <input type="tel" class="form-control" id="address_phone" name="phone" placeholder="+972 (___) ___-__-__" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="country" class="form-label">Страна</label>
                            <input type="text" class="form-control" id="country" name="country" placeholder="Израиль" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="city" class="form-label">Город</label>
                            <input type="text" class="form-control" id="city" name="city" placeholder="Тель-Авив" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="postal_code" class="form-label">Почтовый индекс</label>
                            <input type="text" class="form-control" id="postal_code" name="postal_code" placeholder="123456" required>
                        </div>
                        
                        <div class="col-md-12">
                            <label for="street" class="form-label">Улица</label>
                            <input type="text" class="form-control" id="street" name="street" placeholder="ул. Ленина" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="house" class="form-label">Дом</label>
                            <input type="text" class="form-control" id="house" name="house" placeholder="10" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="apartment" class="form-label">Квартира</label>
                            <input type="text" class="form-control" id="apartment" name="apartment" placeholder="101">
                        </div>
                        
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_default" name="is_default">
                                <label class="form-check-label" for="is_default">
                                    Использовать как адрес по умолчанию
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="saveAddressBtn">Сохранить</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно редактирования адреса -->
<div class="modal fade" id="editAddressModal" tabindex="-1" aria-labelledby="editAddressModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editAddressModalLabel">Редактирование адреса</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <form id="editAddressForm" method="post" action="/customer/address/update">
                    <input type="hidden" name="csrf_token" value="<?= $this->csrf_token ?>">
                    <input type="hidden" name="address_id" id="edit_address_id">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edit_title" class="form-label">Название адреса</label>
                            <input type="text" class="form-control" id="edit_title" name="title" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="edit_recipient_name" class="form-label">Получатель</label>
                            <input type="text" class="form-control" id="edit_recipient_name" name="recipient_name" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="edit_phone" class="form-label">Телефон</label>
                            <input type="tel" class="form-control" id="edit_phone" name="phone" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="edit_country" class="form-label">Страна</label>
                            <input type="text" class="form-control" id="edit_country" name="country" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="edit_city" class="form-label">Город</label>
                            <input type="text" class="form-control" id="edit_city" name="city" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="edit_postal_code" class="form-label">Почтовый индекс</label>
                            <input type="text" class="form-control" id="edit_postal_code" name="postal_code" required>
                        </div>
                        
                        <div class="col-md-12">
                            <label for="edit_street" class="form-label">Улица</label>
                            <input type="text" class="form-control" id="edit_street" name="street" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="edit_house" class="form-label">Дом</label>
                            <input type="text" class="form-control" id="edit_house" name="house" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="edit_apartment" class="form-label">Квартира</label>
                            <input type="text" class="form-control" id="edit_apartment" name="apartment">
                        </div>
                        
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_is_default" name="is_default">
                                <label class="form-check-label" for="edit_is_default">
                                    Использовать как адрес по умолчанию
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" id="updateAddressBtn">Сохранить изменения</button>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно подтверждения удаления адреса -->
<div class="modal fade" id="deleteAddressModal" tabindex="-1" aria-labelledby="deleteAddressModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteAddressModalLabel">Удаление адреса</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <p>Вы действительно хотите удалить этот адрес?</p>
                <form id="deleteAddressForm" method="post" action="/customer/address/delete">
                    <input type="hidden" name="csrf_token" value="<?= $this->csrf_token ?>">
                    <input type="hidden" name="address_id" id="delete_address_id">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Удалить</button>
            </div>
        </div>
    </div>
</div>
