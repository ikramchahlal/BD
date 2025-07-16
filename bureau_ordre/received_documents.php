<?php
$page_title = "Documents Reçus";
$active_page = "received";
require_once 'includes/config.php';
require_once 'includes/auth.php';

$received_documents = $document->getReceivedDocuments($_SESSION['user_id']);
?>

<?php include 'includes/header.php'; ?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2><i class="fas fa-inbox text-primary"></i> Documents Reçus</h2>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if(empty($received_documents)): ?>
            <div class="alert alert-info">Aucun document reçu pour le moment.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-bordered" id="receivedTable">
                    <thead class="table-light">
                        <tr>
                            <th>Référence</th>
                            <th>Titre</th>
                            <th>Expéditeur</th>
                            <th>Date Réception</th>
                            <th>Date d'Envoi</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($received_documents as $doc): ?>
                        <tr>
                            <td><?= htmlspecialchars($doc->reference) ?></td>
                            <td><?= htmlspecialchars($doc->title) ?></td>
                            <td><?= htmlspecialchars($doc->sender_name) ?></td>
                            <td><?= date('d/m/Y', strtotime($doc->date_reception)) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($doc->received_at)) ?></td>
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
                            <td>
                                <a href="view_document.php?id=<?= $doc->id ?>" class="btn btn-sm btn-outline-primary" title="Voir">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $('#receivedTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/fr-FR.json'
        },
        order: [[4, 'desc']]
    });
});
</script>

<?php include 'includes/footer.php'; ?>