<?php

declare(strict_types=1);

namespace App\Factory;

use App\Parser\JobParserInterface;
use App\Parser\RegionsJobXmlParser;
use App\Parser\JobTeaserJsonParser;

class JobParserFactory
{
    /**
     * Mapping des partenaires vers les classes de parsers
     */
    private static array $parsers = [
        'regionsjob' => RegionsJobXmlParser::class,
        'jobteaser' => JobTeaserJsonParser::class,
        // Futurs partenaires :
        // 'indeed' => IndeedJsonParser::class,
        // 'monster' => MonsterXmlParser::class,
    ];

    /**
     * Mapping des extensions vers les parsers par défaut (fallback)
     */
    private static array $defaultParsers = [
        'xml' => RegionsJobXmlParser::class,
        'json' => JobTeaserJsonParser::class,
    ];

    /**
     * Crée un parser approprié selon le partenaire
     * 
     * @param string $partner Nom du partenaire (regionsjob, jobteaser, indeed, etc.)
     * @return JobParserInterface Instance du parser
     * @throws \InvalidArgumentException Si le partenaire n'est pas supporté
     */
    public static function create(string $partner): JobParserInterface
    {
        if (!isset(self::$parsers[$partner])) {
            throw new \InvalidArgumentException("Partenaire non supporté: {$partner}");
        }
        
        $parserClass = self::$parsers[$partner];
        return new $parserClass();
    }

    /**
     * Crée un parser basé sur l'extension (méthode de fallback)
     * 
     * @param string $extension Extension du fichier (xml, json)
     * @return JobParserInterface Instance du parser
     * @throws \InvalidArgumentException Si l'extension n'est pas supportée
     */
    public static function createByExtension(string $extension): JobParserInterface
    {
        if (!isset(self::$defaultParsers[$extension])) {
            throw new \InvalidArgumentException("Extension non supportée: {$extension}");
        }
        
        $parserClass = self::$defaultParsers[$extension];
        return new $parserClass();
    }

    /**
     * Détecte automatiquement le partenaire à partir du nom du fichier
     * 
     * @param string $filepath Chemin du fichier
     * @return JobParserInterface Instance du parser
     * @throws \InvalidArgumentException Si le partenaire ne peut pas être détecté
     */
    public static function createFromFile(string $filepath): JobParserInterface
    {
        $filename = strtolower(pathinfo($filepath, PATHINFO_FILENAME));
        $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        
        // 1. Essayer de détecter le partenaire par le nom du fichier
        foreach (array_keys(self::$parsers) as $partner) {
            if (str_contains($filename, $partner)) {
                return self::create($partner);
            }
        }
        
        // 2. Fallback : utiliser l'extension (comportement actuel)
        return self::createByExtension($extension);
    }

    /**
     * Détection avancée par analyse du contenu du fichier
     * 
     * @param string $filepath Chemin du fichier
     * @return JobParserInterface Instance du parser
     */
    public static function createFromContent(string $filepath): JobParserInterface
    {
        $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        
        if ($extension === 'json') {
            $content = file_get_contents($filepath);
            $data = json_decode($content, true);
            
            // Détection JobTeaser : présence de "offerUrlPrefix"
            if (isset($data['offerUrlPrefix'])) {
                return self::create('jobteaser');
            }
            
            // Futurs patterns de détection :
            // if (isset($data['jobs'][0]['company'])) return self::create('indeed');
        }
        
        if ($extension === 'xml') {
            $xml = simplexml_load_file($filepath);
            
            // Détection RegionsJob : présence d'éléments <item><ref>
            if (isset($xml->item) && isset($xml->item->ref)) {
                return self::create('regionsjob');
            }
        }
        
        // Fallback
        return self::createByExtension($extension);
    }

    /**
     * Retourne la liste des partenaires supportés
     * 
     * @return string[]
     */
    public static function getSupportedPartners(): array
    {
        return array_keys(self::$parsers);
    }

    /**
     * Retourne la liste des extensions supportées
     * 
     * @return string[]
     */
    public static function getSupportedExtensions(): array
    {
        return array_keys(self::$defaultParsers);
    }
}
