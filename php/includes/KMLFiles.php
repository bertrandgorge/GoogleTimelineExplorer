<?php

class KMLFiles
{
    function __construct()
    {

    }

    public function fetchFiles($start_date, $end_date)
    {
        $bValid = false;

        while ($start_date < $end_date)
        {
            $d = date('j', $start_date);
            $m = date('n', $start_date);
            $y = date('Y', $start_date);

            $kmlfile = new KMLFile($_SESSION['cookie'], $start_date, $_SESSION['tempfolder']);
            if (!$kmlfile->fetchKML())
            {
                echo 'Error: Could not fetch the file - please check that your cURL command is correct';
                $_SESSION['cookie'] = '';
                return;
            }

            if (!$kmlfile->isValid())
            {
                echo "Error: Oups... we may have triggered Google's anti-robot system... Please restart with a new cookie!";
                $_SESSION['cookie'] = '';
                return;
            }

            $bValid = true;

            $start_date = mktime(0, 0, 0, $m, $d + 1, $y);
        }

        return true;
    }


    public function getFetchProgress($start_date, $end_date)
    {
        $bValid = false;

        $files = 0;
        $filesFetched = 0;

        while ($start_date < $end_date)
        {
            $files ++;
            $d = date('j', $start_date);
            $m = date('n', $start_date);
            $y = date('Y', $start_date);

            $kmlfile = new KMLFile($_SESSION['cookie'], $start_date, $_SESSION['tempfolder']);
            if ($kmlfile->fileExists())
                $filesFetched ++;
        }

        return round(100*$filesFetched/$files);
    }
}