# Dragon Ball Z Blog

Projet reconstruit depuis zero avec Symfony 8, Doctrine, Security et Bootstrap autour de l'univers Dragon Ball Z.

## Fonctionnalites implementees

- Visiteur: accueil, liste des articles, detail article, lecture des commentaires approuves.
- Utilisateur connecte: inscription, connexion, ajout de commentaire (statut pending).
- Utilisateur connecte: consultation et modification du profil.
- Administrateur:
  - CRUD articles
  - Liste des utilisateurs
  - Activation/desactivation compte utilisateur
  - Moderation des commentaires (approved/deleted)

## Modele de donnees

- `User` (email unique, password hash, roles, firstName, lastName, profilePicture, createdAt, updatedAt, isActive, activationToken)
- `Post` (title, content, publishedAt, picture, relation author et category)
- `Category` (name, description)
- `Comment` (content, createdAt, status, relation author et post)

## Installation

```bash
composer install
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console app:seed-blog
symfony server:start
```

## Compte admin par defaut

- Email: `admin@blog.local`
- Mot de passe: `Admin123!`

## Activation par email

- A l'inscription, un email est envoye avec un lien `/activation/{token}`.
- Le compte passe a `isActive=true` apres clic sur le lien.
- En local, le projet est configure sur `MAILER_DSN=smtp://127.0.0.1:1025`.
- Lance un serveur SMTP de dev (Mailpit/MailHog) sur le port `1025` pour recevoir les emails.

## Routes principales

- `/` accueil
- `/articles` liste des articles
- `/inscription` inscription
- `/connexion` connexion
- `/profil` gestion du profil utilisateur
- `/admin/articles` gestion articles
- `/admin/utilisateurs` gestion utilisateurs
- `/admin/utilisateurs/commentaires` moderation commentaires
