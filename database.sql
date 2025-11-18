-- ============================================
-- BASE DE DONNÉES E-COMMERCE IMPRIXO
-- À exécuter dans phpMyAdmin (O2Switch)
-- ============================================

-- ⚠️ IMPORTANT : Créer d'abord la base via cPanel > "Bases de données MySQL"
--    Nom de la base : imprixo_ecommerce (avec préfixe automatique)
--
-- Dans phpMyAdmin, sélectionner d'abord votre base dans le menu de gauche,
-- puis importer ce fichier SQL
--
-- Les lignes CREATE DATABASE et USE ont été supprimées car :
-- - Vous n'avez pas les droits de créer une base via phpMyAdmin
-- - La base doit être sélectionnée manuellement dans phpMyAdmin

-- ============================================
-- TABLE : produits
-- ============================================
CREATE TABLE produits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL COMMENT 'ID_PRODUIT du CSV',
    nom VARCHAR(255) NOT NULL,
    categorie VARCHAR(100),
    sous_titre VARCHAR(255),
    description_courte TEXT,
    description_longue TEXT,

    -- Caractéristiques techniques
    poids_m2 DECIMAL(10,2),
    epaisseur VARCHAR(20),
    format_max VARCHAR(50),
    `usage` VARCHAR(100),
    duree_vie VARCHAR(50),
    certification VARCHAR(50),
    finition VARCHAR(100),
    impression_faces VARCHAR(50),

    -- Prix
    prix_simple_face DECIMAL(10,2),
    prix_double_face DECIMAL(10,2),
    prix_0_10 DECIMAL(10,2),
    prix_11_50 DECIMAL(10,2),
    prix_51_100 DECIMAL(10,2),
    prix_101_300 DECIMAL(10,2),
    prix_300_plus DECIMAL(10,2),

    -- Gestion
    stock_disponible BOOLEAN DEFAULT TRUE,
    commande_min DECIMAL(10,2) DEFAULT 25,
    delai_jours INT DEFAULT 3,
    unite_vente VARCHAR(20) DEFAULT 'm²',

    -- SEO
    url_slug VARCHAR(255) UNIQUE,
    meta_title VARCHAR(255),
    meta_description TEXT,

    -- Dates
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_categorie (categorie),
    INDEX idx_code (code),
    INDEX idx_url_slug (url_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE : clients
-- ============================================
CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255),

    -- Infos personnelles
    prenom VARCHAR(100),
    nom VARCHAR(100),
    entreprise VARCHAR(255),
    telephone VARCHAR(20),

    -- Adresse de facturation
    adresse_facturation TEXT,
    code_postal_facturation VARCHAR(10),
    ville_facturation VARCHAR(100),
    pays_facturation VARCHAR(100) DEFAULT 'France',

    -- Adresse de livraison
    adresse_livraison TEXT,
    code_postal_livraison VARCHAR(10),
    ville_livraison VARCHAR(100),
    pays_livraison VARCHAR(100) DEFAULT 'France',

    -- Profil
    type_client ENUM('particulier', 'professionnel', 'entreprise') DEFAULT 'particulier',
    siret VARCHAR(20),
    tva_intracommunautaire VARCHAR(20),

    -- Newsletter
    newsletter BOOLEAN DEFAULT FALSE,

    -- Statistiques
    nombre_commandes INT DEFAULT 0,
    total_depense DECIMAL(10,2) DEFAULT 0,

    -- Dates
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,

    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE : paniers
-- ============================================
CREATE TABLE paniers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) UNIQUE NOT NULL COMMENT 'ID session PHP ou cookie',
    client_id INT NULL,

    -- Totaux
    sous_total DECIMAL(10,2) DEFAULT 0,
    frais_port DECIMAL(10,2) DEFAULT 0,
    reduction DECIMAL(10,2) DEFAULT 0,
    total_ht DECIMAL(10,2) DEFAULT 0,
    total_tva DECIMAL(10,2) DEFAULT 0,
    total_ttc DECIMAL(10,2) DEFAULT 0,

    -- Codes promo
    code_promo VARCHAR(50),

    -- Dates
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expire_at TIMESTAMP NULL COMMENT 'Expiration panier (30 jours)',

    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
    INDEX idx_session (session_id),
    INDEX idx_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE : lignes_panier
-- ============================================
CREATE TABLE lignes_panier (
    id INT AUTO_INCREMENT PRIMARY KEY,
    panier_id INT NOT NULL,
    produit_id INT NOT NULL,

    -- Configuration produit
    surface DECIMAL(10,3) NOT NULL COMMENT 'Surface en m²',
    quantite INT DEFAULT 1,
    largeur DECIMAL(10,2) COMMENT 'Largeur en cm',
    hauteur DECIMAL(10,2) COMMENT 'Hauteur en cm',

    -- Options
    impression VARCHAR(20) DEFAULT 'simple' COMMENT 'simple ou double',
    oeillets BOOLEAN DEFAULT FALSE,
    decoupe BOOLEAN DEFAULT FALSE,
    lamination BOOLEAN DEFAULT FALSE,

    -- Prix
    prix_unitaire_m2 DECIMAL(10,2),
    prix_options DECIMAL(10,2) DEFAULT 0,
    prix_ligne_ht DECIMAL(10,2),
    prix_ligne_ttc DECIMAL(10,2),

    -- Fichier
    fichier_nom VARCHAR(255),
    fichier_path VARCHAR(500),
    fichier_uploaded_at TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (panier_id) REFERENCES paniers(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE,
    INDEX idx_panier (panier_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE : commandes
-- ============================================
CREATE TABLE commandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_commande VARCHAR(50) UNIQUE NOT NULL,
    client_id INT NOT NULL,

    -- Infos client (snapshot au moment de la commande)
    client_email VARCHAR(255),
    client_nom VARCHAR(100),
    client_prenom VARCHAR(100),
    client_entreprise VARCHAR(255),
    client_telephone VARCHAR(20),

    -- Adresse facturation
    adresse_facturation TEXT,
    code_postal_facturation VARCHAR(10),
    ville_facturation VARCHAR(100),
    pays_facturation VARCHAR(100),

    -- Adresse livraison
    adresse_livraison TEXT,
    code_postal_livraison VARCHAR(10),
    ville_livraison VARCHAR(100),
    pays_livraison VARCHAR(100),

    -- Montants
    sous_total DECIMAL(10,2),
    frais_port DECIMAL(10,2) DEFAULT 0,
    reduction DECIMAL(10,2) DEFAULT 0,
    total_ht DECIMAL(10,2),
    total_tva DECIMAL(10,2),
    total_ttc DECIMAL(10,2),

    -- Paiement
    mode_paiement VARCHAR(50) DEFAULT 'stripe',
    statut_paiement ENUM('en_attente', 'paye', 'echoue', 'rembourse') DEFAULT 'en_attente',
    stripe_payment_intent_id VARCHAR(255),
    stripe_charge_id VARCHAR(255),
    date_paiement TIMESTAMP NULL,

    -- Statut commande
    statut ENUM('nouveau', 'confirme', 'en_production', 'expedie', 'livre', 'annule') DEFAULT 'nouveau',

    -- Livraison
    transporteur VARCHAR(100),
    numero_suivi VARCHAR(100),
    date_expedition TIMESTAMP NULL,
    date_livraison_estimee DATE,
    date_livraison_reelle TIMESTAMP NULL,

    -- Code promo
    code_promo VARCHAR(50),

    -- Notes
    notes_client TEXT,
    notes_admin TEXT,

    -- Dates
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
    INDEX idx_numero (numero_commande),
    INDEX idx_client (client_id),
    INDEX idx_statut (statut),
    INDEX idx_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE : lignes_commande
-- ============================================
CREATE TABLE lignes_commande (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    produit_id INT NOT NULL,

    -- Snapshot produit
    produit_code VARCHAR(50),
    produit_nom VARCHAR(255),

    -- Configuration
    surface DECIMAL(10,3),
    quantite INT,
    largeur DECIMAL(10,2),
    hauteur DECIMAL(10,2),

    -- Options
    impression VARCHAR(20),
    oeillets BOOLEAN DEFAULT FALSE,
    decoupe BOOLEAN DEFAULT FALSE,
    lamination BOOLEAN DEFAULT FALSE,

    -- Prix (snapshot au moment de la commande)
    prix_unitaire_m2 DECIMAL(10,2),
    prix_options DECIMAL(10,2),
    prix_ligne_ht DECIMAL(10,2),
    prix_ligne_ttc DECIMAL(10,2),

    -- Fichier
    fichier_nom VARCHAR(255),
    fichier_path VARCHAR(500),

    -- Production
    statut_production ENUM('en_attente', 'en_cours', 'termine') DEFAULT 'en_attente',

    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE RESTRICT,
    INDEX idx_commande (commande_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE : codes_promo
-- ============================================
CREATE TABLE codes_promo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    description VARCHAR(255),

    -- Type de réduction
    type_reduction ENUM('pourcentage', 'montant_fixe') DEFAULT 'pourcentage',
    valeur_reduction DECIMAL(10,2) NOT NULL,

    -- Conditions
    montant_minimum DECIMAL(10,2) DEFAULT 0,
    surface_minimum DECIMAL(10,2) DEFAULT 0,
    premiere_commande_seulement BOOLEAN DEFAULT FALSE,

    -- Limites
    nombre_utilisations_max INT NULL COMMENT 'NULL = illimité',
    nombre_utilisations_actuelles INT DEFAULT 0,
    utilisations_par_client INT DEFAULT 1,

    -- Validité
    date_debut DATE,
    date_fin DATE,
    actif BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_code (code),
    INDEX idx_actif (actif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE : historique_emails
-- ============================================
CREATE TABLE historique_emails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_email VARCHAR(50) NOT NULL,
    destinataire VARCHAR(255) NOT NULL,
    sujet VARCHAR(255),

    -- Lié à
    commande_id INT NULL,
    client_id INT NULL,

    -- Statut
    statut ENUM('envoye', 'echoue', 'en_attente') DEFAULT 'en_attente',
    erreur TEXT,

    -- Dates
    envoye_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE SET NULL,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
    INDEX idx_type (type_email),
    INDEX idx_commande (commande_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE : admin_users (utilisateurs admin)
-- ============================================
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,

    nom VARCHAR(100),
    prenom VARCHAR(100),

    role ENUM('admin', 'gestionnaire', 'lecture_seule') DEFAULT 'gestionnaire',
    actif BOOLEAN DEFAULT TRUE,

    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLE : logs_admin (journaux d'activité)
-- ============================================
CREATE TABLE logs_admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_user_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_date (created_at),
    INDEX idx_admin (admin_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- DONNÉES INITIALES
-- ============================================

-- Créer un utilisateur admin par défaut
-- Mot de passe: Admin123! (à changer impérativement !)
INSERT INTO admin_users (username, email, password_hash, nom, prenom, role) VALUES
('admin', 'admin@visuprintpro.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'VisuPrint', 'admin');

-- Codes promo d'exemple
INSERT INTO codes_promo (code, description, type_reduction, valeur_reduction, montant_minimum, date_debut, date_fin, actif) VALUES
('BIENVENUE10', 'Réduction 10% première commande', 'pourcentage', 10, 50, '2025-01-01', '2025-12-31', TRUE),
('NOEL2025', 'Offre spéciale Noël', 'pourcentage', 15, 100, '2025-12-01', '2025-12-31', TRUE),
('FEVRIER20', '20€ de réduction', 'montant_fixe', 20, 200, '2025-02-01', '2025-02-28', TRUE);

-- ============================================
-- VUES UTILES
-- ============================================

-- Vue: Statistiques commandes
CREATE VIEW vue_stats_commandes AS
SELECT
    DATE(created_at) as date,
    COUNT(*) as nb_commandes,
    SUM(total_ttc) as ca_ttc,
    SUM(total_ht) as ca_ht,
    AVG(total_ttc) as panier_moyen,
    SUM(CASE WHEN statut = 'nouveau' THEN 1 ELSE 0 END) as nb_nouveau,
    SUM(CASE WHEN statut = 'confirme' THEN 1 ELSE 0 END) as nb_confirme,
    SUM(CASE WHEN statut = 'en_production' THEN 1 ELSE 0 END) as nb_production,
    SUM(CASE WHEN statut = 'expedie' THEN 1 ELSE 0 END) as nb_expedie
FROM commandes
GROUP BY DATE(created_at);

-- Vue: Top produits
CREATE VIEW vue_top_produits AS
SELECT
    p.nom,
    p.code,
    COUNT(lc.id) as nb_ventes,
    SUM(lc.quantite) as quantite_totale,
    SUM(lc.prix_ligne_ttc) as ca_total
FROM produits p
LEFT JOIN lignes_commande lc ON p.id = lc.produit_id
GROUP BY p.id
ORDER BY nb_ventes DESC;

-- Vue: Top clients
CREATE VIEW vue_top_clients AS
SELECT
    c.id,
    c.email,
    CONCAT(c.prenom, ' ', c.nom) as nom_complet,
    c.entreprise,
    COUNT(co.id) as nb_commandes,
    SUM(co.total_ttc) as total_depense,
    AVG(co.total_ttc) as panier_moyen,
    MAX(co.created_at) as derniere_commande
FROM clients c
LEFT JOIN commandes co ON c.id = co.client_id
WHERE co.statut_paiement = 'paye'
GROUP BY c.id
ORDER BY total_depense DESC;

-- ============================================
-- INDEX DE PERFORMANCE
-- ============================================

-- Index pour recherche rapide
CREATE INDEX idx_produits_recherche ON produits(nom, code, categorie);
CREATE INDEX idx_commandes_dates ON commandes(created_at, statut, statut_paiement);
CREATE INDEX idx_clients_stats ON clients(nombre_commandes, total_depense);

-- ============================================
-- TRIGGERS
-- ============================================

-- Trigger: Mettre à jour le total du panier
DELIMITER //
CREATE TRIGGER update_panier_total AFTER INSERT ON lignes_panier
FOR EACH ROW
BEGIN
    UPDATE paniers
    SET
        sous_total = (SELECT SUM(prix_ligne_ht) FROM lignes_panier WHERE panier_id = NEW.panier_id),
        total_ht = (SELECT SUM(prix_ligne_ht) FROM lignes_panier WHERE panier_id = NEW.panier_id),
        total_tva = (SELECT SUM(prix_ligne_ht) * 0.20 FROM lignes_panier WHERE panier_id = NEW.panier_id),
        total_ttc = (SELECT SUM(prix_ligne_ttc) FROM lignes_panier WHERE panier_id = NEW.panier_id)
    WHERE id = NEW.panier_id;
END//
DELIMITER ;

-- Trigger: Mettre à jour les stats client
DELIMITER //
CREATE TRIGGER update_client_stats AFTER INSERT ON commandes
FOR EACH ROW
BEGIN
    UPDATE clients
    SET
        nombre_commandes = nombre_commandes + 1,
        total_depense = total_depense + NEW.total_ttc
    WHERE id = NEW.client_id;
END//
DELIMITER ;

-- ============================================
-- FIN DU SCRIPT
-- ============================================

-- Afficher un message de succès
SELECT 'Base de données créée avec succès ! 🎉' as message;
SELECT 'Utilisateur admin créé : admin / Admin123!' as info;
SELECT 'IMPORTANT : Changez le mot de passe admin !' as warning;
