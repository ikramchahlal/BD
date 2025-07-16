<?php
$page_title = "Gestion des Documents";
$active_page = "documents";
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Vérification supplémentaire pour la suppression
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $doc = $document->getDocumentById($id);
    
    // Empêcher la suppression si l'utilisateur n'est pas admin ou propriétaire
    if($_SESSION['user_role'] != 'admin' && $doc->created_by != $_SESSION['user_id']) {
        $_SESSION['error_msg'] = "Vous n'avez pas la permission de supprimer ce document.";
        header('Location: documents.php');
        exit();
    }
    
    try {
        if($document->deleteDocument($id)) {
            $_SESSION['success_msg'] = "Document supprimé avec succès.";
        } else {
            $_SESSION['error_msg'] = "Erreur lors de la suppression du document.";
        }
    } catch(PDOException $e) {
        $_SESSION['error_msg'] = "Erreur lors de la suppression: " . $e->getMessage();
        error_log('Delete Error: ' . $e->getMessage());
    }
    header('Location: documents.php');
    exit();
}

// Récupérer tous les documents (filtrés automatiquement par la classe Document)
try {
    $all_documents = $document->getDocuments();
} catch(PDOException $e) {
    $error_msg = "Erreur lors de la récupération des documents: " . $e->getMessage();
    error_log($error_msg);
}
?>

<?php include 'includes/header.php'; ?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2><i class="fas fa-file-alt text-primary"></i> Gestion des Documents</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="add_document.php" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Ajouter un Document
        </a>
    </div>
</div>

<?php if(isset($error_msg)): ?>
<div class="alert alert-danger"><?php echo $error_msg; ?></div>
<?php endif; ?>

<?php if(isset($_SESSION['success_msg'])): ?>
<div class="alert alert-success"><?php echo $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?></div>
<?php endif; ?>

<?php if(isset($_SESSION['error_msg'])): ?>
<div class="alert alert-danger"><?php echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-bordered" id="documentsTable">
                <thead class="table-light">
                    <tr>
                        <th>Référence</th>
                        <th>Titre</th>
                        <th>Type</th>
                        <th>Expéditeur/Destinataire</th>
                        <th>Date Réception</th>
                        <th>Statut</th>
                        <th>Créé par</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(isset($all_documents)): ?>
                        <?php foreach($all_documents as $doc): ?>
                        <tr>
                            <td><?php echo $doc->reference; ?></td>
                            <td><?php echo $doc->title; ?></td>
                            <td>
                                <?php 
                                $badge_class = '';
                                switch($doc->type) {
                                    case 'entrant': $badge_class = 'bg-primary'; break;
                                    case 'sortant': $badge_class = 'bg-success'; break;
                                    case 'interne': $badge_class = 'bg-secondary'; break;
                                }
                                ?>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($doc->type); ?></span>
                            </td>
                            <td>
                                <?php echo $doc->type == 'entrant' ? $doc->sender : $doc->recipient; ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($doc->date_reception)); ?></td>
                            <td>
                                <?php 
                                $status_class = '';
                                switch($doc->status) {
                                    case 'nouveau': $status_class = 'bg-info'; break;
                                    case 'en_cours': $status_class = 'bg-warning text-dark'; break;
                                    case 'traité': $status_class = 'bg-success'; break;
                                    case 'archivé': $status_class = 'bg-secondary'; break;
                                }
                                ?>
                                <span class="badge <?php echo $status_class; ?>"><?php echo ucfirst(str_replace('_', ' ', $doc->status)); ?></span>
                            </td>
                            <td><?php echo $doc->created_by_name; ?></td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="view_document.php?id=<?php echo $doc->id; ?>" class="btn btn-outline-primary" title="Voir">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if($_SESSION['user_role'] == 'admin' || $doc->created_by == $_SESSION['user_id']): ?>
                                    <a href="add_document.php?edit=<?php echo $doc->id; ?>" class="btn btn-outline-success" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if($_SESSION['user_role'] == 'admin'): ?>
                                    <a href="documents.php?delete=<?php echo $doc->id; ?>" class="btn btn-outline-danger" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce document?')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser DataTables
    $('#documentsTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/fr-FR.json'
        },
        order: [[4, 'desc']]
    });
});
</script>

<?php include 'includes/footer.php'; ?>