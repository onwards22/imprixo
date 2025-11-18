# ⚠️ CONFIGURATION IMPRIXO - À LIRE AVANT INSTALLATION

## 🔧 Configuration Base de Données

Après avoir créé ta base dans cPanel, tu auras probablement ces noms :

```
Nom complet de la base : ispy2055_imprixo_ecommerce
Nom complet utilisateur : ispy2055_imprixo_user
```

## 📝 Fichiers à modifier

### 1️⃣ `/api/config.php` - Lignes 12-15

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ispy2055_imprixo_ecommerce');  // ⚠️ Avec le préfixe !
define('DB_USER', 'ispy2055_imprixo_user');       // ⚠️ Avec le préfixe !
define('DB_PASS', 'TON_MOT_DE_PASSE_MYSQL');      // ⚠️ Le mot de passe noté !
```

### 2️⃣ `/api/config.php` - Ligne 42

```php
define('SITE_URL', 'https://imprixo.fr');  // ⚠️ Ton vrai domaine
```

### 3️⃣ `/api/config.php` - Lignes 32-36

```php
define('EMAIL_FROM', 'contact@imprixo.fr');
define('EMAIL_FROM_NAME', 'Imprixo');
define('EMAIL_ADMIN', 'admin@imprixo.fr');
```

### 4️⃣ `/api/.htaccess` - Ligne 15

```apache
Header set Access-Control-Allow-Origin "https://imprixo.fr"  // ⚠️ Ton domaine
```

### 5️⃣ `/api/.htaccess` - Ligne 38

```apache
RewriteCond %{HTTP_REFERER} !^https?://(www\.)?imprixo\.fr [NC]
```

## 🎯 Checklist Installation

- [ ] Base de données créée dans cPanel : `ispy2055_imprixo_ecommerce`
- [ ] Utilisateur MySQL créé : `ispy2055_imprixo_user`
- [ ] Utilisateur associé à la base (TOUS LES PRIVILÈGES)
- [ ] Mot de passe MySQL noté
- [ ] Fichier `database.sql` importé dans phpMyAdmin (sans erreur)
- [ ] `/api/config.php` modifié avec les bons noms
- [ ] Stripe PHP SDK installé dans `/vendor/stripe/`
- [ ] Tous les fichiers uploadés sur le serveur

## 🚀 Test de connexion

Après configuration, teste la connexion :

```
https://imprixo.fr/api/produits.php
```

Si tu vois `{"error":"Aucun produit trouvé"}` → **✅ CONNEXION OK !**

Si tu vois une erreur de connexion → Vérifie les identifiants dans `config.php`

## 📞 Préfixes O2Switch

O2Switch ajoute automatiquement ton username devant :
- Bases de données : `ispy2055_xxx`
- Utilisateurs MySQL : `ispy2055_xxx`

**⚠️ TOUJOURS utiliser les noms complets dans `config.php` !**
