<?php
define('CLI_SCRIPT', true);
require(__DIR__.'/config.php');
require_once($CFG->libdir.'/clilib.php');

global $DB;

echo "=== Nettoyage complet H5P ===\n\n";

// 1. Vider le cache des assets
echo "1. Vidage du cache des assets...\n";
$DB->execute("TRUNCATE TABLE {h5p_libraries_cachedassets}");
echo "   ✓ Fait\n\n";

// 2. Supprimer les fichiers
echo "2. Suppression des fichiers cache...\n";
$dirs = [
    $CFG->dataroot . '/h5p',
    $CFG->dataroot . '/localcache/h5p',
    $CFG->dataroot . '/cache/cachestore_file/default_application/core_h5p'
];

foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        system("rm -rf $dir/*");
        echo "   ✓ $dir vidé\n";
    }
}

// 3. Purger les caches
echo "\n3. Purge des caches Moodle...\n";
purge_all_caches();
echo "   ✓ Fait\n\n";

echo "=== Terminé ! Rechargez votre page H5P ===\n";
