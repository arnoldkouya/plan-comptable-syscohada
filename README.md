# Plan Comptable SYSCOHAD

Bibliothèque PHP complète du **Plan Comptable SYSCOHADA** — le système comptable normalisé des États membres de l'OHADA.

> Recherche, validation, navigation et interrogation de tous les comptes (classes 1 à 9), avec leurs libellés, leur nature (bilan / gestion / hors-bilan / CAGE) et leur sens (débit / crédit / mixte).

---

## Installation

```bash
composer require arnoldkouya/plan-comptable-syscohada
```

**Prérequis** : PHP ≥ 8.1

---

## Démarrage rapide

```php
use Syscohada\PlanComptable;
use Syscohada\Validator;

$plan = PlanComptable::getInstance();

// Rechercher un compte par code exact
$compte = $plan->find('6011');
echo $compte; // "6011 — Dans la Région"

// Trouver ou lancer une exception
$compte = $plan->findOrFail('4111');
echo $compte->getLibelle(); // "Clients"

// Tous les comptes d'une classe
$classe6 = $plan->classe(6); // array de Compte

// Recherche plein texte (insensible à la casse et aux accents)
$resultats = $plan->search('marchandises');

// Enfants directs
$enfants = $plan->enfants('60'); // [601, 602, 603, ...]

// Tous les descendants (récursif)
$descendants = $plan->descendants('60');

// Chemin depuis la racine
$chemin = $plan->chemin('6011');
// → [60, 601, 6011]
```

---

## API complète

### `PlanComptable`

| Méthode | Description |
|---|---|
| `getInstance()` | Singleton — retourne l'instance unique |
| `find(string $code)` | Retourne le `Compte` ou `null` |
| `findOrFail(string $code)` | Retourne le `Compte` ou lance `CompteNotFoundException` |
| `exists(string $code)` | Vérifie l'existence d'un code |
| `valider(string $code)` | Alias de `exists` |
| `classe(int $classe)` | Tous les comptes d'une classe (1–9) |
| `classeNiveau(int $classe, int $niveau)` | Comptes d'une classe à un niveau donné |
| `enfants(string $code)` | Enfants directs |
| `descendants(string $code)` | Tous les descendants (récursif) |
| `search(string $terme)` | Recherche sur le code ou le libellé |
| `nature(string $nature)` | Filtre par nature (`bilan`, `gestion`, `hors-bilan`, `cage`) |
| `sens(string $sens)` | Filtre par sens (`debit`, `credit`, `mixte`) |
| `chemin(string $code)` | Chemin depuis la racine jusqu'au compte |
| `all()` | Tous les comptes du plan |
| `statistiques()` | Statistiques du plan |

### `Compte`

| Méthode | Retour | Description |
|---|---|---|
| `getCode()` | `string` | Code du compte |
| `getLibelle()` | `string` | Libellé officiel |
| `getClasse()` | `int` | Classe (1–9) |
| `getNiveau()` | `int` | Niveau / profondeur (1–4) |
| `getCodeParent()` | `?string` | Code du compte parent |
| `getNature()` | `?string` | `bilan`, `gestion`, `hors-bilan`, `cage` |
| `getSens()` | `?string` | `debit`, `credit`, `mixte` |
| `isActif()` | `bool` | Compte actif |
| `isGroupe()` | `bool` | Niveau ≤ 2 (compte racine) |
| `isDivisionnaire()` | `bool` | Niveau = 4 |
| `toArray()` | `array` | Représentation tableau |
| `__toString()` | `string` | `"CODE — Libellé"` |

### `Validator`

```php
use Syscohada\Validator;

Validator::formatValide('6011');          // true
Validator::existeDansPlan('9999');        // false
Validator::classeDeCode('601');           // 6
Validator::niveauDeCode('6011');          // 4
Validator::estBilan('401');               // true
Validator::estGestion('701');             // true
Validator::estCharge('661');              // true
Validator::estProduit('701');             // true
Validator::estTresorerie('521');          // true
Validator::invalides(['6011','XXXX']);    // ['XXXX']
```

---

## Exemples avancés

### Arbre d'une classe

```php
$plan = PlanComptable::getInstance();

// Comptes de niveau 2 (sous-classes) de la classe 4
$sousClasses = $plan->classeNiveau(4, 2);

foreach ($sousClasses as $parent) {
    echo $parent->getCode() . ' — ' . $parent->getLibelle() . "\n";
    foreach ($plan->enfants($parent->getCode()) as $enfant) {
        echo '  └─ ' . $enfant . "\n";
    }
}
```

### Valider un lot de codes

```php
$codesUtilisateur = ['6011', '4111', '9999', 'ABCD'];
$invalides = Validator::invalides($codesUtilisateur);
// ['9999', 'ABCD']
```

### Export JSON de la classe 5

```php
$tresorerie = $plan->classe(5);
echo json_encode(
    array_map(fn ($c) => $c->toArray(), $tresorerie),
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
);
```

### Statistiques

```php
$stats = $plan->statistiques();
echo "Total comptes : " . $stats['total'] . "\n";
// Total comptes : 560

foreach ($stats['par_classe'] as $classe => $nb) {
    echo "Classe $classe : $nb comptes\n";
}
```

---

## Tests

```bash
composer install
composer test
```

---

## Structure du projet

```
src/
├── Compte.php              # Value object d'un compte
├── PlanComptable.php       # Registre principal (singleton)
├── Validator.php           # Utilitaires de validation
├── Contracts/
│   ├── CompteInterface.php
│   └── PlanComptableInterface.php
├── Data/
│   └── comptes.php         # Données complètes du plan (classes 1–9)
└── Exceptions/
    └── CompteNotFoundException.php
tests/
└── PlanComptableTest.php
```

---

## Couverture du plan

| Classe | Description | Comptes |
|---|---|---|
| 1 | Ressources durables | ✅ Complet |
| 2 | Actif immobilisé | ✅ Complet |
| 3 | Stocks | ✅ Complet |
| 4 | Comptes de tiers | ✅ Complet |
| 5 | Trésorerie | ✅ Complet |
| 6 | Charges activités ordinaires | ✅ Complet |
| 7 | Produits activités ordinaires | ✅ Complet |
| 8 | Autres charges et produits | ✅ Complet |
| 9 | Engagements hors bilan + CAGE | ✅ Complet |

---

## Licence

MIT — voir [LICENSE](LICENSE)
