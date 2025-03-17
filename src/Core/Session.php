<?php

namespace App\Core;

use App\Models\User;

class Session {
    protected const FLASH_KEY = 'flash_messages';
    protected const USER_KEY = 'user';
    protected const ROLES_KEY = 'user_roles';

    private ?User $user = null;

    public function __construct() {
        session_start();
        $flashMessages = $_SESSION[self::FLASH_KEY] ?? [];
        foreach ($flashMessages as $key => &$flashMessage) {
            $flashMessage['remove'] = true;
        }
        $_SESSION[self::FLASH_KEY] = $flashMessages;

        // Load user if session exists
        $userId = $this->get(self::USER_KEY);
        if ($userId) {
            $this->user = User::findOne(['id' => $userId]);
        }
    }

    public function setFlash($key, $message) {
        $_SESSION[self::FLASH_KEY][$key] = [
            'remove' => false,
            'value' => $message
        ];
    }

    public function getFlash($key) {
        return $_SESSION[self::FLASH_KEY][$key]['value'] ?? false;
    }

    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public function get($key) {
        return $_SESSION[$key] ?? false;
    }

    public function remove($key) {
        unset($_SESSION[$key]);
    }

    public function setUser(?User $user) {
        $this->user = $user;
        if ($user) {
            $this->set(self::USER_KEY, $user->id);
            $this->setRoles($user->getRoles());
        } else {
            $this->remove(self::USER_KEY);
            $this->remove(self::ROLES_KEY);
        }
    }

    public function getUser(): ?User {
        return $this->user;
    }
    
    /**
     * Get the ID of the currently logged-in user
     * 
     * @return int|null The user ID or null if no user is logged in
     */
    public function getUserId(): ?int {
        return $this->user ? $this->user->id : null;
    }

    public function setRoles(array $roles) {
        $this->set(self::ROLES_KEY, array_column($roles, 'name'));
    }

    public function getRoles(): array {
        return $this->get(self::ROLES_KEY) ?? [];
    }

    public function hasRole(string $role): bool {
        $roles = $this->getRoles();
        return in_array($role, $roles);
    }

    public function isLoggedIn(): bool {
        return $this->user !== null;
    }

    public function destroy() {
        session_destroy();
        $this->user = null;
    }

    public function __destruct() {
        $flashMessages = $_SESSION[self::FLASH_KEY] ?? [];
        foreach ($flashMessages as $key => $flashMessage) {
            if ($flashMessage['remove']) {
                unset($flashMessages[$key]);
            }
        }
        $_SESSION[self::FLASH_KEY] = $flashMessages;
    }
}
