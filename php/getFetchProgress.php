<?php

session_start();

require_once __DIR__ . '/includes/KMLFile.php';
require_once __DIR__ . '/includes/KMLFiles.php';

echo '0';
return;// Should rewrite this without sessions, we're making concurrent calls here.

try
{
    $kmlfiles = new KMLFiles();

    $start_date = mktime(0, 0, 0, date('m'), 1, date('Y') - 1); // last year
    $end_date = mktime(0, 0, 0, date('m'), 1, date('Y')); // today

    if (empty($_SESSION['start_date']) || empty($_SESSION['end_date']))
    {
        echo '0';
        return;
    }

    echo $kmlfiles->getFetchProgress($_SESSION['start_date'], $_SESSION['end_date']);
    return;
}
catch (Exception $e)
{
    echo '0';
    return;
}
