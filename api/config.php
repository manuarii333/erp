<?php
/* ================================================================
   HCS ERP — api/config.php
   Configuration base de données MySQL Planet Hoster
   ⚠️  Ne jamais committer avec le vrai mot de passe en production
   ================================================================ */

define('DB_HOST', 'localhost');
define('DB_NAME', 'highftqb_HCS_ERP');
define('DB_USER', 'highftqb_ERP');
define('DB_PASS', 'HCS2026erp!');

/* Clé API partagée entre l'ERP front et ce backend PHP */
define('API_KEY', 'hcs-erp-2026');

/* Fuseau horaire Polynésie française (UTC-10) */
date_default_timezone_set('Pacific/Tahiti');
