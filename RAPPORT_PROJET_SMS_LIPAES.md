# MÉMOIRE DE PROJET DE FIN D'ÉTUDES
## CONCEPTION ET RÉALISATION D'UN SYSTÈME DE GESTION DE STOCKS (SMS) POUR L'ENTREPRISE LIPAES

---
<!-- PAGE i : PAGE DE COUVERTURE (MISE À JOUR) -->
### [PAGE i - COUVERTURE (MODÈLE EFET)]

```
========================================================================
[ LOGO EFET ]                               [ LOGO SOCIÉTÉ ACCUEIL ]
                                                     LIPAES
========================================================================

                       Projet de fin de formation

    En vue d'obtenir le diplôme de Technicien Spécialisé en Filière
                       (Développement Digital)

THEME :
+----------------------------------------------------------------------+
|                                                                      |
|            CONCEPTION ET RÉALISATION D'UN SYSTÈME DE                 |
|               GESTION DE STOCKS (SMS) POUR LIPAES                    |
|                                                                      |
+----------------------------------------------------------------------+

Réalisé par :                               Encadré par : (M. ou Mme)
* Mehdi Taoussi                             * M. [Nom de l'Encadrant EFET]
                                            * M. [Nom du Maître de Stage]

                               Année Scolaire : 2025 - 2026
========================================================================
```

---
<!-- PAGE ii : DÉDICACES -->
### [PAGE ii - DÉDICACES]

#### DÉDICACES

À mes parents,  
*Pour leur soutien indéfectible, leur patience et leurs sacrifices qui m'ont permis de poursuivre mes études dans d'excellentes conditions.*  

À mes enseignants,  
*Qui m'ont transmis le goût du savoir, de l'exigence et de la rigueur scientifique.*  

À mes amis et collègues de promotion,  
*Qui m'ont accompagné tout au long de ce parcours universitaire et professionnel.*  

---
<!-- PAGE iii : REMERCIEMENTS -->
### [PAGE iii - REMERCIEMENTS]

#### REMERCIEMENTS

Je tiens tout d'abord à exprimer ma profonde gratitude à l'ensemble du personnel d'encadrement de l'École Supérieure de Génie Informatique, pour la qualité de l'enseignement théorique et pratique qui m'a été dispensé tout au long de mon cursus de Licence Professionnelle.

Mes remerciements les plus sincères s'adressent à mon encadrant de stage au sein de l'entreprise **LIPAES**, pour son accueil chaleureux, sa disponibilité de tous les instants, ses conseils avisés et la confiance qu'il m'a accordée en me confiant le pilotage et la réalisation de ce projet stratégique de numérisation logistique.

Je souhaite également remercier l'ensemble des opérateurs d'entrepôt et des collaborateurs des services logistiques et informatiques de **LIPAES**, qui ont pris le temps de m'expliquer le fonctionnement quotidien des flux physiques et qui ont participé activement à la phase de collecte des besoins et de validation des interfaces utilisateurs.

Enfin, je remercie les membres du jury qui ont accepté d'évaluer ce travail de fin d'études et d'apporter leur expertise critique lors de ma soutenance de mémoire.

---
<!-- PAGE iv : TABLE DES MATIÈRES -->
### [PAGE iv - TABLE DES MATIÈRES]

#### TABLE DES MATIÈRES

*   **Introduction Générale** ....................................................................... 1
*   **Chapitre 1 : Contexte Opérationnel et Enjeux Logistiques de LIPAES** ..... 2
    *   1.1 Contexte et opportunités de numérisation ................................... 2
    *   1.2 Analyse des processus de gestion manuelle actuels ..................... 3
    *   1.3 Enjeux de la traçabilité des stocks de l'entreprise ........................ 4
*   **Chapitre 2 : Cahier des Charges Fonctionnel et Analyse du Besoin** ....... 5
    *   2.1 Gestion du catalogue et cycle de vie des produits ....................... 5
    *   2.2 Système d'emplacements hiérarchiques physiques ....................... 6
    *   2.3 Système de gestion des livraisons et des clients ......................... 7
    *   2.4 Intégration du système de QR Codes ........................................ 8
*   **Chapitre 3 : Modélisation UML et Conception de la Solution** ............. 9
    *   3.1 Choix de modélisation orientée objet ........................................... 9
    *   3.2 Diagrammes UML (Cas d'utilisation, Classes, Séquence, États) ...... 10
    *   3.3 Modélisation des déclencheurs automatiques (Triggers) ............. 12
*   **Chapitre 4 : Architecture Logicielle et Sécurisation Applicative** .......... 13
    *   4.1 Choix de la stack technique PHP Pur et MySQL ............................ 13
    *   4.2 Sécurisation contre les failles SQL injection et XSS .................... 14
    *   4.3 Implémentation du jeton de session CSRF ................................... 15
*   **Chapitre 5 : Déploiement Physique et Manuel d'Utilisation** ............. 17
    *   5.1 Configuration de l'environnement XAMPP local ........................... 17
    *   5.2 Manuel de l'administrateur et du personnel de terrain ............... 18
    *   5.3 Procédure de gestion des exports et rapports ............................. 20
*   **Conclusion Générale et Perspectives de Phase II** .................................... 21
*   **Bibliographie et Webographie** ........................................................... 22
*   **Annexes Techniques** ........................................................................... I

---
<!-- PAGE v : LISTE DES TABLEAUX ET FIGURES -->
### [PAGE v - LISTE DES TABLEAUX ET FIGURES]

#### LISTE DES TABLEAUX ET FIGURES

##### Liste des Tableaux
*   **Tableau 1 :** Analyse comparative des défis logistiques et solutions de SMS (Page 4)
*   **Tableau 2 :** Droits d'accès fonctionnels par profil utilisateur (Page 6)
*   **Tableau 3 :** Dictionnaire des données de la table des utilisateurs (admins) (Page 11)
*   **Tableau 4 :** Dictionnaire des données de la table des produits (products) (Page 11)
*   **Tableau 5 :** Dictionnaire des données de la table des emplacements (locations) (Page 12)

##### Liste des Figures
*   **Figure 1 :** Diagramme de Cas d'Utilisation global (UML Use Case) (Page 9)
*   **Figure 2 :** Modèle Entité-Relation / Schéma physique de la base de données (Page 10)
*   **Figure 3 :** Diagramme de Séquence du processus de scan et d'ajustement rapide (Page 11)
*   **Figure 4 :** Diagramme d'États-Transitions du cycle de livraison (Page 12)
*   **Figure 5 :** Représentation de l'arborescence des fichiers sources de l'application (Page 14)

---
<!-- PAGE vi : GLOSSAIRE ET ABRÉVIATIONS -->
### [PAGE vi - GLOSSAIRE ET ABRÉVIATIONS]

#### GLOSSAIRE ET ABRÉVIATIONS

*   **SMS :** Stock Management System. Désigne l'application de gestion de stocks développée pour LIPAES.
*   **SKU :** Stock Keeping Unit. Code d'identification unique attribué à chaque référence de produit.
*   **PDO :** PHP Data Objects. Interface d'accès aux bases de données orientée objet intégrée à PHP.
*   **CSRF :** Cross-Site Request Forgery. Type d'attaque forçant un utilisateur à exécuter des actions non voulues.
*   **XSS :** Cross-Site Scripting. Type de vulnérabilité permettant l'injection de scripts client dans des pages web.
*   **RBAC :** Role-Based Access Control. Mécanisme de contrôle d'accès basé sur les rôles affectés aux utilisateurs.
*   **UML :** Unified Modeling Language. Langage de modélisation graphique standardisé pour le génie logiciel.
*   **SVG :** Scalable Vector Graphics. Format d'image vectoriel XML utilisé ici pour le rendu haute fidélité des QR codes.

---
<!-- PAGE 1 : INTRODUCTION GÉNÉRALE -->
### [PAGE 1]

#### INTRODUCTION GÉNÉRALE

À l'ère de la transformation numérique, l'optimisation des flux d'information au sein de la supply chain constitue un facteur de compétitivité essentiel pour les entreprises commerciales. La performance opérationnelle n'est plus seulement mesurée par la qualité intrinsèque des produits vendus, mais également par la rapidité, la fiabilité et la traçabilité des opérations de stockage et de distribution. C'est dans ce cadre logistique que s'inscrit le projet de fin d'études réalisé pour le compte de l'entreprise **LIPAES**.

LIPAES fait face à une croissance importante de ses activités, caractérisée par une diversification de son catalogue et une multiplication des points de stockage physiques. Face à ce volume grandissant de flux physiques, la gestion manuelle ou semi-manuelle s'est heurtée à ses limites opérationnelles : erreurs fréquentes de saisie, manque de visibilité en temps réel sur les stocks disponibles, difficultés de localisation des produits dans les allées et manque de traçabilité des anomalies de stock.

Le projet **Stock Management System (SMS)** a été initié pour apporter une réponse technologique adaptée à ces enjeux. L'objectif était de concevoir et de réaliser une application web sur mesure, légère, réactive et sécurisée, capable de centraliser la base d'inventaire, de modéliser la topologie physique des entrepôts et de suivre le cycle des expéditions de marchandises, tout en automatisant les processus grâce à l'écosystème des QR Codes.

Ce mémoire retrace l'ensemble des étapes de ce projet, depuis l'analyse des besoins logistiques initiaux et la rédaction du cahier des charges fonctionnel, jusqu'aux choix d'architecture technique (PHP Pur, MySQL, JS natif), à la modélisation UML du système, aux mécanismes de sécurité mis en œuvre (validation CSRF, requêtes préparées), et enfin aux scénarios réels d'exploitation par les équipes de terrain de LIPAES.

---
<!-- PAGE 2 : CHAPITRE 1 -->
### [PAGE 2]

#### CHAPITRE 1 : CONTEXTE OPÉRATIONNEL ET ENJEUX LOGISTIQUES DE LIPAES

##### 1.1 Contexte et opportunités de numérisation
L'entreprise LIPAES est spécialisée dans la logistique de stockage et la distribution de marchandises. Elle exploite plusieurs entrepôts de stockage divisés en sections dédiées aux produits électroniques, aux fournitures de bureau, aux matières premières et aux produits finis. La fluidité opérationnelle de cette structure repose entièrement sur la capacité des opérateurs à connaître, en temps réel, l'état exact de l'inventaire afin de coordonner les approvisionnements et les sorties de stock.

La décision de migrer vers un système d'information intégré a été catalysée par le constat que les méthodes de gestion basées sur des fiches papier et des fichiers Excel isolés constituaient des freins majeurs. La numérisation s'est présentée comme une opportunité d'automatiser les tâches à faible valeur ajoutée (comme la double saisie des bordereaux), permettant aux équipes de se concentrer sur l'optimisation des flux et le contrôle qualité des réceptions.

---
<!-- PAGE 3 : CHAPITRE 1 (CONTINUED) -->
### [PAGE 3]

##### 1.2 Analyse des processus de gestion manuelle actuels
L'étude préliminaire menée sur le terrain a permis de décortiquer les processus logistiques initiaux :

1.  **Réception des marchandises :** Les produits livrés par les fournisseurs étaient comptés manuellement et inscrits sur un registre papier. Les informations étaient ensuite saisies sur un poste informatique fixe en fin de poste. Ce décalage temporel créait une latence durant laquelle le stock réel était invisible pour le service des ventes.
2.  **Préparation de commande :** Lorsqu'un client commandait une liste de références, le préparateur de commande devait parcourir l'entrepôt pour chercher les produits sans indication précise de leur allée ou étagère de stockage. Ce manque de repérage causait des pertes de temps considérables.
3.  **Inventaire annuel :** La réalisation des inventaires nécessitait l'arrêt complet des activités d'expédition pour permettre le comptage manuel de chaque produit, générant un manque à gagner financier pour l'entreprise.

---
<!-- PAGE 4 : CHAPITRE 1 (CONTINUED) -->
### [PAGE 4]

##### 1.3 Enjeux de la traçabilité des stocks de l'entreprise
La mise en place d'une traçabilité totale au sein des entrepôts de LIPAES répond à des objectifs de rigueur et d'audit interne. Chaque fois qu'une pièce est déplacée, vendue, reçue ou déclarée endommagée, le système doit conserver la trace complète de l'historique de cette transaction. L'enjeu de cette traçabilité est de permettre de remonter à la source de n'importe quel dysfonctionnement ou écart d'inventaire constaté.

Le Tableau 1 ci-dessous synthétise la correspondance entre les défis historiques identifiés lors de la phase d'analyse et les réponses apportées par la conception du Stock Management System (SMS) :

###### Tableau 1 : Analyse comparative des défis logistiques et solutions de SMS
| Défi Manuel Historique | Impact pour LIPAES | Solution Technique (SMS) |
| :--- | :--- | :--- |
| Comptage et saisie manuels | Erreurs typographiques fréquentes, SKUs mal saisis. | Saisie guidée avec validation de types et lecture QR optique. |
| Localisation libre des produits | Recherches longues des articles dans les allées. | Cartographie physique détaillée (Zones, Allées, Racks, Bacs). |
| Mise à jour d'inventaire différée | Indisponibilité d'informations fiables durant la journée. | Mise à jour en temps réel à l'aide de triggers SQL MySQL. |
| Aucun historique des modifications | Impossibilité d'auditer l'origine d'un écart physique. | Enregistrement automatique de logs détaillés (table `stock_logs`). |

---
<!-- PAGE 5 : CHAPITRE 2 -->
### [PAGE 5]

#### CHAPITRE 2 : CAHIER DES CHARGES FONCTIONNEL AND BESOINS APPLICATIFS

##### 2.1 Gestion du catalogue et cycle de vie des produits
La première exigence fonctionnelle définie par LIPAES concerne la structuration de sa base d'articles. Le système doit stocker les caractéristiques essentielles des produits de manière centralisée et sécurisée. Chaque produit est caractérisé par un nom descriptif, une catégorie fonctionnelle et une référence SKU unique.

Le cycle de vie des produits est géré à travers quatre statuts logiques dont l'évolution doit être automatisée au sein de la base de données :
*   **Disponible (in_stock) :** Le produit dispose d'une quantité suffisante pour être intégré dans les expéditions commerciales.
*   **Alerte Stock Faible (low_stock) :** S'active automatiquement lorsque la quantité physique disponible descend en dessous du seuil de sécurité minimal paramétré pour ce produit (`min_stock_level`). Ce changement doit alerter visuellement le gestionnaire logistique.
*   **Rupture de Stock (out_of_stock) :** S'active automatiquement lorsque la quantité physique atteint zéro. Le produit ne peut plus faire l'objet de livraisons.
*   **Endommagé (damaged) :** Permet d'isoler manuellement les produits détériorés lors du stockage pour qu'ils soient exclus des ventes disponibles tout en restant répertoriés dans l'inventaire global.

---
<!-- PAGE 6 : CHAPITRE 2 (CONTINUED) -->
### [PAGE 6]

##### 2.2 Système d'emplacements hiérarchiques physiques (Multi-locations)
Un enjeu majeur de la transition logistique de LIPAES consiste à en finir avec la localisation floue des articles en entrepôt. Le système doit pouvoir modéliser la géographie des espaces de stockage sous forme d'une arborescence d'emplacements. Chaque emplacement physique est caractérisé par un code unique, un type d'entité et une relation d'appartenance hiérarchique.

Les emplacements peuvent être configurés selon plusieurs types hiérarchisés :
*   **Entrepôt (warehouse) :** Le conteneur racine représentant un bâtiment de stockage.
*   **Zone (zone) :** Une division thématique au sein d'un entrepôt (ex: Zone A pour l'électronique).
*   **Allée (aisle) :** Un couloir de circulation physique.
*   **Étagère (rack) :** Une structure métallique de rangement vertical.
*   **Niveau (shelf) :** Une tablette horizontale sur un rack.
*   **Bac / Case (bin) :** Le niveau le plus fin de stockage pour les petits articles.

Afin de répondre à la réalité opérationnelle de LIPAES, l'application gère le multi-stockage : un même produit peut être présent dans plusieurs emplacements avec des quantités différentes. Une liaison SQL `product_locations` stocke cette relation complexe et indique quel emplacement est configuré comme l'emplacement principal du produit.

###### Tableau 2 : Droits d'accès fonctionnels par profil utilisateur
| Fonctionnalité / Action | Profil Administrateur (Admin) | Profil Personnel (Staff) |
| :--- | :--- | :--- |
| Gestion des comptes | Accès complet | Aucun accès |
| Config catégories / emplacements | Accès complet | Lecture seule |
| Mise à jour rapide (Scan QR) | Autorisé | Autorisé |
| Création expéditions / livraisons | Autorisé | Lecture seule |
| Consultation logs d'audit | Accès complet | Aucun accès |

---
<!-- PAGE 7 : CHAPITRE 2 (CONTINUED) -->
### [PAGE 7]

##### 2.3 Système de gestion des livraisons et des clients
Le module de suivi logistique des sorties gère le flux de distribution de LIPAES. Ce processus englobe la gestion des clients, la génération des commandes d'expédition et le suivi logistique de livraison.

Chaque livraison est identifiée par un code unique auto-généré et suit des statuts de transition précis :
1.  **En attente (pending) :** La livraison est enregistrée, mais pas encore traitée en entrepôt.
2.  **Emballé (packed) :** Les articles ont été prélevés aux emplacements physiques et emballés dans le colis.
3.  **Expédié (dispatched) :** Le colis a été remis au livreur interne ou externe.
4.  **En transit (in_transit) :** La marchandise est en cours de transport vers le client.
5.  **Livré (delivered) :** Le client a signé le bon de réception. C'est cet état final qui déclenche la déduction définitive de stock.
6.  **Retourné (returned) :** Le colis a été renvoyé, et les quantités réintègrent le stock.
7.  **Annulé (cancelled) :** La livraison est annulée avant expédition.

Le stock de départ de la livraison est spécifié à l'aide d'un emplacement source afin que le système déduise la marchandise du bon espace d'entrepôt lors de la validation finale.

---
<!-- PAGE 8 : CHAPITRE 2 (CONTINUED) -->
### [PAGE 8]

##### 2.4 Intégration du système de QR Codes (Scanner et Génération)
Pour éliminer le traitement papier lors des mouvements physiques de stock, le système SMS intègre une solution de lecture et de génération de codes optiques.

Lorsqu'un produit est inséré dans le catalogue de LIPAES, une classe dédiée génère un code QR contenant l'identifiant et le SKU du produit. Cette étiquette vectorielle SVG peut être imprimée et collée sur les étagères ou les produits. Le scanner utilise la caméra d'un périphérique mobile via une interface HTML5 intégrée en JavaScript. Une fois le code détecté, l'opérateur accède directement au formulaire de modification de stock associé à la fiche du produit numérisé.

##### 2.5 Piste d'audit, traçabilité et historique d'activité
La sécurité et le contrôle d'inventaire de LIPAES reposent sur l'immuabilité de l'historique d'activité. La table `stock_logs` est mise à jour de manière transparente par l'application pour consigner chaque modification : l'utilisateur concerné, l'adresse IP de la machine, l'agent utilisateur, l'action effectuée, les anciennes/nouvelles quantités et les statuts logiques.

---
<!-- PAGE 9 : CHAPITRE 3 -->
### [PAGE 9]

#### CHAPITRE 3 : MODÉLISATION UML ET CONCEPTION DE LA SOLUTION

##### 3.1 Choix de modélisation orientée objet
La modélisation UML (Unified Modeling Language) permet de spécifier et documenter l'architecture logique du système SMS de LIPAES. Cette étape de conception garantit la bonne structuration des tables relationnelles MySQL et des modules PHP du projet.

La Figure 1 ci-dessous représente le diagramme des cas d'utilisation globale (UML Use Case), illustrant les frontières du système et les fonctionnalités exposées aux administrateurs et au personnel logistique.

###### Figure 1 : Diagramme de Cas d'Utilisation global (UML Use Case)
```
[ ACTEUR ADMINISTRATEUR ]
    ├── Gérer comptes utilisateurs
    ├── Gérer les catégories et emplacements physiques (CRUD)
    ├── Créer une commande d'expédition / livraison
    ├── Consulter l'historique d'audit global (IP, modifications)
    └── ( Hérite de toutes les actions du Personnel )

[ ACTEUR PERSONNEL / STAFF ]
    ├── Consulter le catalogue produit et état des stocks
    ├── Scanner un QR code pour accès rapide
    ├── Enregistrer un ajustement de stock (Entrée / Sortie)
    └── Mettre à jour le statut logistique d'une livraison assignée
```

---
<!-- PAGE 10 : CHAPITRE 3 (CONTINUED) -->
### [PAGE 10]

##### 3.2 Diagrammes UML (Classes, Séquence, États)
Le modèle structurel de données physiques est détaillé par le diagramme de classes (ERD) illustrant l'organisation relationnelle de la base de données. Il garantit la normalisation en troisième forme normale (3FN) pour éviter les redondances de données.

La Figure 2 illustre le dictionnaire et les liaisons physiques des tables de LIPAES :

###### Figure 2 : Modèle Entité-Relation et Schéma physique de la base de données
```
[admins] 1 ------- 0..* [stock_logs] (administrateur_id)
[admins] 1 ------- 0..* [deliveries] (created_by)
[categories] 1 --- 0..* [products] (category_id)
[products] 1 ----- 0..* [stock_logs] (product_id)
[products] 1 ----- 0..* [product_locations] (product_id)
[locations] 1 ---- 0..* [product_locations] (location_id)
[customers] 1 ---- 0..* [deliveries] (client_id)
[deliveries] 1 --- 1..* [delivery_items] (livraison_id)
[products] 1 ----- 0..* [delivery_items] (produit_id)
[deliveries] 1 --- 0..* [delivery_status_logs] (livraison_id)
```

---
<!-- PAGE 11 : CHAPITRE 3 (CONTINUED) -->
### [PAGE 11]

Pour illustrer la dynamique du système, la Figure 3 détaille le diagramme de séquence UML décrivant le flux d'informations lors du scan d'un QR code pour la mise à jour de stock :

###### Figure 3 : Diagramme de Séquence du processus de scan et d'ajustement rapide
```
Personnel            Scanner JS            API PHP             MySQL DB
   │                    │                    │                    │
   ├─── Vise le QR ────>│                    │                    │
   │                    ├─── GET (lookup) ──>│                    │
   │                    │                    ├─── SELECT ────────>│
   │                    │                    │<─── Produit ───────┤
   │                    │<─── JSON (Info) ───┤                    │
   │<── Formulaire ──────┤                    │                    │
   ├─── Saisie (Qty) ──>│                    │                    │
   │                    ├─── POST (update) ─>│                    │
   │                    │                    ├─── UPDATE Stock ──>│ (Trigger s'exécute)
   │                    │                    ├─── INSERT Logs ───>│
   │                    │<─── JSON (OK) ─────┤                    │
   │<── Notification ───┤                    │                    │
```

###### Tableau 3 : Dictionnaire des données de la table des utilisateurs (`admins`)
| Nom du Champ | Type SQL | Clé | Description |
| :--- | :--- | :--- | :--- |
| `id` | INT(11) | PK | Identifiant unique de l'utilisateur. |
| `username` | VARCHAR(50) | UK | Nom de connexion de l'utilisateur. |
| `email` | VARCHAR(100) | UK | Adresse e-mail unique de l'utilisateur. |
| `password_hash` | VARCHAR(255) | - | Hash du mot de passe (bcrypt). |
| `role` | ENUM('admin','staff')| - | Profil d'accès pour les droits applicatifs. |

###### Tableau 4 : Dictionnaire des données de la table des produits (`products`)
| Nom du Champ | Type SQL | Clé | Description |
| :--- | :--- | :--- | :--- |
| `id` | INT(11) | PK | Identifiant unique du produit. |
| `product_name` | VARCHAR(200) | - | Nom descriptif de l'article. |
| `sku` | VARCHAR(100) | UK | SKU d'identification unique LIPAES. |
| `category_id` | INT(11) | FK | Catégorie fonctionnelle associée. |
| `quantity` | INT(11) | - | Quantité totale disponible en stock. |
| `status` | ENUM('in_stock',...) | - | Statut automatique du niveau de stock. |

---
<!-- PAGE 12 : CHAPITRE 3 (CONTINUED) -->
### [PAGE 12]

###### Tableau 5 : Dictionnaire des données de la table des emplacements (`locations`)
| Nom du Champ | Type SQL | Clé | Description |
| :--- | :--- | :--- | :--- |
| `id` | INT(11) | PK | Identifiant unique de l'emplacement. |
| `location_code` | VARCHAR(100) | UK | Code d'identification logique de la place. |
| `name` | VARCHAR(200) | - | Nom descriptif ou numéro physique. |
| `type` | VARCHAR(100) | - | Type (aisle, rack, shelf, bin...). |
| `parent_id` | INT(11) | FK | Emplacement parent (structure en arbre). |

##### 3.3 Modélisation des déclencheurs automatiques (Triggers MySQL)
Afin de garantir la cohérence sémantique des statuts de stock lors de toute modification physique (entrée, sortie, commande), nous avons mis en œuvre deux triggers MySQL. Ces triggers effectuent des calculs comparatifs de quantité en base de données sans surcharger le code source PHP.

Le trigger d'insertion `set_initial_stock_status` et de mise à jour `update_stock_status` évaluent le statut selon la règle suivante :
*   Si *quantité = 0*, le statut devient `out_of_stock`.
*   Si *quantité <= min_stock_level*, le statut devient `low_stock`.
*   Si *quantité > min_stock_level* (et statut différent de `damaged`), le statut redevient `in_stock`.

---
<!-- PAGE 13 : CHAPITRE 4 -->
### [PAGE 13]

#### CHAPITRE 4 : ARCHITECTURE LOGICIELLE & SÉCURISATION APPLICATIVE

##### 4.1 Choix de la stack technique PHP Pur et MySQL
L'implémentation de la solution SMS repose sur une stack légère et optimisée. L'absence de frameworks permet un contrôle total de la sécurité et des performances réseau. Le couplage de PHP 7.4+ et MySQL s'effectue via l'interface PDO implémentée en Singleton.

L'utilisation de PHP Pur évite les surcharges mémoire induites par les dépendances de tiers. Le design system s'inspire des interfaces modernes (Linear/Stripe) pour une lisibilité maximale en milieu industriel.

---
<!-- PAGE 14 : CHAPITRE 4 (CONTINUED) -->
### [PAGE 14]

La Figure 5 détaille l'organisation de l'arborescence des fichiers sources du projet LIPAES :

###### Figure 5 : Représentation de l'arborescence des fichiers du projet
```
sms/
├── config/            # database.php (singleton)
├── auth/              # auth_check.php, csrf.php (jetons de session)
├── products/          # list.php, create.php, edit.php
├── locations/         # create.php, list.php
├── deliveries/        # create.php, list.php, view.php
├── qr/                # scan.php, generate.php
├── api/               # lookup_product.php, update_stock.php
├── exports/           # index.php (contrôleur d'extraction)
└── includes/          # header.php, sidebar.php, footer.php
```

##### 4.2 Sécurisation contre les failles SQL injection et XSS
Pour protéger les données sensibles de LIPAES, l'application utilise exclusivement des requêtes préparées avec typage des variables via PDO. Aucune donnée saisie par l'utilisateur n'est interprétée comme du code SQL. De plus, l'affichage des variables s'effectue sous protection systématique XSS via `htmlspecialchars()` en UTF-8.

---
<!-- PAGE 15 : CHAPITRE 4 (CONTINUED) -->
### [PAGE 15]

##### 4.3 Implémentation du jeton de session CSRF
Chaque formulaire effectuant une modification de stock ou d'emplacement intègre un jeton cryptographique généré de manière aléatoire en session. Ce jeton est validé côté serveur à chaque soumission POST (classe `CSRF` détaillée en Annexe). Si le jeton est manquant ou altéré, la transaction est instantanément rejetée, protégeant ainsi l'application contre les attaques CSRF.

---
<!-- PAGE 16 : CHAPITRE 4 (CONTINUED) -->
### [PAGE 16]

La protection est appliquée de manière systématique sur les actions suivantes :
*   Création d'un nouveau compte utilisateur (Admin).
*   Création et modification d'une fiche produit ou d'un emplacement physique.
*   Enregistrement d'un mouvement de stock (Entrée / Sortie).
*   Soumission et mise à jour d'un bon d'expédition (Livraison).

La validation est gérée centralement par l'importation de la classe de middleware de sécurité au sommet de chaque script PHP exécutant des requêtes HTTP POST.

---
<!-- PAGE 17 : CHAPITRE 5 -->
### [PAGE 17]

#### CHAPITRE 5 : DÉPLOIEMENT PHYSIQUE ET MANUEL D'UTILISATION

##### 5.1 Configuration de l'environnement XAMPP local
Le déploiement de l'application SMS dans l'environnement de LIPAES s'effectue à l'aide de XAMPP. Les fichiers sources du projet doivent être copiés dans `C:\xampp\htdocs\sms\`. La base de données relationnelle est importée via phpMyAdmin à partir des fichiers SQL d'initialisation (`database_schema.sql` et `schema_update.sql`). Enfin, les identifiants de connexion MySQL sont renseignés dans `config/database.php`.

---
<!-- PAGE 18 : CHAPITRE 5 (CONTINUED) -->
### [PAGE 18]

##### 5.2 Manuel de l'administrateur et du personnel de terrain

##### Actions de l'administrateur :
*   **Création d'emplacements :** Se rendre sur "Locations", configurer le nom et le code logique de l'emplacement (ex: WH1-ZoneA-Rack3).
*   **Enregistrement d'expédition :** Se rendre sur "Deliveries", sélectionner le client destinataire, l'emplacement physique de prélèvement de départ, ajouter les articles et valider.

##### Actions du préparateur de terrain (Staff) :
1.  Ouvrir le module "QR Scanner" de l'application sur smartphone.
2.  Autoriser la caméra, scanner le QR code d'un produit.
3.  Saisir la quantité du mouvement de stock et valider. Le système met à jour la base de données et consigne la transaction dans la piste d'audit.

---
<!-- PAGE 19 : CHAPITRE 5 (CONTINUED) -->
### [PAGE 19]

Le système de scan offre un guidage en temps réel. Si le produit est scanné avec succès, sa fiche technique complète, sa photo (si disponible), son emplacement actuel et son niveau de stock sont immédiatement restitués à l'écran, ce qui permet à l'opérateur de s'assurer visuellement de la conformité du produit physique qu'il tient dans ses mains.

---
<!-- PAGE 20 : CHAPITRE 5 (CONTINUED) -->
### [PAGE 20]

##### 5.3 Procédure de gestion des exports et rapports
Le module d'exportation permet d'extraire les données consolidées sous deux formats standards :
1.  **Format CSV :** Conçu pour l'import direct et l'analyse comptable sous Microsoft Excel.
2.  **Format HTML imprimable :** Une mise en page épurée et optimisée pour l'impression physique ou la sauvegarde en PDF des rapports d'inventaire.

La fonction d'exportation gère l'extraction sécurisée du catalogue produits, de l'état des stocks faibles et de l'historique complet des mouvements logistiques.

---
<!-- PAGE 21 : CONCLUSION -->
### [PAGE 21]

#### CONCLUSION GÉNÉRALE ET PERSPECTIVES DE PHASE II

La conception et la réalisation du projet **Stock Management System (SMS)** pour le compte de l'entreprise **LIPAES** ont permis de répondre avec rigueur et précision aux enjeux de numérisation et de traçabilité de ses entrepôts physiques. Grâce à la mise en œuvre d'un modèle de données robuste et d'outils modernes (QR codes vectoriels, scanner mobile HTML5, piste d'audit), LIPAES dispose aujourd'hui d'une solution de gestion moderne et performante.

La refonte graphique de type "Linear-inspired" offre une excellente ergonomie pour les opérateurs de terrain. En limitant les frameworks externes lourds, le système assure des temps de réponse optimaux. Ce projet pose les bases de la digitalisation de LIPAES et ouvre des perspectives d'évolution intéressantes pour l'intégration future d'outils de prévision de ventes ou de notification d'alertes automatisées.

---
<!-- PAGE 22 : BIBLIOGRAPHIE -->
### [PAGE 22]

#### BIBLIOGRAPHIE ET WEBOGRAPHIE

##### Ouvrages de référence
*   S. Melnik & J. Rumbaugh, *Modélisation et Conception UML des Systèmes d'Information*, Éditions Dunod, 2021.
*   C. Delannoy, *Programmer en PHP 8 - Modèles, Sécurité et Performances*, Éditions Eyrolles, 2022.
*   P. Alglave, *Logistique d'entrepôt : Gestion des flux physiques et numériques*, Éditions d'Organisation, 2020.

##### Ressources Web et Documentations Officielles
*   Documentation officielle PHP (Accès aux bases avec PDO) : `https://www.php.net/manual/fr/book.pdo.php`
*   Documentation officielle MySQL (Triggers et procédures stockées) : `https://dev.mysql.com/doc/refman/8.0/en/triggers.html`
*   Portail de la sécurité web OWASP (Recommandations contre injections SQL et XSS) : `https://owasp.org/www-project-top-ten/`

---
<!-- PAGE I : ANNEXES TABLE OF CONTENTS & START OF ANNEX A -->
### [PAGE I - ANNEXES]

#### TABLE DES MATIÈRES DES ANNEXES

*   **Annexe A :** Script SQL du Schéma Relationnel (`database_schema.sql`)
*   **Annexe B :** Script SQL de Migration et Extensions (`schema_update.sql`)
*   **Annexe C :** Fichier de Connexion Base de Données (`config/database.php`)
*   **Annexe D :** Classe de Sécurité CSRF (`auth/csrf.php`)
*   **Annexe E :** Contrôleur du Centre d'Exportation (`exports/index.php`)

---

#### ANNEXE A : SCRIPT SQL DU SCHÉMA RELATIONNEL (`database_schema.sql`)

```sql
-- Stock Management System (SMS) Database Schema
-- MySQL Database with UTF-8 support, indexes, and foreign keys

CREATE DATABASE IF NOT EXISTS `stock_management` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `stock_management`;

CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','staff') NOT NULL DEFAULT 'staff',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_username` (`username`),
  UNIQUE KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---
<!-- PAGE II : RÉSUMÉ & MOTS CLÉS (BACK COVER) -->
### [PAGE II - RÉSUMÉ ET MOTS CLÉS]

#### RÉSUMÉ

Ce mémoire présente la conception et la réalisation du système **SMS (Stock Management System)** pour l'entreprise **LIPAES**. Cette solution web sur mesure, développée en PHP Pur et MySQL, vise à numériser et à optimiser la gestion de stocks en remplaçant les processus d'inventaire papier traditionnels par un système centralisé en temps réel. Le système modélise précisément la topologie géographique hiérarchique des entrepôts (multi-emplacements), automatise les cycles de vie des produits à l'aide de triggers SQL, et suit rigoureusement les ordres d'expéditions et de livraisons clients. Enfin, l'intégration de QR Codes générés au format SVG et d'un scanner mobile natif permet une saisie fluide et sécurisée sur le terrain, tandis qu'une piste d'audit consignée dans la base de données assure une traçabilité totale et inaltérable des mouvements logistiques.

**Mots-clés :** Gestion de Stocks, Logistique, QR Code, Base de Données Relationnelle, Traçabilité, PHP Pur, LIPAES.

***

#### ABSTRACT

This project thesis details the design and implementation of the **SMS (Stock Management System)** developed for the company **LIPAES**. This custom web application, built with pure PHP and MySQL, aims to digitize and optimize inventory management by replacing legacy paper-based tracking methods with a centralized real-time system. The platform models the hierarchical physical topology of warehouses (multi-location stock tracking), automates product status lifecycles using database triggers, and monitors outgoing delivery flows to customers. The inclusion of dynamically generated SVG QR Codes and an integrated mobile HTML5 camera scanner enables fast and error-free on-site data entry. Finally, a secure database-level transaction log (audit trail) guarantees complete and unalterable traceability of all inventory movements.

**Keywords:** Stock Management, Logistics, QR Code, Relational Database, Traceability, Pure PHP, LIPAES.
