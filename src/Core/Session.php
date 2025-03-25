<?php

namespace App\Core;

use App\Models\User;

class Session {
    protected const FLASH_KEY = 'flash_messages';
    protected const USER_KEY = 'user';
    protected const ROLES_KEY = 'user_roles';
    protected const CSRF_TOKEN = 'csrf_token';

    private ?User $user = null;

    /**
     * Initialize session and load user data if available
     */
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

    /**
     * Set flash message
     * @param string $key Message key
     * @param string $message Message content
     * @return void
     */
    public function setFlash(string $key, string $message): void {
        $_SESSION[self::FLASH_KEY][$key] = [
            'remove' => false,
            'value' => $message
        ];
    }

    /**
     * Get flash message by key
     * @param string $key Message key
     * @return string|false Message content or false if not found
     */
    public function getFlash(string $key): string|false {
        return $_SESSION[self::FLASH_KEY][$key]['value'] ?? false;
    }

    /**
     * Set session value
     * @param string $key Session key
     * @param mixed $value Session value
     * @return void
     */
    public function set(string $key, mixed $value): void {
        $_SESSION[$key] = $value;
    }

    /**
     * Get session value
     * @param string $key Session key
     * @return mixed Session value or false if not found
     */
    public function get(string $key): mixed {
        return $_SESSION[$key] ?? false;
    }

    /**
     * Remove session value
     * @param string $key Session key
     * @return void
     */
    public function remove(string $key): void {
        unset($_SESSION[$key]);
    }

    /**
     * Set user data
     * @param User|null $user User object or null to remove user data
     * @return void
     */
    public function setUser(?User $user): void {
        $this->user = $user;
        if ($user) {
            $this->set(self::USER_KEY, $user->id);
            $this->setRoles($user->getRoles());
        } else {
            $this->remove(self::USER_KEY);
            $this->remove(self::ROLES_KEY);
        }
    }

    /**
     * Get user data
     * @return User|null User object or null if no user is logged in
     */
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

    /**
     * Set user roles
     * @param array $roles Array of role names
     * @return void
     */
    public function setRoles(array $roles): void {
        $this->set(self::ROLES_KEY, array_column($roles, 'name'));
    }

    /**
     * Get user roles
     * @return array Array of role names
     */
    public function getRoles(): array {
        return $this->get(self::ROLES_KEY) ?? [];
    }

    /**
     * Check if user has a specific role
     * @param string $role Role name
     * @return bool True if user has the role, false otherwise
     */
    public function hasRole(string $role): bool {
        $roles = $this->getRoles();
        return in_array($role, $roles);
    }

    /**
     * Check if user is logged in
     * @return bool True if user is logged in, false otherwise
     */
    public function isLoggedIn(): bool {
        return $this->user !== null;
    }

    /**
     * Generate a CSRF token and store it in the session
     * @return string Generated CSRF token
     */
    public function generateCsrfToken(): string {
        $token = bin2hex(random_bytes(32));
        $this->set(self::CSRF_TOKEN, $token);
        return $token;
    }

    /**
     * Get the current CSRF token or generate a new one if it doesn't exist
     * @return string CSRF token
     */
    public function getCsrfToken(): string {
        $token = $this->get(self::CSRF_TOKEN);
        if (!$token) {
            $token = $this->generateCsrfToken();
        }
        return $token;
    }

    /**
     * Validate a CSRF token against the one stored in the session
     * @param string $token Token to validate
     * @return bool True if token is valid, false otherwise
     */
    public function validateCsrfToken(string $token): bool {
        $storedToken = $this->get(self::CSRF_TOKEN);
        if (!$storedToken) {
            return false;
        }
        return hash_equals($storedToken, $token);
    }

    /**
     * Destroy session
     * @return void
     */
    public function destroy(): void {
        session_destroy();
        $this->user = null;
    }

    /**
     * Remove flash messages on object destruction
     * @return void
     */
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
