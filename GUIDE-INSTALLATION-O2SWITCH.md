# 🚀 Guide d'Installation - VisuPrint Pro sur O2Switch

Guide complet pour déployer votre site e-commerce VisuPrint Pro sur votre hébergement O2Switch.

---

## 📋 Prérequis

✅ Compte O2Switch actif
✅ Accès cPanel
✅ Nom de domaine pointé vers O2Switch
✅ Compte Stripe (pour les paiements)
✅ Client FTP (FileZilla recommandé) ou accès SSH

---

## 🎯 Étape 1: Préparer la Base de Données

### 1.1 Créer la base de données

1. **Connectez-vous à cPanel** (https://votre-domaine.fr:2083)

2. **Allez dans "Bases de données MySQL"**

3. **Créer une nouvelle base de données:**
   ```
   Nom: visuprint_ecommerce
   ```

4. **Créer un utilisateur MySQL:**
   ```
   Nom d'utilisateur: visuprint_user
   Mot de passe: [Générer un mot de passe fort]
   ```
   ⚠️ **NOTEZ CE MOT DE PASSE !**

5. **Associer l'utilisateur à la base:**
   - Sélectionner l'utilisateur
   - Sélectionner la base
   - Cocher "TOUS LES PRIVILÈGES"
   - Valider

### 1.2 Importer la structure de la base

1. **Allez dans "phpMyAdmin"** (dans cPanel)

2. **Sélectionnez votre base** `visuprint_ecommerce`

3. **Onglet "Importer"**

4. **Choisir le fichier** `database.sql`

5. **Cliquez sur "Exécuter"**

✅ **Résultat:** 10 tables créées + utilisateur admin

---

## 🎯 Étape 2: Uploader les Fichiers

### 2.1 Via FileZilla (Recommandé)

1. **Télécharger FileZilla:** https://filezilla-project.org/

2. **Connectez-vous à votre serveur:**
   ```
   Hôte: ftp.votre-domaine.fr
   Utilisateur: [votre_user_o2switch]
   Mot de passe: [votre_password_o2switch]
   Port: 21
   ```

3. **Naviguer vers:** `/public_html/`

4. **Uploader TOUS les fichiers du projet:**
   ```
   /api/
   /admin/
   /produit/
   /scripts/
   /uploads/
   /vendor/
   *.html
   *.css
   *.js
   .htaccess
   database.sql
   CATALOGUE_COMPLET_VISUPRINT.csv
   ```

### 2.2 Via Gestionnaire de Fichiers cPanel

1. Dans cPanel, **"Gestionnaire de fichiers"**
2. Aller dans `/public_html/`
3. Créer un ZIP de votre projet
4. Upload le ZIP
5. Extraire le ZIP

---

## 🎯 Étape 3: Configuration

### 3.1 Configurer la base de données

Éditez le fichier `/api/config.php`:

```php
// LIGNE 12-15 : Modifier avec vos identifiants
define('DB_HOST', 'localhost');
define('DB_NAME', 'visuprint_ecommerce');  // Nom exact de votre base
define('DB_USER', 'visuprint_user');       // Votre utilisateur MySQL
define('DB_PASS', 'VOTRE_MOT_DE_PASSE');   // Le mot de passe noté précédemment
```

### 3.2 Configurer Stripe

1. **Créer un compte Stripe:** https://dashboard.stripe.com/register

2. **Récupérer vos clés API:**
   - Aller dans **Développeurs > Clés API**
   - Mode Test (pour tester) ou Mode Live (production)

3. **Dans `/api/config.php` (lignes 22-25):**
   ```php
   // Mode TEST (pour tester)
   define('STRIPE_PUBLIC_KEY', 'pk_test_xxxxxxxxxxxxx');
   define('STRIPE_SECRET_KEY', 'sk_test_xxxxxxxxxxxxx');
   define('STRIPE_WEBHOOK_SECRET', 'whsec_xxxxxxxxxxxxx');

   // Mode LIVE (production - après tests)
   // define('STRIPE_PUBLIC_KEY', 'pk_live_xxxxxxxxxxxxx');
   // define('STRIPE_SECRET_KEY', 'sk_live_xxxxxxxxxxxxx');
   ```

### 3.3 Configurer les emails

Dans `/api/config.php` (lignes 32-36):

```php
define('EMAIL_FROM', 'contact@visuprintpro.fr');
define('EMAIL_FROM_NAME', 'VisuPrint Pro');
define('EMAIL_ADMIN', 'admin@visuprintpro.fr');
```

### 3.4 Configurer l'URL du site

Dans `/api/config.php` (ligne 42):

```php
define('SITE_URL', 'https://visuprintpro.fr');  // VOTRE domaine
```

---

## 🎯 Étape 4: Installer Stripe PHP SDK

### Option A: Via Composer (Recommandé)

Si vous avez accès SSH:

```bash
cd /home/votre_user/public_html
composer require stripe/stripe-php
```

### Option B: Téléchargement manuel

1. **Télécharger:** https://github.com/stripe/stripe-php/releases

2. **Extraire dans:** `/vendor/stripe/stripe-php/`

3. **Structure finale:**
   ```
   /vendor/stripe/stripe-php/init.php
   /vendor/stripe/stripe-php/lib/...
   ```

---

## 🎯 Étape 5: Importer les Produits

### 5.1 Exécuter le script d'import

1. **Dans votre navigateur, allez sur:**
   ```
   https://votre-domaine.fr/scripts/import-produits.php
   ```

2. **Le script va importer les 54 produits**

3. **Vérifier le résultat:**
   ```
   ✓ Importés : 54 produits
   📦 TOTAL EN BASE : 54
   ```

### 5.2 Désactiver le script (IMPORTANT!)

**Méthode 1:** Modifier le fichier

Éditez `/scripts/import-produits.php` ligne 9:
```php
$IMPORT_ENABLED = false;  // Passer à false
```

**Méthode 2:** Bloquer l'accès

Éditez `/scripts/.htaccess` ligne 7:
```apache
Require all denied  # Décommenter cette ligne
```

**Méthode 3:** Supprimer le dossier (recommandé)
```bash
rm -rf /public_html/scripts/
```

---

## 🎯 Étape 6: Accéder à l'Administration

### 6.1 Page de connexion

**URL:** https://votre-domaine.fr/admin/login.php

### 6.2 Identifiants par défaut

```
Utilisateur: admin
Mot de passe: Admin123!
```

### 6.3 ⚠️ CHANGER LE MOT DE PASSE IMMÉDIATEMENT

1. Connectez-vous à phpMyAdmin

2. Table `admin_users`

3. Trouver l'utilisateur `admin`

4. Générer un nouveau hash:
   ```php
   <?php
   echo password_hash('VotreNouveauMotDePasse', PASSWORD_BCRYPT);
   ?>
   ```

5. Remplacer le `password_hash` dans la table

---

## 🎯 Étape 7: Sécurité Avancée

### 7.1 Activer HTTPS

Dans cPanel:
1. **"SSL/TLS"**
2. **"Installer un certificat SSL"** (Let's Encrypt gratuit)
3. **Activer "Force HTTPS Redirect"**

Dans `/.htaccess`, décommenter lignes 36-38:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 7.2 Restreindre l'accès Admin par IP

Éditez `/admin/.htaccess` lignes 8-16:

```apache
<RequireAll>
    Require all denied
    Require ip 123.456.789.0  # VOTRE IP
</RequireAll>
```

**Pour trouver votre IP:** https://whatismyipaddress.com/

### 7.3 Double authentification Admin

```bash
# En SSH
htpasswd -c /home/votre_user/.htpasswd admin_secure
```

Dans `/admin/.htaccess`, décommenter lignes 22-25.

### 7.4 Renommer le dossier admin

```bash
mv /public_html/admin /public_html/gestion-xyz123
```

Nouvelle URL: `https://votre-domaine.fr/gestion-xyz123/`

---

## 🎯 Étape 8: Configuration Stripe Webhooks

### 8.1 Créer un webhook

1. **Dashboard Stripe > Développeurs > Webhooks**

2. **Ajouter un endpoint:**
   ```
   URL: https://votre-domaine.fr/api/webhook-stripe.php
   ```

3. **Sélectionner les événements:**
   - `checkout.session.completed`
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`

4. **Copier la clé de signature:**
   ```
   whsec_xxxxxxxxxxxxx
   ```

5. **Dans `/api/config.php`:**
   ```php
   define('STRIPE_WEBHOOK_SECRET', 'whsec_xxxxxxxxxxxxx');
   ```

---

## 🎯 Étape 9: Tests

### 9.1 Tester les pages

- ✅ Page d'accueil: https://votre-domaine.fr
- ✅ Page produit: https://votre-domaine.fr/produit/forex-3mm.html
- ✅ API Produits: https://votre-domaine.fr/api/produits.php
- ✅ Admin: https://votre-domaine.fr/admin/login.php

### 9.2 Tester une commande TEST

1. **Mode TEST Stripe:** Utiliser cartes de test
   ```
   Carte: 4242 4242 4242 4242
   Date: 12/34
   CVC: 123
   ```

2. **Créer une commande test**

3. **Vérifier:**
   - Email de confirmation reçu
   - Commande visible dans `/admin/`
   - Statut "payé" si paiement réussi

### 9.3 Vérifier les emails

Si les emails ne partent pas:

**Option 1:** Utiliser SMTP (recommandé)

Installer WP Mail SMTP ou équivalent PHP

**Option 2:** Configurer SPF/DKIM

Contacter O2Switch pour configuration

---

## 🎯 Étape 10: Passer en Production

### 10.1 Checklist finale

- [ ] Base de données créée et importée
- [ ] Tous les fichiers uploadés
- [ ] `/api/config.php` configuré (DB, Stripe, Emails)
- [ ] Stripe PHP SDK installé
- [ ] Produits importés (54)
- [ ] Script d'import désactivé
- [ ] Mot de passe admin changé
- [ ] HTTPS activé
- [ ] Admin sécurisé (IP + .htpasswd)
- [ ] Stripe webhooks configurés
- [ ] Emails fonctionnels
- [ ] Commande test réussie

### 10.2 Basculer Stripe en mode LIVE

1. **Dashboard Stripe:** Activer votre compte

2. **Dans `/api/config.php`:**
   ```php
   // Commenter les clés TEST
   // define('STRIPE_PUBLIC_KEY', 'pk_test_xxx');

   // Activer les clés LIVE
   define('STRIPE_PUBLIC_KEY', 'pk_live_xxxxxxxxxxxxx');
   define('STRIPE_SECRET_KEY', 'sk_live_xxxxxxxxxxxxx');
   ```

3. **Recréer le webhook** avec les clés LIVE

### 10.3 Désactiver le mode développement

Dans `/api/config.php` lignes 289-295:

```php
// Forcer production
error_reporting(0);
ini_set('display_errors', 0);
```

---

## 📊 Monitoring et Maintenance

### Logs à surveiller

```bash
# Logs Apache
tail -f /home/votre_user/logs/error_log

# Logs PHP
tail -f /home/votre_user/logs/php_errors.log

# Logs Admin
tail -f /home/votre_user/logs/admin_errors.log
```

### Sauvegardes régulières

1. **Base de données:** phpMyAdmin > Exporter (1x/jour)

2. **Fichiers:** Télécharger `/uploads/` régulièrement

3. **Automatiser:** Utiliser les sauvegardes O2Switch

---

## 🆘 Dépannage

### Erreur "Connexion base de données"

✅ Vérifier `/api/config.php` lignes 12-15
✅ Vérifier que l'utilisateur a les droits
✅ Tester connexion dans phpMyAdmin

### Emails non reçus

✅ Vérifier spam
✅ Configurer SPF/DKIM
✅ Utiliser SMTP au lieu de mail()
✅ Vérifier table `historique_emails`

### Paiement Stripe échoue

✅ Mode TEST actif ?
✅ Clés API correctes ?
✅ Stripe PHP SDK installé ?
✅ Vérifier logs Stripe Dashboard

### Admin inaccessible

✅ URL correcte ?
✅ .htaccess bloque IP ?
✅ Mot de passe correct ?
✅ Désactiver .htpasswd temporairement

---

## 📞 Support

### Documentation Stripe
https://stripe.com/docs/api

### Documentation O2Switch
https://faq.o2switch.fr/

### Support O2Switch
support@o2switch.fr

---

## ✅ Résumé des URLs Importantes

| Service | URL |
|---------|-----|
| Site principal | https://visuprintpro.fr |
| Admin | https://visuprintpro.fr/admin/ |
| API Produits | https://visuprintpro.fr/api/produits.php |
| cPanel | https://visuprintpro.fr:2083 |
| phpMyAdmin | https://visuprintpro.fr:2083/phpMyAdmin |

---

## 🎉 Félicitations !

Votre site e-commerce VisuPrint Pro est maintenant en ligne et opérationnel !

**Prochaines étapes:**
1. Ajouter du contenu (images produits, descriptions)
2. Configurer Google Analytics
3. Mettre en place le référencement SEO
4. Lancer vos premières campagnes marketing

**Bon succès ! 🚀**
