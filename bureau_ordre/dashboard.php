<?php
$page_title = "Tableau de bord";
$active_page = "dashboard";
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Récupérer les documents récents
$recent_documents = $document->getDocuments(5);
?>

<?php include 'includes/header.php'; ?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Derniers Documents</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Référence</th>
                                <th>Titre</th>
                                <th>Type</th>
                                <th>Expéditeur/Destinataire</th>
                                <th>Date Réception</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_documents as $doc): ?>
                            <tr>
                                <td><?php echo $doc->reference; ?></td>
                                <td><?php echo substr($doc->title, 0, 30) . (strlen($doc->title) > 30 ? '...' : ''); ?></td>
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
                                <td>
                                    <a href="view_document.php?id=<?php echo $doc->id; ?>" class="btn btn-sm btn-outline-primary" title="Voir">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>