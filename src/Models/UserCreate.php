<?php

namespace Models\User;

require_once 'User.php';

/**
 * Class UserCreate
 * @package Models\User
 *
 * This class is responsible for creating a new user in the database.
 * It validates the data and checks if the username already exists.
 */
class UserCreate extends User {
    private $username;
    private $password;
    private $email;
    private $name;
    private $bio;
    private $profile_pic;
    private $data_changes_history;

    /**
     * @param $connection \PDO
     * @param $username
     * @param $password
     * @param $email
     * @param $name
     * @param $bio = null
     * @param $profile_pic = null
     */
    public function __construct($connection, $username, $password, $email, $name, $bio = null, $profile_pic = null) {
        parent::__construct($connection);
        $this->username = strtolower(trim($username));
        $this->password = $password;
        $this->email = $email;
        $this->name = $name;
        $this->bio = $bio;
        $this->profile_pic = $profile_pic;
        $this->setDataChangesHistory();
    }

    /**
     * Creates a new user in the database if data is valid
     *
     * @throws \Exception if data validation fails
     * @return bool
     */
    public function createUser() {
        if (!$this->validate()) {
            throw new \Exception("Data validation failed.");
        }
        // Ensure username starts with @
        $this->username = (str_starts_with($this->username, '@')) ? $this->username : chr(64) . $this->username;
        // Hash the password
        $hashedPassword = password_hash($this->password, PASSWORD_DEFAULT);
        // Prepare the SQL statement and execute it
        $stmt = $this->getConnection()->prepare("INSERT INTO users (username, password, email, name, bio, profile_pic, data_changes_history) VALUES (?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $this->username,
            $hashedPassword,
            $this->email,
            $this->name,
            $this->bio,
            $this->profile_pic,
            json_encode($this->data_changes_history)
        ]);
    }

    /**
     * Validates all required data
     *
     * @return bool
     */
    public function validate(): bool {
        return $this->isValidUsername() && $this->isValidEmail() && $this->isValidPassword();
    }

    /**
     * Check if username is not empty, follows a valid pattern, and is not already taken or a reserved word
     *
     * @return bool
     */
    private function isValidUsername(): bool {
        // Check if username is not empty
        if (empty($this->username)) {
            return false;
        }
        // Allow only alphanumeric characters, numbers and underscores, 3 to 20 characters. At least one letter
        if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $this->username) && !preg_match('/[a-zA-Z]/', $this->username)) {
            return false;
        }
        // Check if username already exists
        if ($this->isUsernameExist($this->username, $this->getConnection())) {
            return false;
        }
        // Check if username is not a reserved word
        $reservedWords = ['qwerty', 'dany', 'admin', 'root', 'user', 'test'];
        if (in_array($this->username, $reservedWords)) {
            return false;
        }
        return true;
    }

    /**
     * Check if email is not empty and is a valid email address format
     *
     * @return bool
     */
    private function isValidEmail(): bool {
        // Check if email is not empty
        if (empty($this->email)) {
            return false;
        }
        // Check if email is a valid email address format
        return filter_var($this->email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Check if password is not empty and meets the required strength
     *
     * @return bool
     */
    private function isValidPassword(): bool {
        // Check if password is not empty
        if (empty($this->password)) {
            return false;
        }
        // One Rule: At least 6 characters long
        return strlen($this->password) >= 6;
    }

    /**
     * Checks if a username already exists in the database to avoid duplication (error: username must be unique)
     *
     * @param string $username
     * @param \PDO $connection
     *
     * @return bool
     */
    public function isUsernameExist(string $username, \PDO $connection) : bool {
        $username = '@' . $username;
        // Prepare the SQL statement and execute it
        $stmt = $connection->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Sets the data changes history
     *
     * @param int|null $userId
     * @param array|null $fields
     */
    protected function setDataChangesHistory(int $userId = null, array $fields = null): void {
        $this->data_changes_history = [
            'origin' => [
                'username'    => $this->username,
                'email'       => $this->email,
                'name'        => $this->name,
                'bio'         => $this->bio,
                'profile_pic' => $this->profile_pic
            ]
        ];
    }
}
