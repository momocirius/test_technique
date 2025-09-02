<?php

declare(strict_types=1);

namespace App\Factory;

class DatabaseConnectionFactory
{
    /**
     * Crée une connexion PDO configurée pour l'application
     * 
     * @param string $host Hôte de la base de données
     * @param string $username Nom d'utilisateur
     * @param string $password Mot de passe
     * @param string $databaseName Nom de la base de données
     * @return \PDO Instance PDO configurée
     * @throws \Exception Si la connexion échoue
     */
    public static function create(string $host, string $username, string $password, string $databaseName): \PDO
    {
        try {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $databaseName);
            
            $pdo = new \PDO($dsn, $username, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
            
            return $pdo;
        } catch (\PDOException $e) {
            throw new \Exception('Erreur de connexion à la base de données: ' . $e->getMessage());
        }
    }

    /**
     * Crée une connexion avec les paramètres globaux de l'application
     * 
     * @return \PDO Instance PDO configurée
     */
    public static function createFromGlobals(): \PDO
    {
        return self::create(SQL_HOST, SQL_USER, SQL_PWD, SQL_DB);
    }
}
