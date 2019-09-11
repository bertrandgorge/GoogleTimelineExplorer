<?php

session_start();

unset($_SESSION['tempfolder']);
if (!isset($_SESSION['tempfolder']))
{
    $_SESSION['tempfolder'] = dirname(__DIR__) . '/tmp/' . '123456' . '/';

    if (!is_dir($_SESSION['tempfolder']) && !mkdir($_SESSION['tempfolder'], 0777, true))
    {
        echo 'Error: could not create temp folder '.  $_SESSION['tempfolder'];
        return;
    }
}

if (!isset($_POST['cURLCommand']))
{
    echo 'Error: Please enter a cURL command to start with.';
    return;
}

require_once __DIR__ . '/includes/KMLFile.php';

try
{
    $_SESSION['cookie'] = getCookie($_POST['cURLCommand']);

    $start_date = mktime(0, 0, 0, 4, 2, 2019); // some random date

    $kmlfile = new KMLFile($_SESSION['cookie'], $start_date, $_SESSION['tempfolder']);
    if (!$kmlfile->fetchKML())
    {
        echo 'Error: Could not fetch the file - please check that your cURL command is correct';
        $_SESSION['cookie'] = '';
        return;
    }

    if (!$kmlfile->isValid())
    {
        echo 'Error: the file was fetched but is not valid - please check that your cURL command is correct';
        $_SESSION['cookie'] = '';
        return;
    }

    echo 'ok';
}
catch (Exception $e)
{
    echo $e->getMessage();
    $_SESSION['cookie'] = '';
}


/**
 * Parses the curl command that Google Dev Console will build, and returns the cookie part, ready to use in curl
 */
function getCookie($curlCommand)
{
    $matches = array();
    if (preg_match('@-H "cookie: ([^"]+)"@', $curlCommand, $matches))
        return $matches[1];

    throw new Exception("Error: Please use a cURL command that matches the example. It should have a -H \"cookie: ... \" part.");
}
