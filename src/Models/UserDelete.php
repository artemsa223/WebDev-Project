<?php

namespace Models\User;

require_once 'User.php';

/**
 * Class UserDelete
 * @package Models\User
 *
 * This class is responsible for deleting user data from the database.
 * It allows for soft deletion (marking as deleted) and permanent deletion of user profiles.
 */
class UserDelete extends User {
    /**
     * @param $connection \PDO
     */
    public function __construct($connection) {
        parent::__construct($connection);
    }

    /**
     * Soft deletes a user by setting the is_deleted flag to true
     *
     * @param int $userId
     *
     * @return bool
     */
    public function deleteUser(int $userId): bool {
        $stmt = $this->getConnection()->prepare("UPDATE users SET is_deleted = TRUE WHERE user_id = ?");
        if ($stmt->execute([$userId])) {
            $this->setDataChangesHistory($userId, ['is_deleted' => 1]);
            return true;
        }
        return false;
    }

    /**
     * Permanently deletes a user from the database
     *
     * @param int $userId
     *
     * @return bool
     */
    public function actuallyDeleteUser(int $userId): bool {
        if ($_SESSION['admin_authenticated']) {
            $stmt = $this->getConnection()->prepare("DELETE FROM users WHERE user_id = ?");
            return $stmt->execute([$userId]);
        }
        return false;
    }

    /**
     * Checks if a username already exists in the database to avoid duplication (error: username must be unique)
     *
     * @param string $username
     * @param \PDO $connection
     *
     * @return bool
     */
    public function isUsernameExist(string $username, \PDO $connection): bool {
        if (!str_starts_with($username, '@')) {
            $username = '@' . $username;
        }
        // Prepare the SQL statement and execute it
        $stmt = $connection->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetchColumn() > 0;
    }
}
