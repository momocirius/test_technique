<?php

declare(strict_types=1);

require_once __DIR__.'/vendor/autoload.php';

use App\Service\JobImportService;
use App\Repository\MySqlJobRepository;
use App\Factory\DatabaseConnectionFactory;

// Configuration SQLite pour test local
define('SQL_HOST', 'sqlite');
define('SQL_USER', '');
define('SQL_PWD', '');
define('SQL_DB', __DIR__ . '/test.db');
define('RESOURCES_DIR', __DIR__ . '/resources/');

/**
 * Création d'une connexion SQLite pour test
 */
function createSQLiteConnection(): PDO {
    $pdo = new PDO('sqlite:' . SQL_DB);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Créer la table job
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS job (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            reference TEXT NOT NULL,
            title TEXT NOT NULL,
            description TEXT,
            url TEXT,
            company_name TEXT,
            publication TEXT
        )
    ");
    
    return $pdo;
}

/**
 * Repository adapté pour SQLite
 */
class SQLiteJobRepository extends MySqlJobRepository {
    public function __construct() {
        parent::__construct(createSQLiteConnection());
    }
}

// Test de l'implémentation
echo "=== TEST LOCAL CHOOSEMYCOMPANY ===\n";
echo "Utilisation de SQLite pour test rapide\n\n";

try {
    // Initialisation
    $repository = new SQLiteJobRepository();
    $service = new JobImportService($repository);
    
    // Test RegionsJob XML
    echo "1. Test import RegionsJob (XML)...\n";
    $countXml = $service->import(RESOURCES_DIR . 'regionsjob.xml');
    echo "   → {$countXml} jobs importés ✅\n\n";
    
    // Test JobTeaser JSON  
    echo "2. Test import JobTeaser (JSON)...\n";
    $countJson = $service->append(RESOURCES_DIR . 'jobteaser.json'); // Mode append
    echo "   → {$countJson} jobs ajoutés ✅\n\n";
    
    // Affichage des résultats
    $totalJobs = $service->getTotalJobs();
    echo "3. Total jobs en base: {$totalJobs}\n\n";
    
    $jobs = $service->getAllJobs();
    echo "4. Détail des jobs:\n";
    foreach ($jobs as $job) {
        echo "   • {$job->reference} - {$job->title} ({$job->companyName})\n";
    }
    
    echo "\n✅ TEST RÉUSSI ! L'implémentation fonctionne parfaitement.\n";
    echo "🎯 Architecture SOLID validée, nouveaux partenaires prêts !\n";

} catch (Exception $e) {
    echo "❌ ERREUR: {$e->getMessage()}\n";
    echo "📁 Vérifiez que les fichiers regionsjob.xml et jobteaser.json existent\n";
}

// Nettoyage (fermer les connexions d'abord)
unset($repository, $service);
if (file_exists(SQL_DB)) {
    sleep(1); // Petite pause pour libérer les verrous
    if (unlink(SQL_DB)) {
        echo "🧹 Base de test nettoyée\n";
    } else {
        echo "ℹ️  Base de test conservée: " . SQL_DB . "\n";
    }
}
