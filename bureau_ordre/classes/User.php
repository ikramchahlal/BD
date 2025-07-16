<?php
class User {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }

    // Inscription d'un nouvel utilisateur
    public function register($data) {
        // Validation des données
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            throw new Exception('Tous les champs obligatoires doivent être remplis');
        }

        // Vérification des doublons
        if ($this->findUserByEmail($data['email'])) {
            throw new Exception('Cet email est déjà utilisé');
        }

        if ($this->findUserByUsername($data['username'])) {
            throw new Exception('Ce nom d\'utilisateur est déjà pris');
        }

        // Insertion dans la base de données
        $this->db->query('INSERT INTO users (username, email, password, full_name, created_at) 
                         VALUES (:username, :email, :password, :full_name, NOW())');
        
        $this->db->bind(':username', trim($data['username']));
        $this->db->bind(':email', trim($data['email']));
        $this->db->bind(':password', $data['password']); // Mot de passe en clair
        $this->db->bind(':full_name', !empty($data['full_name']) ? trim($data['full_name']) : null);
        
        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    // Connexion d'un utilisateur
    public function login($username, $password) {
        $this->db->query('SELECT * FROM users WHERE username = :username');
        $this->db->bind(':username', $username);
        
        $row = $this->db->single();
        
        // Comparaison directe des mots de passe (non hachés)
        if ($row && $password === $row->password) {
            return $row;
        }
        return false;
    }
    
    // Vérifie si un email existe déjà
    public function findUserByEmail($email) {
        $this->db->query('SELECT id FROM users WHERE email = :email');
        $this->db->bind(':email', $email);
        return $this->db->single() ? true : false;
    }
    
    // Vérifie si un nom d'utilisateur existe déjà
    public function findUserByUsername($username) {
        $this->db->query('SELECT id FROM users WHERE username = :username');
        $this->db->bind(':username', $username);
        return $this->db->single() ? true : false;
    }
    
    // Récupère un utilisateur par son ID
    public function getUserById($id) {
        $this->db->query('SELECT * FROM users WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->single();
    }
    
    // Récupère tous les utilisateurs
    public function getAllUsers() {
        $this->db->query('SELECT id, username, email, full_name, created_at FROM users ORDER BY created_at DESC');
        return $this->db->resultSet();
    }

    // Mise à jour du profil utilisateur
    public function updateProfile($data) {
        $this->db->query('UPDATE users SET full_name = :full_name, email = :email WHERE id = :id');
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':full_name', trim($data['full_name']));
        $this->db->bind(':email', trim($data['email']));
        
        return $this->db->execute();
    }
    
    // Changement de mot de passe
    public function changePassword($data) {
        $this->db->query('UPDATE users SET password = :password WHERE id = :id');
        $this->db->bind(':id', $data['id']);
        $this->db->bind(':password', $data['password']); // Mot de passe en clair
        
        return $this->db->execute();
    }

    // Vérification du mot de passe actuel
    public function verifyCurrentPassword($user_id, $password) {
        $this->db->query('SELECT password FROM users WHERE id = :id');
        $this->db->bind(':id', $user_id);
        $user = $this->db->single();
        
        return ($user && $password === $user->password);
    }
    
    // Suppression d'un utilisateur
    public function deleteUser($user_id) {
        $this->db->query('DELETE FROM users WHERE id = :id');
        $this->db->bind(':id', $user_id);
        return $this->db->execute();
    }
}