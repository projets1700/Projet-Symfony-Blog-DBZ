# Projet-Symfony-Blog-DBZ

Application Symfony de blog sur l'univers Dragon Ball Z.

## Fonctionnalites principales

- Likes d'articles (utilisateur connecte)
- Recherche, filtres, tri et pagination des articles
- Reinitialisation du mot de passe par email
- Dashboard administrateur avec indicateurs
- Auto-publication d'articles DBZ (generation locale)
- Workflow de moderation: un article n'est visible qu'apres clic admin sur `Publier`
- Durcissement securite admin (actions sensibles en POST + CSRF)

## Prerequis

- PHP 8.4+
- Composer
- Docker Desktop (optionnel, pour PostgreSQL/Mailpit)
- Symfony CLI (optionnel)

## Installation

Depuis la racine du projet :

```bash
cd backend
composer install
```

## Base de donnees

```bash
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console app:seed-blog
```

Optionnel (publier un article automatiquement) :

```bash
php bin/console app:autopublish-dbz
```

Important:
- Les articles auto sont crees en `en attente` (`isApproved = false`)
- Ils ne sont pas visibles cote visiteur/utilisateur tant que l'admin ne clique pas sur `Publier`

## Lancer le projet

Option Symfony CLI :

```bash
symfony server:start
```

Option PHP natif :

```bash
php -S 127.0.0.1:8000 -t public
```

## Emails en local (Mailpit)

Dans `backend`, lancer le service mail :

```bash
docker compose up -d mailer
```

Interface web Mailpit :

- http://localhost:8025

SMTP local :

- `127.0.0.1:1025`

## Routes utiles

- Front:
  - `/`
  - `/articles`
  - `/connexion`
  - `/inscription`
  - `/mot-de-passe/oublie`
- Admin:
  - `/admin`
  - `/admin/articles`
  - `/admin/utilisateurs`
  - `/admin/utilisateurs/commentaires`

## Comptes de demo

- Admin
  - Email: `admin@blog.local`
  - Mot de passe: `Admin123!`
- Utilisateur
  - Email: `fan@dbz.local`
  - Mot de passe: `Fan123!`

## Commandes rapides

Depuis `backend` :

```bash
# lancer les migrations
php bin/console doctrine:migrations:migrate --no-interaction

# injecter les donnees initiales
php bin/console app:seed-blog

# publier un article auto DBZ
php bin/console app:autopublish-dbz

# verifier le container Symfony
php bin/console lint:container
```

## Illustrations automatiques des articles auto

La generation d'illustration par IA est optionnelle et desactivee sans cle API.

Dans `backend/.env.local`:

```env
OPENAI_API_KEY=sk-...
OPENAI_IMAGE_MODEL=gpt-image-1
```

Ensuite, les commandes auto (`app:seed-blog`, `app:autopublish-dbz`) peuvent associer une illustration au sujet de l'article. Sans cle, l'article est cree sans image.

## Depannage rapide Docker/WSL

Si Docker indique "WSL needs updating" :

```cmd
wsl --update
wsl --shutdown
```

Puis redemarrer Docker Desktop.
