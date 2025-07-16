<?php
$page_title = "Ajouter un Document";
$active_page = "add_document";
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Mode édition
$edit_mode = isset($_GET['edit']);
$document_data = null;

if ($edit_mode) {
    $doc_id = $_GET['edit'];
    $document_data = $document->getDocumentById($doc_id);
    
    if (!$document_data) {
        $_SESSION['error_msg'] = "Document introuvable";
        header('Location: documents.php');
        exit();
    }
    
    // Vérifier que l'utilisateur est admin ou propriétaire du document
    if ($_SESSION['user_role'] != 'admin' && $document_data->created_by != $_SESSION['user_id']) {
        $_SESSION['error_msg'] = "Vous n'avez pas la permission de modifier ce document";
        header('Location: documents.php');
        exit();
    }
    
    $page_title = "Modifier le Document";
}

// Récupérer tous les utilisateurs pour la sélection des destinataires
$users = $user->getAllUsers();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = [
        'title' => trim($_POST['title']),
        'type' => $_POST['type'],
        'sender' => trim($_POST['sender']),
        'recipient' => trim($_POST['recipient']),
        'date_reception' => $_POST['date_reception'],
        'date_creation' => $_POST['date_creation'],
        'subject' => trim($_POST['subject']),
        'keywords' => trim($_POST['keywords']),
        'status' => $_POST['status'],
        'created_by' => $_SESSION['user_id'],
        'reference' => trim($_POST['reference'])
    ];

    // Gestion du fichier
    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = UPLOAD_DIR;
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_ext = strtolower(pathinfo($_FILES['document_file']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];
        
        if (in_array($file_ext, $allowed_ext)) {
            $new_filename = uniqid() . '.' . $file_ext;
            $target_path = $upload_dir . '/' . $new_filename;

            if (move_uploaded_file($_FILES['document_file']['tmp_name'], $target_path)) {
                $data['file_path'] = $new_filename;
                
                // Supprimer ancien fichier en mode édition
                if ($edit_mode && $document_data->file_path && file_exists($upload_dir . '/' . $document_data->file_path)) {
                    unlink($upload_dir . '/' . $document_data->file_path);
                }
            }
        } else {
            $_SESSION['error_msg'] = "Type de fichier non autorisé. Formats acceptés: " . implode(', ', $allowed_ext);
            header('Location: ' . $_SERVER['PHP_SELF'] . ($edit_mode ? '?edit=' . $doc_id : ''));
            exit();
        }
    } elseif ($edit_mode) {
        $data['file_path'] = $document_data->file_path;
    }

    // Enregistrement
    if ($edit_mode) {
        $data['id'] = $doc_id;
        
        if ($document->updateDocument($data)) {
            $_SESSION['success_msg'] = "Document mis à jour avec succès";
            header('Location: view_document.php?id=' . $doc_id);
            exit();
        }
    } else {
        try {
            if ($document->addDocument($data)) {
                $document_id = $db->lastInsertId();
                
                // Gestion des destinataires
                if (!empty($_POST['recipients'])) {
                    foreach ($_POST['recipients'] as $recipient_id) {
                        $document->sendDocumentToUser($document_id, $recipient_id, $_SESSION['user_id']);
                    }
                }
                
                $_SESSION['success_msg'] = "Document ajouté avec succès";
                header('Location: documents.php');
                exit();
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                $_SESSION['error_msg'] = "La référence existe déjà. Veuillez en choisir une autre.";
            } else {
                $_SESSION['error_msg'] = "Erreur lors de l'enregistrement";
            }
            header('Location: add_document.php');
            exit();
        }
    }
    
    $_SESSION['error_msg'] = "Erreur lors de l'enregistrement";
}

// Générer référence si nouveau document
if (!$edit_mode) {
    $type = $_POST['type'] ?? 'entrant';
    $reference = $document->generateReference($type);
} else {
    $reference = $document_data->reference;
}
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h3 class="card-title">
                <i class="fas <?= $edit_mode ? 'fa-edit' : 'fa-plus-circle' ?>"></i>
                <?= $edit_mode ? 'Modifier le Document' : 'Ajouter un Document' ?>
            </h3>
        </div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Référence <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="reference" 
                                   value="<?= htmlspecialchars($reference) ?>" required>
                            <small class="text-muted">Format recommandé: TYPE-ANNEE-NUM (ex: ENT-2023-001)</small>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Type de Document <span class="text-danger">*</span></label>
                            <select class="form-select" name="type" id="doc_type" required>
                                <option value="entrant" <?= ($edit_mode && $document_data->type == 'entrant') ? 'selected' : '' ?>>Entrant</option>
                                <option value="sortant" <?= ($edit_mode && $document_data->type == 'sortant') ? 'selected' : '' ?>>Sortant</option>
                                <option value="interne" <?= ($edit_mode && $document_data->type == 'interne') ? 'selected' : '' ?>>Interne</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label">Titre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" value="<?= $edit_mode ? htmlspecialchars($document_data->title) : '' ?>" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Expéditeur</label>
                            <input type="text" class="form-control" name="sender" id="sender_field" 
                                   value="<?= $edit_mode ? htmlspecialchars($document_data->sender) : '' ?>"
                                   <?= (!$edit_mode || $document_data->type == 'entrant') ? 'required' : '' ?>>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Destinataire</label>
                            <input type="text" class="form-control" name="recipient" id="recipient_field"
                                   value="<?= $edit_mode ? htmlspecialchars($document_data->recipient) : '' ?>"
                                   <?= ($edit_mode && $document_data->type == 'sortant') ? 'required' : '' ?>>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Date de Réception <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="date_reception" 
                                   value="<?= $edit_mode ? $document_data->date_reception : date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Date du Document</label>
                            <input type="date" class="form-control" name="date_creation" 
                                   value="<?= $edit_mode ? $document_data->date_creation : date('Y-m-d') ?>">
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label">Objet <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="subject" rows="3" required><?= $edit_mode ? htmlspecialchars($document_data->subject) : '' ?></textarea>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Mots-clés (séparés par des virgules)</label>
                            <input type="text" class="form-control" name="keywords" 
                                   value="<?= $edit_mode ? htmlspecialchars($document_data->keywords) : '' ?>">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Statut <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" required>
                                <option value="nouveau" <?= ($edit_mode && $document_data->status == 'nouveau') ? 'selected' : '' ?>>Nouveau</option>
                                <option value="en_cours" <?= ($edit_mode && $document_data->status == 'en_cours') ? 'selected' : '' ?>>En Cours</option>
                                <option value="traité" <?= ($edit_mode && $document_data->status == 'traité') ? 'selected' : '' ?>>Traité</option>
                                <option value="archivé" <?= ($edit_mode && $document_data->status == 'archivé') ? 'selected' : '' ?>>Archivé</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label">Fichier Joint</label>
                            <input type="file" class="form-control" name="document_file">
                            <?php if ($edit_mode && $document_data->file_path): ?>
                                <div class="mt-2">
                                    <small>Fichier actuel: </small>
                                    <a href="<?= URL_ROOT . '/' . UPLOAD_DIR . '/' . $document_data->file_path ?>" target="_blank">
                                        <?= $document_data->file_path ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="mb-3">
                            <label class="form-label">Envoyer à des utilisateurs (optionnel)</label>
                            <select class="form-select" name="recipients[]" multiple>
                                <?php foreach($users as $u): ?>
                                    <?php if($u->id != $_SESSION['user_id']): ?>
                                        <option value="<?= $u->id ?>" <?= ($edit_mode && $document->isRecipient($doc_id, $u->id)) ? 'selected' : '' ?>>
                                            <?= $u->username ?> (<?= $u->full_name ?>)
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Maintenez Ctrl (Windows) ou Cmd (Mac) pour sélectionner plusieurs utilisateurs</small>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="d-flex justify-content-end">
                            <a href="<?= $edit_mode ? 'view_document.php?id=' . $doc_id : 'documents.php' ?>" 
                               class="btn btn-secondary me-2">
                                Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <?= $edit_mode ? 'Mettre à jour' : 'Enregistrer' ?>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const docType = document.getElementById('doc_type');
    const senderField = document.getElementById('sender_field');
    const recipientField = document.getElementById('recipient_field');

    function updateFields() {
        const type = docType.value;
        senderField.required = type === 'entrant';
        recipientField.required = type === 'sortant';
    }

    // Initialisation
    updateFields();
    
    // Écouteur de changement
    docType.addEventListener('change', updateFields);
});
</script>

<?php include 'includes/footer.php'; ?>