<?php
$page_title = "Recherche de Documents";
$active_page = "search";
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Initialisation des variables
$search_results = [];
$search_query = '';
$search_type = 'keyword'; // Valeur par défaut
$error_msg = '';

// Traitement de la recherche
// Dans la partie traitement de la recherche
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    $search_query = trim($_GET['search_query']);
    $search_type = $_GET['search_type'] ?? 'keyword';
    
    if (!empty($search_query)) {
        try {
            // Détermine si l'utilisateur est admin
            $is_admin = ($_SESSION['user_role'] === 'admin');
            
            if ($search_type === 'keyword') {
                // Nettoyage supplémentaire des termes de recherche
                $search_query = preg_replace('/[^a-zA-Z0-9éèêëàâäôöûüçÉÈÊËÀÂÄÔÖÛÜÇ\s,-]/', '', $search_query);
                $search_results = $document->searchByKeyword(
                    $search_query, 
                    $_SESSION['user_id'], 
                    $is_admin
                );
            } else {
                // Pour la recherche par référence, on veut une correspondance exacte
                $search_results = $document->searchByReference(
                    trim($search_query), 
                    $_SESSION['user_id'], 
                    $is_admin
                );
            }
        } catch (Exception $e) {
            $error_msg = "Une erreur est survenue lors de la recherche : " . $e->getMessage();
            error_log("Search error: " . $e->getMessage());
        }
    } else {
        $error_msg = "Veuillez entrer un terme de recherche";
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-search me-2"></i>Recherche de Documents</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger"><?php echo $error_msg; ?></div>
                <?php endif; ?>
                
                <form method="get" action="search.php" class="row g-3">
                    <div class="col-md-8">
                        <div class="input-group">
                            <input type="text" name="search_query" class="form-control" 
                                   placeholder="Entrez un mot-clé ou une référence..." 
                                   value="<?php echo htmlspecialchars($search_query); ?>" required>
                            <button type="submit" name="search" class="btn btn-primary">
                                <i class="fas fa-search"></i> Rechercher
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="search_type" 
                                   id="search_keyword" value="keyword" 
                                   <?php echo ($search_type === 'keyword') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="search_keyword">Mot-clé</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="search_type" 
                                   id="search_reference" value="reference"
                                   <?php echo ($search_type === 'reference') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="search_reference">Référence exacte</label>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if (isset($_GET['search'])): ?>
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">
                    <i class="fas fa-file-alt me-2"></i>
                    Résultats de la recherche (<?php echo count($search_results); ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($search_results)): ?>
                    <div class="alert alert-info">Aucun document trouvé pour votre recherche.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="table-light">
                                <tr>
                                    <th>Référence</th>
                                    <th>Titre</th>
                                    <th>Type</th>
                                    <th>Mots-clés</th>
                                    <th>Date Réception</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($search_results as $doc): ?>
                                <tr>
                                    <td><?php echo $doc->reference; ?></td>
                                    <td><?php echo htmlspecialchars($doc->title); ?></td>
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
                                    <td><?php echo htmlspecialchars($doc->keywords); ?></td>
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
                                        <div class="btn-group btn-group-sm">
                                            <a href="view_document.php?id=<?php echo $doc->id; ?>" class="btn btn-outline-primary" title="Voir">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($_SESSION['user_role'] == 'admin' || $doc->created_by == $_SESSION['user_id']): ?>
                                            <a href="add_document.php?edit=<?php echo $doc->id; ?>" class="btn btn-outline-success" title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>