<?php

session_start();

if (!isset($_SESSION['tempfolder']) || 
    !isset($_SESSION['cookie']) ||
    empty($_POST['date']))
{
    $ret = array('error' => true,
                 'error_string' => "Bad parameters");

    echo json_encode($ret);
    return;
}

$date = strtotime($_POST['date']);

$ret = array();

require_once __DIR__ . '/includes/KMLFile.php';
require_once __DIR__ . '/includes/KMLFiles.php';

try
{
    $kmlfiles = new KMLFiles();

    $month = date('n', $date);
    $year = date('Y', $date);
    
    $ret = $kmlfiles->getMonthlyStats($month, $year);
}
catch (Exception $e)
{
    $ret = array('error' => true,
                 'error_string' => $e->getMessage());
}

echo json_encode($ret);