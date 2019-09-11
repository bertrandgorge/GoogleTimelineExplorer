<?php

session_start();
set_time_limit(0);

if (!isset($_SESSION['tempfolder']) || !isset($_SESSION['cookie']))
{
    echo 'FAIL';
    return;
}

require_once __DIR__ . '/includes/KMLFile.php';
require_once __DIR__ . '/includes/KMLFiles.php';

try
{
    $kmlfiles = new KMLFiles();

    $start_date = mktime(0, 0, 0, date('m'), 1, date('Y') - 1); // last year
    $end_date = mktime(0, 0, 0, date('m'), date('j') - 1, date('Y')); // yesterday

    $_SESSION['start_date'] = $start_date;
    $_SESSION['end_date'] = $end_date;

    if (!$kmlfiles->fetchFiles($start_date, $end_date))
    {
        echo 'Error: Could not fetch the files';
        return;
    }

    echo 'ok';
}
catch (Exception $e)
{
    echo $e->getMessage();
}
