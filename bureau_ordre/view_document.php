<?php
$page_title = "Détails du Document";
require_once 'includes/config.php';
require_once 'includes/auth.php';

if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_msg'] = "ID de document invalide";
    header('Location: documents.php');
    exit();
}

$doc_id = (int)$_GET['id'];
$doc = $document->getDocumentById($doc_id);

if(!$doc) {
    $_SESSION['error_msg'] = "Document introuvable ou accès non autorisé";
    header('Location: documents.php');
    exit();
}

// Vérification des permissions
$is_owner = ($doc->created_by == $_SESSION['user_id']);
$is_recipient = $document->isRecipient($doc_id, $_SESSION['user_id']);
$is_admin = ($_SESSION['user_role'] == 'admin');

if(!$is_admin && !$is_owner && !$is_recipient) {
    $_SESSION['error_msg'] = "Vous n'avez pas accès à ce document";
    header('Location: documents.php');
    exit();
}

// Récupérer les destinataires (visible seulement pour admin/propriétaire)
$recipients_info = [];
if ($is_admin || $is_owner) {
    try {
        $db->query('SELECT u.username, u.full_name, dr.created_at 
                   FROM document_recipients dr 
                   JOIN users u ON dr.recipient_id = u.id 
                   WHERE dr.document_id = :doc_id');
        $db->bind(':doc_id', $doc_id);
        $recipients_info = $db->resultSet();
    } catch (PDOException $e) {
        error_log("Error fetching recipients: " . $e->getMessage());
        $_SESSION['error_msg'] = "Erreur lors de la récupération des destinataires";
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-file-alt me-2"></i> Détails du Document</h4>
                    <div>
                        <?php if ($is_admin || $is_owner): ?>
                        <a href="add_document.php?edit=<?= $doc->id ?>" class="btn btn-sm btn-light me-2">
                            <i class="fas fa-edit me-1"></i> Modifier
                        </a>
                        <?php endif; ?>
                        <a href="<?= $is_recipient ? 'received_documents.php' : 'documents.php' ?>" class="btn btn-sm btn-light">
                            <i class="fas fa-arrow-left me-1"></i> Retour
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-4">
                            <h5 class="border-bottom pb-2">Informations de Base</h5>
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <th width="40%">Référence:</th>
                                        <td><?= htmlspecialchars($doc->reference) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Type:</th>
                                        <td>
                                            <?php 
                                            $badge_class = '';
                                            switch($doc->type) {
                                                case 'entrant': $badge_class = 'bg-primary'; break;
                                                case 'sortant': $badge_class = 'bg-success'; break;
                                                case 'interne': $badge_class = 'bg-secondary'; break;
                                            }
                                            ?>
                                            <span class="badge <?= $badge_class ?>"><?= ucfirst($doc->type) ?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Titre:</th>
                                        <td><?= htmlspecialchars($doc->title) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Statut:</th>
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
                                            <span class="badge <?= $status_class ?>"><?= ucfirst(str_replace('_', ' ', $doc->status)) ?></span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h5 class="border-bottom pb-2">Dates</h5>
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <th width="40%">Date de Réception:</th>
                                        <td><?= date('d/m/Y', strtotime($doc->date_reception)) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Date de Création:</th>
                                        <td><?= date('d/m/Y', strtotime($doc->date_creation)) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Créé le:</th>
                                        <td><?= date('d/m/Y H:i', strtotime($doc->created_at)) ?></td>
                                    </tr>
                                    <?php if($doc->updated_at): ?>
                                    <tr>
                                        <th>Mis à jour le:</th>
                                        <td><?= date('d/m/Y H:i', strtotime($doc->updated_at)) ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-4">
                            <h5 class="border-bottom pb-2">Parties Concernées</h5>
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <th width="40%"><?= $doc->type == 'entrant' ? 'Expéditeur:' : 'Destinataire:' ?></th>
                                        <td><?= htmlspecialchars($doc->type == 'entrant' ? $doc->sender : $doc->recipient) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Créé par:</th>
                                        <td><?= htmlspecialchars($doc->created_by_name) ?></td>
                                    </tr>
                                    <?php if (!empty($recipients_info)): ?>
                                    <tr>
                                        <th>Envoyé à:</th>
                                        <td>
                                            <ul class="list-unstyled mb-0">
                                                <?php foreach ($recipients_info as $recipient): ?>
                                                    <li>
                                                        <?= htmlspecialchars($recipient->full_name) ?> (<?= htmlspecialchars($recipient->username) ?>)
                                                        <small class="text-muted">- <?= date('d/m/Y H:i', strtotime($recipient->created_at)) ?></small>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h5 class="border-bottom pb-2">Métadonnées</h5>
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <th width="40%">Mots-clés:</th>
                                        <td>
                                            <?php 
                                            $keywords = explode(',', $doc->keywords);
                                            foreach($keywords as $keyword) {
                                                if(trim($keyword) != '') {
                                                    echo '<span class="badge bg-light text-dark me-1">' . htmlspecialchars(trim($keyword)) . '</span>';
                                                }
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Fichier:</th>
                                        <td>
                                            <?php if($doc->file_path): ?>
                                                <a href="<?= URL_ROOT . '/' . UPLOAD_DIR . '/' . htmlspecialchars($doc->file_path) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-download me-1"></i> Télécharger
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">Aucun fichier attaché</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-align-left me-2"></i>Objet du Document</h5>
                            </div>
                            <div class="card-body">
                                <div class="p-3 bg-white border rounded">
                                    <?= nl2br(htmlspecialchars($doc->subject)) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>