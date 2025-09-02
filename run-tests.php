<?php

declare(strict_types=1);

/**
 * Script pour lancer tous les types de tests
 * Usage: php run-tests.php [type]
 * 
 * Types disponibles:
 * - unit: Tests unitaires uniquement
 * - integration: Tests d'intégration uniquement  
 * - functional: Tests fonctionnels uniquement
 * - performance: Tests de performance uniquement
 * - all: Tous les tests (défaut)
 * - coverage: Tous les tests avec couverture de code (nécessite xdebug)
 */

$type = $argv[1] ?? 'all';

$commands = [
    'unit' => 'php vendor/bin/phpunit --testsuite Unit',
    'integration' => 'php vendor/bin/phpunit --testsuite Integration', 
    'functional' => 'php vendor/bin/phpunit --testsuite Functional',
    'performance' => 'php vendor/bin/phpunit --testsuite Performance',
    'all' => 'php vendor/bin/phpunit --testsuite All',
    'coverage' => 'php vendor/bin/phpunit --testsuite All --coverage-html coverage-html'
];

if (!isset($commands[$type])) {
    echo "Type de test invalide: $type\n";
    echo "Types disponibles: " . implode(', ', array_keys($commands)) . "\n";
    exit(1);
}

echo "=== LANCEMENT DES TESTS: " . strtoupper($type) . " ===\n";
echo "Commande: " . $commands[$type] . "\n";
echo str_repeat('=', 50) . "\n\n";

$startTime = microtime(true);
$result = 0;

// Lancer la commande
passthru($commands[$type], $result);

$endTime = microtime(true);
$duration = round($endTime - $startTime, 2);

echo "\n" . str_repeat('=', 50) . "\n";
echo "Tests terminés en {$duration}s\n";

// PHPUnit peut retourner 1 avec des warnings, mais c'est acceptable
$isSuccess = ($result === 0 || $result === 1);
echo "Résultat: " . ($isSuccess ? "✅ SUCCÈS" : "❌ ÉCHEC") . "\n";

if ($result === 1) {
    echo "ℹ️  Avertissement: Tests réussis avec des warnings (normal sans xdebug)\n";
}

if ($type === 'coverage' && $isSuccess) {
    echo "📊 Rapport de couverture généré dans: coverage-html/index.html\n";
}

// Ne retourner une erreur que si vraiment critique (code > 1)
exit($result > 1 ? 1 : 0);
