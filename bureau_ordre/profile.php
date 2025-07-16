<?php
$page_title = "Profil Utilisateur";
require_once 'includes/config.php';
require_once 'includes/auth.php';

$current_user = $user->getUserById($_SESSION['user_id']);

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = [
        'id' => $_SESSION['user_id'],
        'full_name' => trim($_POST['full_name']),
        'email' => trim($_POST['email'])
    ];
    
    if($user->updateProfile($data)) {
        $_SESSION['success_msg'] = "Profil mis à jour avec succès.";
        header('Location: profile.php');
        exit();
    } else {
        $_SESSION['error_msg'] = "Erreur lors de la mise à jour du profil.";
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-user-circle me-2"></i>Profil Utilisateur</h4>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">Nom d'utilisateur</label>
                                <input type="text" class="form-control" id="username" value="<?php echo $current_user->username; ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Nom Complet</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo $current_user->full_name; ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo $current_user->email; ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="role" class="form-label">Rôle</label>
                                <input type="text" class="form-control" id="role" value="<?php echo ucfirst($current_user->role); ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="created_at" class="form-label">Compte créé le</label>
                                <input type="text" class="form-control" id="created_at" value="<?php echo date('d/m/Y H:i', strtotime($current_user->created_at)); ?>" readonly>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Enregistrer les modifications
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>