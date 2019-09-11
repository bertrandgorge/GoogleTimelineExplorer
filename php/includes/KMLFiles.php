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
            $d = date('j', $end_date);
            $m = date('n', $end_date);
            $y = date('Y', $end_date);

            $kmlfile = new KMLFile($end_date, $_SESSION['tempfolder']);
            if (!$kmlfile->fetchKML($_SESSION['cookie']))
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

            $end_date = mktime(0, 0, 0, $m, $d - 1, $y);
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

            $kmlfile = new KMLFile($start_date, $_SESSION['tempfolder']);
            if ($kmlfile->fileExists())
                $filesFetched ++;
        }

        return round(100*$filesFetched/$files);
    }

    public function getMonthlyStats($month, $year)
    {
        $start_date = mktime(0, 0, 0, $month, 1, $year);
        $end_date = mktime(0, 0, 0, $month + 1, 0, $year); // the day before next month
    
        $ret = array();
        $ret['totalDays'] = 0;
        $ret['totalWorkingDays'] = 0;
        $ret['totalDaysBiked'] = 0;
        $ret['totalWorkingDaysBiked'] = 0;
        $ret['categories'] = array();

        $ret['pieChartData'] = array(
            'labels' => array(),
            'datasets' => array());
            
        $ret['pieChartData']['datasets'][0] = array(
            'data' => array(),
            'backgroundColor' => array(),
            'hoverBackgroundColor' => array(), 
            'hoverBorderColor'=> "rgba(234, 236, 244, 1)");
        // [{
        //   data: [55, 30, 15, 15],
        //   backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#CFCFCF'],
        //   : ['#2e59d9', '#17a673', '#2c9faf', '#36b9cc'],
        //   ,
        // }],

        while ($start_date < $end_date)
        {
            $d = date('j', $start_date);
            $m = date('n', $start_date);
            $y = date('Y', $start_date);
            $bWorkingDay = (date('N', $start_date) < 6); // 	1 (for Monday) through 7 (for Sunday)
            
            $kmlfile = new KMLFile($start_date, $_SESSION['tempfolder']);
            
            $start_date = mktime(0, 0, 0, $m, $d + 1, $y);
            
            $data = $kmlfile->parse();

            $ret['totalDays']++;
            if ($bWorkingDay)
                $ret['totalWorkingDays']++;

            if ($data === false)
                continue; // No file to parse

            if (isset($data['Cycling']) && $data['Cycling']['distance'] > 2) // More than 2km
            {
                $ret['totalDaysBiked']++;
                if ($bWorkingDay)
                    $ret['totalWorkingDaysBiked']++;
            }
            
            foreach ($data as $cat => $catData)
            {
                if (!isset($ret['categories'][$cat]))
                {
                    $ret['categories'][$cat] = array('distance' => 0, 'icon' => $this->getImageForCategory($cat));
                }    

                $ret['categories'][$cat]['distance'] += $catData['distance'];
            }

            $ret[date('Y-n-j', $start_date)] = $data;
        }

        foreach ($ret['categories'] as $cat => $data)
        {
            $ret['pieChartData']['labels'][] = $cat;
                
            $ret['pieChartData']['datasets'][0]['data'][] = round($data['distance']);
        }

        return $ret;
    }

    function getImageForCategory($cat)
    {
        switch ($cat)
        {
            case 'Cycling': $img = 'ic_activity_biking_black_24dp.png'; break;
            case 'Driving': $img = 'directions_car_black_24dp.png'; break;
            case 'Flying': $img = 'local_airport_black_24dp.png'; break;
            case 'On a bus': $img = 'directions_bus_black_24dp.png'; break;
            case 'On a ferry': $img = 'directions_boat_black_24dp.png'; break;
            case 'On a train': $img = 'directions_railway_black_24dp.png'; break;
            case 'On a tram': $img = 'ic_activity_tram_black_24dp.png'; break;
            case 'On the subway': $img = 'directions_railway_black_24dp.png'; break;
            case 'Running': $img = 'ic_activity_running_black_24dp.png'; break;
            case 'Skiing': $img = 'ic_activity_downhill_skiing_black_24dp.png'; break;
            case 'Walking': $img = 'ic_activity_walking_black_24dp.png'; break;
            
            // NB : there's more to be found, but let's start with that.

            default:
            case 'Moving': $img = 'ic_activity_moving_black_24dp.png'; break;
        }

    	return 'img/icons/' . $img;
    }
}