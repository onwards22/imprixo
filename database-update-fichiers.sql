-- ============================================
-- Mise à jour BDD : Ajout Système de Fichiers
-- Pour impression / Upload client
-- ============================================

-- Table des fichiers uploadés par les clients
CREATE TABLE IF NOT EXISTS fichiers_impression (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Relation commande/panier
    commande_id INT NULL,
    panier_id INT NULL,
    ligne_panier_id INT NULL,
    ligne_commande_id INT NULL,

    -- Informations fichier
    nom_original VARCHAR(255) NOT NULL,
    nom_stockage VARCHAR(255) UNIQUE NOT NULL,
    chemin_complet VARCHAR(500) NOT NULL,
    extension VARCHAR(10) NOT NULL,
    taille_octets BIGINT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,

    -- Métadonnées impression
    largeur_mm DECIMAL(10,2) NULL COMMENT 'Largeur en mm',
    hauteur_mm DECIMAL(10,2) NULL COMMENT 'Hauteur en mm',
    resolution_dpi INT NULL,
    espace_couleur VARCHAR(20) NULL COMMENT 'CMYK, RVB, etc',
    nombre_pages INT DEFAULT 1,

    -- Statut et validation
    statut ENUM('en_attente', 'valide', 'erreur', 'supprime') DEFAULT 'en_attente',
    erreur_validation TEXT NULL,
    valide_par_admin BOOLEAN DEFAULT FALSE,
    date_validation TIMESTAMP NULL,

    -- Sécurité
    hash_md5 VARCHAR(32) NOT NULL,
    hash_sha256 VARCHAR(64) NOT NULL,
    ip_upload VARCHAR(45) NOT NULL,

    -- Dates
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Index
    INDEX idx_commande (commande_id),
    INDEX idx_panier (panier_id),
    INDEX idx_statut (statut),
    INDEX idx_hash_md5 (hash_md5),

    -- Clés étrangères
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (panier_id) REFERENCES paniers(id) ON DELETE CASCADE,
    FOREIGN KEY (ligne_panier_id) REFERENCES lignes_panier(id) ON DELETE CASCADE,
    FOREIGN KEY (ligne_commande_id) REFERENCES lignes_commande(id) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table pour les BAT (Bon À Tirer) - fichiers de validation admin
CREATE TABLE IF NOT EXISTS fichiers_bat (
    id INT AUTO_INCREMENT PRIMARY KEY,

    commande_id INT NOT NULL,
    ligne_commande_id INT NULL,

    -- Informations fichier BAT
    nom_fichier VARCHAR(255) NOT NULL,
    chemin_fichier VARCHAR(500) NOT NULL,
    taille_octets BIGINT NOT NULL,

    -- Validation client
    envoye_client BOOLEAN DEFAULT FALSE,
    date_envoi TIMESTAMP NULL,
    valide_par_client BOOLEAN DEFAULT FALSE,
    date_validation_client TIMESTAMP NULL,
    commentaire_client TEXT NULL,

    -- Admin
    cree_par_admin_id INT NOT NULL,

    -- Dates
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Index
    INDEX idx_commande (commande_id),
    INDEX idx_validation (valide_par_client),

    -- Clés étrangères
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (ligne_commande_id) REFERENCES lignes_commande(id) ON DELETE CASCADE,
    FOREIGN KEY (cree_par_admin_id) REFERENCES admin_users(id)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ajouter colonne pour tracker les fichiers dans lignes_panier
ALTER TABLE lignes_panier
ADD COLUMN fichier_impression_id INT NULL AFTER prix_ligne_ttc,
ADD CONSTRAINT fk_ligne_panier_fichier
    FOREIGN KEY (fichier_impression_id)
    REFERENCES fichiers_impression(id)
    ON DELETE SET NULL;

-- Ajouter colonne pour tracker les fichiers dans lignes_commande
ALTER TABLE lignes_commande
ADD COLUMN fichier_impression_id INT NULL AFTER prix_ligne_ttc,
ADD CONSTRAINT fk_ligne_commande_fichier
    FOREIGN KEY (fichier_impression_id)
    REFERENCES fichiers_impression(id)
    ON DELETE SET NULL;

-- ============================================
-- NOTES D'UTILISATION
-- ============================================

-- 1. Les fichiers sont uploadés AVANT ou PENDANT l'ajout au panier
-- 2. Stockés dans /uploads/impressions/YYYY/MM/
-- 3. Nom unique : timestamp_random_original.ext
-- 4. Validation automatique : format, taille, résolution
-- 5. Admin peut uploader BAT pour validation client
-- 6. Client peut télécharger ses fichiers + BAT

-- Extensions acceptées :
-- - Images : jpg, jpeg, png, tif, tiff, psd, ai, eps
-- - PDF : pdf
-- - Vectoriel : svg, ai, eps, cdr
-- - Archive : zip (contenant fichiers valides)

-- Limites recommandées :
-- - Taille max : 100 MB par fichier
-- - Résolution min : 150 DPI
-- - Résolution recommandée : 300 DPI
-- - Formats couleur : CMYK pour impression, RVB accepté (conversion auto)
