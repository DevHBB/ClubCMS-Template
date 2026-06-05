# ⚡ ClubCMS — Guide d'installation complet

> **Pour les nuls en informatique** — Suivez les étapes dans l'ordre, ça marche.

---

## 🎯 Ce que vous allez obtenir

Un site complet pour votre club avec :
- Page d'accueil avec les couleurs et le logo de votre club
- Inscription / connexion des membres (protégé anti-robots)
- Espace membre avec profil, licence et carte membre PDF
- Forum de discussion entre membres
- Boutique en ligne (paiement Stripe, PayPal ou à la remise)
- Galerie photos avec albums et sous-albums
- Planning / calendrier avec réservation de créneaux
- Administration complète (4 niveaux de droits)
- Emails automatiques (inscription, commande, forum...)

---

## 📋 Ce qu'il vous faut AVANT de commencer

### Chez votre hébergeur, vérifiez que vous avez :
- **PHP 8.2 ou supérieur**
- **MySQL** ou **MariaDB**
- **Apache** avec mod_rewrite activé

### Hébergeurs compatibles :
- o2switch (recommandé, français, ~7€/mois)
- OVH / Infomaniak / PlanetHoster
- LWS / Hostinger / Ionos

### Ce dont vous aurez besoin :
- Un nom de domaine (ex: www.monclub.fr)
- Un accès FTP (fourni par votre hébergeur)
- FileZilla (logiciel FTP gratuit : filezilla-project.org)
- Les identifiants de votre base de données (dans votre espace client)

---

## 🚀 Installation — Étape par étape

### ÉTAPE 1 — Créer la base de données

1. Connectez-vous à votre espace client hébergeur
2. Allez dans "Bases de données" ou "MySQL"
3. Cliquez "Créer une base de données"
4. Notez bien :
   - le nom de la base (ex: monclub_db)
   - le nom d'utilisateur (ex: monclub_user)
   - le mot de passe de la base
   - le serveur (souvent localhost)

---

### ÉTAPE 2 — Uploader les fichiers sur votre serveur

1. Décompressez clubcms-final.zip sur votre ordinateur
2. Ouvrez FileZilla
3. Connectez-vous à votre serveur FTP
4. Naviguez vers le dossier public_html (ou www, ou htdocs)
5. Glissez-déposez TOUT LE CONTENU du dossier clubcms/ dans ce dossier

ATTENTION : Uploadez le contenu du dossier, pas le dossier lui-même.
Votre index.php doit être directement dans public_html/.

---

### ÉTAPE 3 — Lancer l'assistant d'installation

1. Ouvrez votre navigateur
2. Tapez : https://www.votresite.fr/install.php
3. Suivez les 5 étapes :

ETAPE 1/5 — Base de données
Remplissez avec les infos de l'étape 1. Hôte : localhost dans 99% des cas.

ETAPE 2/5 — Votre club
Nom, sport, couleurs, polices, logo, photo de bannière.

ETAPE 3/5 — Compte administrateur
Votre email et mot de passe (8 caractères minimum). Ce compte a tous les droits.

ETAPE 4/5 — Emails et paiements (optionnel)
Vous pouvez passer cette étape et configurer ça plus tard.

ETAPE 5/5 — Finalisation
Cliquez "Lancer l'installation". C'est terminé !

---

### ÉTAPE 4 — IMPORTANT : Supprimer install.php

Après l'installation, supprimez le fichier install.php via FileZilla.
C'est une sécurité essentielle.

---

### ÉTAPE 5 — Vérifier que tout fonctionne

Allez sur https://www.votresite.fr → vous voyez la page d'accueil.
Connectez-vous sur https://www.votresite.fr/admin avec vos identifiants.

---

## 👥 Les 4 types d'utilisateurs

Super Administrateur : Tout (paramètres, paiements, modules, apparence)
Administrateur : Articles, galerie, boutique, modération forum
Coach : Gérer les créneaux, valider les licences, voir les membres
Membre : Son profil, forum, boutique, planning, galerie

Pour changer le rôle d'un membre : Admin > Membres > cliquez sur son rôle

---

## ⚙️ Activer / désactiver des modules

Admin > Modules

- Forum : discussions entre membres
- Boutique : vendre des articles
- Galerie : albums photos
- Planning : calendrier et réservations

Pour chaque module :
- Activer ou désactiver complètement
- Exiger une connexion pour y accéder

---

## 📅 Créer un créneau dans le planning

1. Admin > Planning > "+ Nouveau créneau"
2. Titre, date, heure de début et de fin
3. Type : Ouverture libre / Entraînement / Événement / Fermeture
4. Optionnel : activez "Inscription requise"
   - Formulaire interne : inscription directe sur le site
   - URL externe : redirige vers un Google Form
5. Cliquez Créer

---

## 🛒 Ajouter un produit en boutique

1. Admin > Boutique > Ajouter
2. Nom, description, prix, stock, photos
3. Variantes (taille, couleur...) si besoin
4. Publiez

Pour activer le paiement en ligne : Admin > Paramètres > Paiements

---

## 📸 Ajouter des photos

1. Admin > Galerie > Créer un dossier (ex: "Tournoi 2025")
2. Vous pouvez créer des sous-dossiers dans un dossier
3. Admin > Galerie > Uploader des photos
4. Sélectionnez le dossier et uploadez plusieurs photos d'un coup

---

## 📄 Valider les licences des membres

1. Admin > Licences
2. Voyez les licences "en attente"
3. Cliquez Valider ou Refuser

---

## 🪪 Carte membre PDF

Générée automatiquement quand un membre complète son profil.
- Format carte bancaire
- Couleurs et logo du club
- Signée numériquement (impossible à falsifier)
- Vérifiable par le staff sur /verifier-carte

---

## 📧 Emails automatiques

Inscription > le nouveau membre
Vérification email > le nouveau membre
Mot de passe oublié > le membre
Confirmation de commande > l'acheteur
Nouvelle réponse forum > l'auteur du topic
Nouvelle galerie publiée > tous les membres

---

## 🔧 Problèmes fréquents

Page blanche :
→ Vérifiez que PHP 8.2+ est activé chez votre hébergeur.
→ Vérifiez que config/config.php existe.

"Erreur de connexion à la base de données" :
→ Vérifiez les identifiants BDD (hôte, nom, utilisateur, mot de passe).
→ Sur certains hébergeurs, l'hôte n'est pas localhost mais une adresse spécifique.

Emails qui n'arrivent pas :
→ Admin > Paramètres > Emails : vérifiez les paramètres SMTP.
→ Regardez dans les spams.

Images qui ne s'affichent pas :
→ Le dossier assets/uploads/ doit avoir les droits 755.
→ Dans FileZilla : clic droit sur le dossier > Permissions > 755.

"404" sur toutes les pages :
→ Le fichier .htaccess n'est pas uploadé (fichier caché).
→ Dans FileZilla : menu Serveur > Afficher les fichiers cachés.
→ Vérifiez que mod_rewrite est activé chez votre hébergeur.

---

## 🔁 Déployer sur un autre club

Pour chaque nouveau club :
1. Nouveau nom de domaine
2. Nouvelle base de données
3. Uploadez les fichiers dans le nouveau dossier
4. Lancez install.php avec les infos du nouveau club
5. Chaque site est 100% indépendant

---

## 📁 Structure des fichiers

clubcms/
├── install.php      → Assistant d'installation (A SUPPRIMER après)
├── index.php        → Coeur du site (ne pas toucher)
├── .htaccess        → Créé automatiquement (ne pas supprimer)
├── config/          → Créé automatiquement (ne pas modifier)
├── core/            → Moteur du site (ne pas toucher)
├── modules/         → Fonctionnalités (forum, boutique, galerie...)
├── admin/           → Panneau d'administration
├── templates/       → Mise en page commune
└── assets/
    ├── css/         → Style du site
    ├── js/          → Animations
    └── uploads/     → Vos fichiers (logos, photos...)

---

ClubCMS v1.0 — Pour tous les clubs et associations sportives

---

## 🖥️ Configuration XAMPP (développement local Windows)

Si les pages d'articles donnent une erreur 404 sur XAMPP, c'est que `mod_rewrite` n'est pas activé ou que `AllowOverride` n'est pas configuré.

### Activer mod_rewrite sur XAMPP

1. Ouvrez **XAMPP Control Panel** → cliquez **Config** à côté d'Apache → **Apache (httpd.conf)**
2. Cherchez la ligne `#LoadModule rewrite_module` et retirez le `#` :
   ```
   LoadModule rewrite_module modules/mod_rewrite.so
   ```
3. Cherchez le bloc `<Directory "C:/xampp/htdocs">` et changez :
   ```
   AllowOverride None
   ```
   en :
   ```
   AllowOverride All
   ```
4. Sauvegardez et **redémarrez Apache** dans XAMPP Control Panel

### Vérifier que ça fonctionne

Allez sur `http://localhost/actualites` — vous devriez voir la liste des articles.
