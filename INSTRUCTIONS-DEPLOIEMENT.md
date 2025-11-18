# 🚀 INSTRUCTIONS COMPLÈTES - Déploiement Imprixo

Guide étape par étape pour mettre ton site e-commerce Imprixo en ligne.

---

## ✅ CE QUI EST DÉJÀ FAIT

- ✅ Base de données créée avec 10 tables
- ✅ 54 produits importés
- ✅ Admin dashboard fonctionnel
- ✅ Connexion admin OK

---

## 📋 CE QU'IL TE RESTE À FAIRE

### **ÉTAPE 1 : Importer la mise à jour BDD (Système fichiers)** ⏱️ 2 min

1. **Va dans phpMyAdmin**
2. **Sélectionne** `ispy2055_imprixo_ecommerce`
3. **Onglet "SQL"**
4. **Copie-colle** le contenu du fichier `database-update-fichiers.sql`
5. **Exécute**

✅ **Résultat** : 2 nouvelles tables créées (`fichiers_impression`, `fichiers_bat`)

---

### **ÉTAPE 2 : Upload TOUS les nouveaux fichiers** ⏱️ 10 min

Via FileZilla ou Gestionnaire de fichiers cPanel, upload :

```
✅ Déjà sur le serveur :
- /api/config.php
- /api/produits.php
- /api/panier.php
- /api/commandes.php
- /api/paiement.php
- /admin/* (toutes les pages)
- /scripts/import-produits.php
- database.sql

🆕 NOUVEAUX FICHIERS À UPLOADER :
- /api/upload-fichier.php
- /api/.htaccess
- /admin/commandes.php
- /admin/clients.php
- /mon-compte.php
- /suivi-commande.php
- /upload-fichier.html
- .htaccess (racine)
- /uploads/.htaccess
```

---

### **ÉTAPE 3 : Créer le dossier uploads** ⏱️ 1 min

Via FileZilla ou Gestionnaire fichiers cPanel :

```
Créer : /public_html/uploads/
Créer : /public_html/uploads/impressions/

Permissions (chmod) : 755
```

---

### **ÉTAPE 4 : Configurer `/api/config.php`** ⏱️ 5 min ⚠️ **CRITIQUE**

Édite ce fichier et remplace :

```php
// LIGNES 12-15 : Base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'ispy2055_imprixo_ecommerce');  // ⚠️ Ton préfixe
define('DB_USER', 'ispy2055_imprixo_user');       // ⚠️ Ton préfixe
define('DB_PASS', 'TON_MOT_DE_PASSE_MYSQL');      // ⚠️ Le vrai !

// LIGNES 22-24 : Stripe (mode TEST pour commencer)
define('STRIPE_PUBLIC_KEY', 'pk_test_XXXXXXXXXX');  // De ton compte Stripe
define('STRIPE_SECRET_KEY', 'sk_test_XXXXXXXXXX'); // De ton compte Stripe
define('STRIPE_WEBHOOK_SECRET', '');                // Laisser vide pour l'instant

// LIGNE 42 : URL
define('SITE_URL', 'https://imprixo.fr');  // ⚠️ Ton vrai domaine

// LIGNES 32-36 : Emails
define('EMAIL_FROM', 'contact@imprixo.fr');
define('EMAIL_FROM_NAME', 'Imprixo');
define('EMAIL_ADMIN', 'admin@imprixo.fr');
```

---

### **ÉTAPE 5 : Installer Stripe PHP SDK** ⏱️ 5 min

**Option A : Via Composer (si SSH disponible)**

```bash
cd /home/ispy2055/public_html
composer require stripe/stripe-php
```

**Option B : Téléchargement manuel**

1. **Télécharge** : https://github.com/stripe/stripe-php/releases/latest
2. **Extraire le ZIP**
3. **Upload via FTP dans** : `/public_html/vendor/stripe/stripe-php/`

Structure finale :
```
/public_html/vendor/stripe/stripe-php/init.php
/public_html/vendor/stripe/stripe-php/lib/...
```

---

### **ÉTAPE 6 : Créer compte Stripe** ⏱️ 5 min

1. **Inscription** : https://dashboard.stripe.com/register
2. **Mode TEST** (activé par défaut)
3. **Dashboard > Développeurs > Clés API**
4. **Copie** :
   - `pk_test_...` → Colle dans `config.php` ligne 22
   - `sk_test_...` → Colle dans `config.php` ligne 23

---

### **ÉTAPE 7 : Tester l'API** ⏱️ 1 min

Dans ton navigateur :

```
https://imprixo.fr/api/produits.php
```

**✅ Résultat attendu** : JSON avec tes 54 produits

**❌ Si erreur** : Vérifie `config.php` lignes 12-15

---

### **ÉTAPE 8 : Changer mot de passe admin** ⏱️ 3 min ⚠️ **SÉCURITÉ**

**Option A : Via generer-hash.php**

1. Upload `generer-hash.php` sur le serveur
2. Va sur `https://imprixo.fr/generer-hash.php`
3. Entre ton nouveau mot de passe (ex: `Imprixo2025!`)
4. Copie le hash généré
5. **phpMyAdmin** > table `admin_users` > Éditer ligne `admin`
6. Colle le hash dans `password_hash`
7. **SUPPRIME** `generer-hash.php` du serveur

**Option B : SQL direct**

```sql
UPDATE admin_users
SET password_hash = '$2y$10$NOUVEAU_HASH_ICI'
WHERE username = 'admin';
```

---

### **ÉTAPE 9 : Sécuriser les fichiers sensibles** ⏱️ 2 min

**Supprimer/désactiver du serveur :**

- ✅ `creer-admin.php` (supprimer)
- ✅ `generer-hash.php` (supprimer)
- ✅ `scripts/import-produits.php` (modifier ligne 9 : `$IMPORT_ENABLED = false;`)

---

### **ÉTAPE 10 : Tester upload fichier** ⏱️ 2 min

```
https://imprixo.fr/upload-fichier.html
```

1. **Upload** un fichier test (JPG ou PDF)
2. **Vérifier** qu'il apparaît dans `/uploads/impressions/`
3. **Vérifier** dans phpMyAdmin > table `fichiers_impression`

---

### **ÉTAPE 11 : Tester une commande TEST** ⏱️ 5 min

1. **Va sur** : `https://imprixo.fr`
2. **Ajoute** un produit au panier
3. **Passe** une commande
4. **Utilise** carte de test Stripe :
   ```
   Numéro : 4242 4242 4242 4242
   Date : 12/34
   CVC : 123
   ```
5. **Vérifie** :
   - Email de confirmation reçu
   - Commande visible dans `/admin/commandes.php`
   - Statut "payé"

---

## 🎯 ÉTAPES OPTIONNELLES (Recommandées)

### **A. Activer HTTPS** (Gratuit avec Let's Encrypt)

1. **cPanel** > "SSL/TLS"
2. **Installer** certificat (Let's Encrypt gratuit)
3. **Activer** "Force HTTPS Redirect"

### **B. Configurer Stripe Webhooks** (Pour production)

1. **Dashboard Stripe** > Développeurs > Webhooks
2. **Ajouter endpoint** : `https://imprixo.fr/api/webhook-stripe.php`
3. **Événements** :
   - `checkout.session.completed`
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
4. **Copier** clé de signature `whsec_...`
5. **Dans `config.php`** ligne 24 : Coller la clé

### **C. Restreindre accès Admin par IP** (Sécurité max)

Édite `/admin/.htaccess` lignes 8-16 :

```apache
<RequireAll>
    Require all denied
    Require ip 123.456.789.0  # TON IP
</RequireAll>
```

Trouve ton IP : https://whatismyipaddress.com/

### **D. Tester emails** (Important)

Si les emails ne partent pas :

1. **Option 1** : Configurer SMTP dans O2Switch
2. **Option 2** : Utiliser service externe (SendGrid, Mailgun)
3. **Contacter** support O2Switch pour config SPF/DKIM

---

## ✅ CHECKLIST FINALE AVANT PRODUCTION

- [ ] Base de données créée et mise à jour
- [ ] Tous les fichiers uploadés
- [ ] Dossier `/uploads/` créé avec bonnes permissions
- [ ] `/api/config.php` configuré (DB + Stripe + URLs)
- [ ] Stripe PHP SDK installé
- [ ] API produits fonctionne (test navigateur)
- [ ] Mot de passe admin changé
- [ ] Fichiers sensibles supprimés (creer-admin.php, etc.)
- [ ] Script import désactivé
- [ ] Upload fichier fonctionne (test)
- [ ] Commande test réussie avec Stripe
- [ ] HTTPS activé (recommandé)
- [ ] Emails fonctionnels

---

## 🆘 EN CAS DE PROBLÈME

### **Erreur "Connexion base de données"**
→ Vérifie `/api/config.php` lignes 12-15

### **API produits retourne erreur**
→ Vérifie que Stripe SDK est bien installé dans `/vendor/stripe/`

### **Upload fichier échoue**
→ Vérifie permissions dossier `/uploads/` (chmod 755)

### **Emails non reçus**
→ Vérifie spam, ou contacte support O2Switch

### **Page blanche**
→ Active affichage erreurs : `ini_set('display_errors', 1);` en haut de `config.php`

---

## 📞 SUPPORT

- **O2Switch** : support@o2switch.fr
- **Stripe** : https://support.stripe.com
- **Documentation Stripe** : https://stripe.com/docs/api

---

## 🎉 APRÈS INSTALLATION

Ton site sera 100% fonctionnel avec :

✅ **Frontend** : Site vitrine + configurateur
✅ **E-commerce** : Panier + paiement Stripe
✅ **Admin** : Dashboard complet
✅ **Upload** : Système fichiers impression
✅ **Emails** : Confirmations automatiques
✅ **Suivi** : Tracking commandes clients
✅ **Sécurité** : Protection .htaccess + validation

---

## 🚀 PASSER EN MODE PRODUCTION (Plus tard)

Quand tu es prêt pour de vraies commandes :

1. **Stripe** : Activer le compte (vérification identité)
2. **Dans `config.php`** : Remplacer clés TEST par clés LIVE
3. **Webhook** : Recréer avec clés LIVE
4. **Tests finaux** : Commander avec vraie carte
5. **Lancement** ! 🎉

---

**Tout est prêt ! Suis ces étapes dans l'ordre et ton site sera en ligne ! 💪**
