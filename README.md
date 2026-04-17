# Projet-Symfony-Blog-DBZ

Application Symfony d'un blog Dragon Ball Z avec espace public, espace administrateur, moderation, categories/sous-categories et generation automatique de contenu DBZ.

## Fonctionnalites principales

- Interface 100% en francais
- Menu lateral deroulant pour la navigation (plus lisible sur toutes les pages)
- Articles classes par categories et sous-categories
- Page dediee Analyse de l'oeuvre (`/articles/analyse-de-l-oeuvre`) : uniquement les articles de la categorie reservee `Analyse` (non listes sur l accueil ni dans la grille Articles par categorie)
- Workflow de publication: un article reste invisible tant qu'il n'est pas publie par l'admin
- Commentaires avec moderation admin (`pending`, `approved`, `rejected`)
- Classement des commentaires par categorie > sous-categorie > article dans l'admin
- Likes sur les articles (utilisateur connecte)
- Dashboard administrateur (utilisateurs, articles, commentaires en attente, top articles)
- Reinitialisation de mot de passe par email
- Generation automatique d'articles DBZ (5 par execution) sans doublon de sujet
- Categorie reservee `Analyse` : articles visibles uniquement sur `/articles/analyse-de-l-oeuvre` ; commandes dediees pour creer la categorie et generer du contenu
- Durcissement securite admin (actions sensibles en POST + CSRF)

## Prerequis

- PHP 8.4+
- Composer
- SQLite (fichier `backend/var/data.db`) ou autre SGBD configure
- Docker Desktop (optionnel, pour Mailpit)
- Symfony CLI (optionnel)

## Installation

Depuis la racine du projet:

```bash
cd backend
composer install
```

## Base de donnees

Depuis `backend`:

```bash
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console app:seed-blog
```

Notes importantes:
- `app:seed-blog` cree les comptes de demo, les categories DBZ et des donnees initiales
- les articles auto sont crees en attente (`isApproved = false`) et doivent etre publies par un admin

## Lancer le projet

Option Symfony CLI:

```bash
symfony server:start
```

Option PHP natif:

```bash
php -S 127.0.0.1:8000 -t public
```

## Emails en local (Mailpit)

Dans `backend`:

```bash
docker compose up -d mailer
```

Acces Mailpit:
- interface web: http://localhost:8025
- SMTP local: `127.0.0.1:1025`

## Generation automatique DBZ

Commande de publication automatique:

```bash
php bin/console app:autopublish-dbz
```

Comportement actuel:
- genere jusqu'a 5 articles par execution
- evite les doublons de sujet
- conserve un contenu coherent avec l'univers Dragon Ball Z
- les articles restent en attente de validation admin

## Analyse de l oeuvre (categorie Analyse)

La categorie `Analyse` est reservee : ses articles n apparaissent pas sur l accueil ni dans la grille des categories, uniquement sur la page publique dediee.

Commandes (depuis `backend`) :

| Commande | Role |
|----------|------|
| `php bin/console app:ensure-analyse-category` | Cree la categorie `Analyse` si elle n existe pas |
| `php bin/console app:generate-analysis-articles` | Ajoute 5 nouveaux articles `Analyse` (publies, visibles sur la page dediee) |
| `php bin/console app:generate-analysis-articles 10` | Ajoute 10 articles |
| `php bin/console app:generate-analysis-articles --ensure` | Complete jusqu a 5 articles au total (comportement proche du seed) |

Au seed (`app:seed-blog`), des articles d analyse peuvent etre ajoutes jusqu a 5 au total dans cette categorie (avec `--ensure` logique interne).

## Moderation des commentaires (admin)

Route: `/admin/utilisateurs/commentaires`

- un commentaire utilisateur est cree en `pending`
- l'admin peut le `Valider` ou le `Rejeter`
- les actions sont disponibles uniquement sur les commentaires en attente
- les commentaires approuves sont visibles publiquement sur l'article
- le statut `approuve` n'est pas affiche publiquement dans le texte (si visible, il est valide)
- un utilisateur voit une alerte sur l'article tant que son commentaire est en attente de validation admin

## Regles de gestion des administrateurs

Dans `/admin/utilisateurs`, les regles suivantes sont appliquees:

- il doit toujours y avoir au moins 1 compte administrateur actif
- un admin peut promouvoir un compte utilisateur en administrateur
- maximum 3 comptes administrateurs au total
- la promotion est desactivee visuellement quand la limite est atteinte
- un badge `ADMIN` est affiche dans la liste utilisateurs
- un compteur `Admins: X / 3` est affiche en haut de page

## Routes utiles

Public:
- `/`
- `/articles`
- `/articles/analyse-de-l-oeuvre`
- `/articles/categorie/{id}`
- `/connexion`
- `/inscription`
- `/mot-de-passe/oublie`

Admin:
- `/admin/articles`
- `/admin/categories`
- `/admin/utilisateurs`
- `/admin/utilisateurs/commentaires`
- `/admin/dashboard`

## Comptes de demo

Admin:
- email: `admin@blog.local`
- mot de passe: `Admin123!`

Utilisateur:
- email: `fan@dbz.local`
- mot de passe: `Fan123!`

## Commandes rapides

Depuis `backend`:

```bash
# migrations
php bin/console doctrine:migrations:migrate --no-interaction

# donnees de depart
php bin/console app:seed-blog

# generation auto DBZ (5 articles)
php bin/console app:autopublish-dbz

# creer uniquement la categorie Analyse (si absente)
php bin/console app:ensure-analyse-category

# articles Analyse de l oeuvre (cree 5 nouveaux articles par defaut)
php bin/console app:generate-analysis-articles

# creer un nombre precis d articles Analyse (exemple: 10)
php bin/console app:generate-analysis-articles 10

# uniquement combler jusqu a 5 articles au total (comme au seed)
php bin/console app:generate-analysis-articles --ensure

# verification conteneur Symfony
php bin/console lint:container
```

## Variables d'environnement IA (optionnel)

Dans `backend/.env.local`:

```env
OPENAI_API_KEY=sk-...
OPENAI_IMAGE_MODEL=gpt-image-1
```

Si non renseignees, la generation d'illustration peut etre ignoree selon la configuration locale.

## Depannage rapide Docker/WSL

Si Docker affiche "WSL needs updating":

```cmd
wsl --update
wsl --shutdown
```

Puis redemarrer Docker Desktop.
