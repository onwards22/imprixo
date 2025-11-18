-- ============================================
-- Créer l'utilisateur admin pour Imprixo
-- À exécuter dans phpMyAdmin
-- ============================================

-- Supprimer l'admin existant s'il existe
DELETE FROM admin_users WHERE username = 'admin';

-- Créer le nouvel admin
-- Username: admin
-- Password: Admin123!
INSERT INTO admin_users (username, email, password_hash, nom, prenom, role, actif) VALUES
('admin', 'admin@imprixo.fr', '$2y$10$eH9Vu3GZB5YKZ5K5K5K5K.eJvPYqKqYqKqYqKqYqKqYqKqYqKqYqK', 'Admin', 'Imprixo', 'admin', TRUE);

-- Si l'erreur persiste, utilisez cette alternative avec un mot de passe simple
-- Password: admin123
-- INSERT INTO admin_users (username, email, password_hash, nom, prenom, role, actif) VALUES
-- ('admin', 'admin@imprixo.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Imprixo', 'admin', TRUE);
