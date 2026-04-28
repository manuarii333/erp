<?php
/* ================================================================
   HCS ERP — api/migrate_schema.php
   Migration du schéma MySQL : ajout des colonnes JSON manquantes
   et de la colonne store_id pour la sync localStorage ↔ MySQL.

   Usage : appeler une seule fois via le navigateur :
     https://highcoffeeshirts.com/erp/api/migrate_schema.php?key=hcs-erp-2026
   ================================================================ */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

/* Vérification clé */
$key = $_GET['key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($key !== API_KEY) {
    http_response_code(401);
    echo json_encode(['error' => 'Clé API invalide']);
    exit;
}

$db  = Database::getInstance();
$pdo = $db->getPdo();
$log = [];

/* ----------------------------------------------------------------
   Colonnes JSON à ajouter / vérifier dans chaque table
   Format : [ table => [ col => type ], ... ]
   ---------------------------------------------------------------- */
$migrations = [
    'devis' => [
        'ref'            => 'VARCHAR(50) NULL',
        'lignes'         => 'LONGTEXT NULL',
        'paiements'      => 'LONGTEXT NULL',
        'paiements_devis'=> 'LONGTEXT NULL',
        'statut'         => 'VARCHAR(50) NOT NULL DEFAULT "Brouillon"',
        'client_nom'     => 'VARCHAR(255) NULL',
        'client_id'      => 'VARCHAR(100) NULL',
        'date'           => 'DATE NULL',
        'date_expiration'=> 'DATE NULL',
        'total_ht'       => 'DECIMAL(12,2) NULL DEFAULT 0',
        'total_tva'      => 'DECIMAL(12,2) NULL DEFAULT 0',
        'total_ttc'      => 'DECIMAL(12,2) NULL DEFAULT 0',
        'notes'          => 'TEXT NULL',
        'store_id'       => 'VARCHAR(100) NULL',
    ],
    'commandes' => [
        'lignes'         => 'LONGTEXT NULL',
        'statut'         => 'VARCHAR(50) NOT NULL DEFAULT "Brouillon"',
        'client_nom'     => 'VARCHAR(255) NULL',
        'total_ht'       => 'DECIMAL(12,2) NULL DEFAULT 0',
        'total_tva'      => 'DECIMAL(12,2) NULL DEFAULT 0',
        'total_ttc'      => 'DECIMAL(12,2) NULL DEFAULT 0',
        'notes'          => 'TEXT NULL',
        'quote_id'       => 'VARCHAR(100) NULL',
        'archived_at'    => 'DATETIME NULL',
        'store_id'       => 'VARCHAR(100) NULL',
    ],
    'factures' => [
        'ref'            => 'VARCHAR(50) NULL',
        'lignes'         => 'LONGTEXT NULL',
        'paiements'      => 'LONGTEXT NULL',
        'statut'         => 'VARCHAR(50) NOT NULL DEFAULT "Brouillon"',
        'client_nom'     => 'VARCHAR(255) NULL',
        'client_id'      => 'VARCHAR(100) NULL',
        'date'           => 'DATE NULL',
        'date_echeance'  => 'DATE NULL',
        'total_ht'       => 'DECIMAL(12,2) NULL DEFAULT 0',
        'total_tva'      => 'DECIMAL(12,2) NULL DEFAULT 0',
        'total_ttc'      => 'DECIMAL(12,2) NULL DEFAULT 0',
        'notes'          => 'TEXT NULL',
        'commande_id'    => 'VARCHAR(100) NULL',
        'devis_id'       => 'VARCHAR(100) NULL',
        'store_id'       => 'VARCHAR(100) NULL',
    ],
    'produits' => [
        'store_id'       => 'VARCHAR(100) NULL',
        'nom'            => 'VARCHAR(255) NULL',
        'ref'            => 'VARCHAR(100) NULL',
        'sku'            => 'VARCHAR(100) NULL',
        'emoji'          => 'VARCHAR(20) NULL',
        'image'          => 'LONGTEXT NULL',
        'categorie'      => 'VARCHAR(100) NULL',
        'fournisseur'    => 'VARCHAR(255) NULL',
        'unite'          => 'VARCHAR(50) NULL',
        'prix'           => 'DECIMAL(12,2) NULL DEFAULT 0',
        'cout'           => 'DECIMAL(12,2) NULL DEFAULT 0',
        'stock'          => 'DECIMAL(10,2) NULL DEFAULT 0',
        'stock_min'      => 'DECIMAL(10,2) NULL DEFAULT 0',
        'tva'            => 'INT NULL DEFAULT 0',
        'status'         => 'VARCHAR(50) NULL DEFAULT "active"',
        'description'    => 'TEXT NULL',
        'tailles'        => 'VARCHAR(500) NULL',
        'couleurs'       => 'VARCHAR(500) NULL',
        'coupe'          => 'VARCHAR(255) NULL',
        'variantes'      => 'LONGTEXT NULL',
        'paliers'        => 'LONGTEXT NULL',
        'attr_prix'      => 'VARCHAR(100) NULL',
        'attr_increments'=> 'LONGTEXT NULL',
        'formats_prix'   => 'LONGTEXT NULL',
        'custom_attrs'   => 'LONGTEXT NULL',
    ],
    'bons_achat' => [
        'store_id'           => 'VARCHAR(100) NULL',
        'ref'                => 'VARCHAR(50) NULL',
        'fournisseur'        => 'VARCHAR(255) NULL',
        'fournisseur_id'     => 'VARCHAR(100) NULL',
        'date'               => 'DATE NULL',
        'date_livraison_prevue' => 'DATE NULL',
        'statut'             => 'VARCHAR(50) NOT NULL DEFAULT "Brouillon"',
        'lignes'             => 'LONGTEXT NULL',
        'notes'              => 'TEXT NULL',
        'devis_origine_id'   => 'VARCHAR(100) NULL',
        'devis_origine_ref'  => 'VARCHAR(50) NULL',
        'total_ht'           => 'DECIMAL(12,2) NULL DEFAULT 0',
        'total_ttc'          => 'DECIMAL(12,2) NULL DEFAULT 0',
    ],
    'fournisseurs' => [
        'store_id'       => 'VARCHAR(100) NULL',
        'nom'            => 'VARCHAR(255) NULL',
        'contact'        => 'VARCHAR(255) NULL',
        'email'          => 'VARCHAR(255) NULL',
        'telephone'      => 'VARCHAR(50) NULL',
        'adresse'        => 'TEXT NULL',
        'notes'          => 'TEXT NULL',
    ],
    'contacts' => [
        'store_id'       => 'VARCHAR(100) NULL',
        'nom'            => 'VARCHAR(255) NULL',
        'prenom'         => 'VARCHAR(255) NULL',
        'email'          => 'VARCHAR(255) NULL',
        'telephone'      => 'VARCHAR(50) NULL',
        'entreprise'     => 'VARCHAR(255) NULL',
        'adresse'        => 'TEXT NULL',
        'type'           => 'VARCHAR(50) NULL',
        'statut'         => 'VARCHAR(50) NULL',
        'notes'          => 'TEXT NULL',
        'tags'           => 'LONGTEXT NULL',
    ],
    'commandes_atelier' => [
        'lignes'         => 'LONGTEXT NULL',
        'statut'         => 'VARCHAR(50) NOT NULL DEFAULT "En attente"',
        'store_id'       => 'VARCHAR(100) NULL',
        'commande_id'    => 'VARCHAR(100) NULL',
        'cmd_ref'        => 'VARCHAR(100) NULL',
        'poste'          => 'VARCHAR(100) NULL',
        'operateur'      => 'VARCHAR(255) NULL',
        'priorite'       => 'VARCHAR(50) NULL',
        'progression'    => 'INT NULL DEFAULT 0',
    ],
    'planning_atelier' => [
        'lignes'         => 'LONGTEXT NULL',
        'statut'         => 'VARCHAR(50) NOT NULL DEFAULT "Planifié"',
        'store_id'       => 'VARCHAR(100) NULL',
        'commande_id'    => 'VARCHAR(100) NULL',
        'cmd_ref'        => 'VARCHAR(100) NULL',
    ],
];

/* Récupérer les tables existantes */
$tablesStmt = $pdo->query("SHOW TABLES");
$tablesExistantes = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($migrations as $table => $colonnes) {
    if (!in_array($table, $tablesExistantes)) {
        $log[] = "⚠ Table `{$table}` inexistante — création...";
        /* Créer la table avec un schéma minimal */
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `{$table}` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $log[] = "✅ Table `{$table}` créée.";
        } catch (Exception $e) {
            $log[] = "❌ Erreur création `{$table}`: " . $e->getMessage();
            continue;
        }
    }

    /* Récupérer les colonnes existantes */
    $colStmt = $pdo->query("DESCRIBE `{$table}`");
    $colsExistantes = array_column($colStmt->fetchAll(PDO::FETCH_ASSOC), 'Field');

    foreach ($colonnes as $col => $type) {
        if (in_array($col, $colsExistantes)) {
            $log[] = "ℹ `{$table}`.`{$col}` existe déjà.";
            continue;
        }
        try {
            $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$col}` {$type}");
            $log[] = "✅ Ajout `{$table}`.`{$col}` ({$type})";
        } catch (Exception $e) {
            $log[] = "❌ `{$table}`.`{$col}`: " . $e->getMessage();
        }
    }
}

/* ----------------------------------------------------------------
   Réparer AUTO_INCREMENT sur les colonnes id des tables principales
   (au cas où la table existait avant sans AUTO_INCREMENT)
   ---------------------------------------------------------------- */
$tablesToFix = ['devis', 'commandes', 'factures', 'produits', 'contacts', 'fournisseurs',
                'bons_achat', 'commandes_atelier', 'planning_atelier'];

foreach ($tablesToFix as $table) {
    try {
        $stmt = $pdo->prepare(
            "SELECT Extra FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = 'id'"
        );
        $stmt->execute([$table]);
        $colInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$colInfo) continue;

        if (strpos(strtolower($colInfo['Extra'] ?? ''), 'auto_increment') === false) {
            $pdo->exec("ALTER TABLE `{$table}` MODIFY `id` INT NOT NULL AUTO_INCREMENT");
            $log[] = "✅ `{$table}`.`id` — AUTO_INCREMENT ajouté";
        } else {
            $log[] = "ℹ `{$table}`.`id` — AUTO_INCREMENT OK";
        }
    } catch (Exception $e) {
        $log[] = "❌ Fix AUTO_INCREMENT `{$table}`: " . $e->getMessage();
    }
}

echo json_encode([
    'ok'  => true,
    'msg' => 'Migration terminée',
    'log' => $log,
]);
