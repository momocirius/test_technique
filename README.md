# Test technique ChooseMyCompany – Développeur back-end / fullstack

**Confidentiel – Ne pas transmettre**

## Pré-requis
- Docker installé

## Contexte

ChooseMyCompany est un site d'information sur les employeurs.  
Grâce aux enquêtes salariés que nous administrons, nous récoltons des notes par entreprise, que nous mettons à disposition des visiteurs.

Ces visiteurs étant souvent en recherche d’emploi, nous avons souhaité leur proposer des offres directement sur notre site.  
Notre partenaire actuel – le site d’emploi RegionsJob.com – nous transmet ses offres via un flux XML (simulé ici par un fichier statique).

Ce projet contient un script permettant l’import de ces offres via la ligne de commande.

Un nouveau partenaire – le site JobTeaser.com – souhaite également proposer ses offres sur notre plateforme.

> Il est probable que d'autres partenaires soient ajoutés à l’avenir.

## À réaliser en 1h30

- Adapter le code pour importer un nouveau flux JSON (`jobteaser.json`)
- Adapter si besoin le modèle de données
- Refactoriser le code existant (initialement conçu pour un seul partenaire)

Ce test a pour but d’évaluer :
- Votre capacité à structurer un code existant
- Votre respect des principes de conception (POO, SOLID, design patterns)
- Votre capacité à anticiper l’évolutivité du système

## Critères d’évaluation

- Qualité du code : lisibilité, découpage, tests
- Robustesse : gestion des erreurs, des exceptions, validation des données
- Compréhension du besoin : adaptation pertinente du code existant (Open/Close, etc.)
- Propreté du code : indentation, nommage, respect des conventions

## Si vous aviez plus de temps

Ajoutez un fichier `UPGRADE.md` pour détailler les évolutions que vous auriez envisagées avec plus de temps (découpage, tests, performances, sécurité...).

## Commandes utiles

```bash
./init.sh        # Initialise et lance le projet
./run-import.sh  # Lance l’import des offres
./clean.sh       # Stoppe et nettoie l’environnement
