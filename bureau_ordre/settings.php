<?php

$page_title = "Paramètres";
require_once 'includes/config.php';
require_once 'includes/auth.php';

$current_user = $user->getUserById($_SESSION['user_id']);
$password_error = '';
$profile_error = '';

// Traitement du changement de mot de passe
if (isset($_POST['change_password'])) {
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $password_error = "Tous les champs sont obligatoires";
    } elseif (!password_verify($current_password, $current_user->password)) {
        $password_error = "Mot de passe actuel incorrect";
    } elseif ($new_password !== $confirm_password) {
        $password_error = "Les nouveaux mots de passe ne correspondent pas";
    } elseif (strlen($new_password) < 8) {
        $password_error = "Le mot de passe doit contenir au moins 8 caractères";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        if ($user->changePassword(['id' => $_SESSION['user_id'], 'password' => $hashed_password])) {
            $_SESSION['success_msg'] = "Mot de passe changé avec succès";
            header('Location: settings.php');
            exit();
        } else {
            $password_error = "Erreur lors de la mise à jour du mot de passe";
        }
    }
}

// Traitement de la mise à jour du profil
if (isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);

    if (empty($full_name)) {
        $profile_error = "Le nom complet est obligatoire";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $profile_error = "Email invalide";
    } else {
        $data = [
            'id' => $_SESSION['user_id'],
            'full_name' => $full_name,
            'email' => $email
        ];

        if ($user->updateProfile($data)) {
            $_SESSION['success_msg'] = "Profil mis à jour avec succès";
            header('Location: settings.php');
            exit();
        } else {
            $profile_error = "Erreur lors de la mise à jour du profil";
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-user-edit me-2"></i>Modifier le Profil</h4>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Nom d'utilisateur</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($current_user->username) ?>" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nom Complet</label>
                            <input type="text" class="form-control" name="full_name" 
                                   value="<?= htmlspecialchars($current_user->full_name) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" 
                                   value="<?= htmlspecialchars($current_user->email) ?>" required>
                        </div>
                        
                        <?php if ($profile_error): ?>
                            <div class="alert alert-danger"><?= $profile_error ?></div>
                        <?php endif; ?>
                        
                        <div class="d-grid">
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-key me-2"></i>Changer le Mot de Passe</h4>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Mot de passe actuel</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nouveau mot de passe</label>
                            <input type="password" class="form-control" name="new_password" required>
                            <small class="text-muted">Minimum 8 caractères</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Confirmer le nouveau mot de passe</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                        
                        <?php if ($password_error): ?>
                            <div class="alert alert-danger"><?= $password_error ?></div>
                        <?php endif; ?>
                        
                        <div class="d-grid">
                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fas fa-key me-1"></i> Changer le mot de passe
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>