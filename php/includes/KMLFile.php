<?php


class KMLFile
{
    private $date;
    private $tmpFolder;
    private $kmlfilename;

    function __construct($date, $tmpFolder)
    {
        $this->date = $date;
        $this->tmpFolder = $tmpFolder;
        
        $d = date('j', $this->date);
        $m = date('n', $this->date);
        $y = date('Y', $this->date);

        $this->kmlfilename = $this->tmpFolder . "$y-$m-$d.xml";
    }


    /**
     * Fetch all the KML and store them in temp xml files, because the whole process might take some time.
     */
    function fetchKML($cookie)
    {
        if (file_exists($this->kmlfilename))
            return true;
        
        $d = date('j', $this->date);
        $m = date('n', $this->date) - 1; // JS Style, months start at 0
        $y = date('Y', $this->date);

        $url = "https://www.google.fr/maps/timeline/kml?authuser=0&pb=!1m8!1m3!1i$y!2i$m!3i$d!2m3!1i$y!2i$m!3i$d";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $xml = curl_exec($ch);

        if ($xml === false)
            throw new Exception('Error: ' . curl_error($ch));

        file_put_contents($this->kmlfilename, $xml);

        curl_close($ch);

        return true;
    }

    function fileExists()
    {
        if (file_exists($this->kmlfilename))
            return true;

        return false;
    }

    /**
     * Try to parse the file. If the cookie is badly formed or if something went wrong,
     * return false, and then delete the file (so that we can reload it later)
     * Else, return true.
     */
    function isValid()
    {
        // <!DOCTYPE html>
        // <html lang=en>
        //   <meta charset=utf-8>
        //   <meta name=viewport content="initial-scale=1, minimum-scale=1, width=device-width">
        //   <title>Error 400 (Bad Request)!!1</title>
        //   <style>
        //     *{margin:0;padding:0}html,code{font:15px/22px arial,sans-serif}html{background:#fff;color:#222;padding:15px}body{margin:7% auto 0;max-width:390px;min-height:180px;padding:30px 0 15px}* > body{background:url(//www.google.com/images/errors/robot.png) 100% 5px no-repeat;padding-right:205px}p{margin:11px 0 22px;overflow:hidden}ins{color:#777;text-decoration:none}a img{border:0}@media screen and (max-width:772px){body{background:none;margin-top:0;max-width:none;padding-right:0}}#logo{background:url(//www.google.com/images/branding/googlelogo/1x/googlelogo_color_150x54dp.png) no-repeat;margin-left:-5px}@media only screen and (min-resolution:192dpi){#logo{background:url(//www.google.com/images/branding/googlelogo/2x/googlelogo_color_150x54dp.png) no-repeat 0% 0%/100% 100%;-moz-border-image:url(//www.google.com/images/branding/googlelogo/2x/googlelogo_color_150x54dp.png) 0}}@media only screen and (-webkit-min-device-pixel-ratio:2){#logo{background:url(//www.google.com/images/branding/googlelogo/2x/googlelogo_color_150x54dp.png) no-repeat;-webkit-background-size:100% 100%}}#logo{display:inline-block;height:54px;width:150px}
        //   </style>
        //   <a href=//www.google.com/><span id=logo aria-label=Google></span></a>
        //   <p><b>400.</b> <ins>That’s an error.</ins>
        //   <p>Your client has issued a malformed or illegal request.  <ins>That’s all we know.</ins>

        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($this->kmlfilename);

        if ($xml === false)
        {
            if (strpos($this->kmlfilename, dirname(__DIR__) . '/tmp/') !== false) // make sure we are not deleting some random file
                rename($this->kmlfilename, $this->kmlfilename . '.bad.xml');

            return false;
        }

        return true;
    }

    function parse()
    {
        if (!file_exists($this->kmlfilename))
            return false;

        libxml_use_internal_errors(true);

        $xml = simplexml_load_file($this->kmlfilename);

        if ($xml === false)
            throw new Exception('Error: XML file is not valid');

        $dayData = array();

        foreach ($xml->Document->Placemark as $placemark)
        {
            $data = array();

            foreach ($placemark->ExtendedData->Data as $ext)
            {
                $data[(string)$ext->attributes()->name] = (string)$ext->value;
            }

            if (empty($data['Distance']))
                continue; // ignore empty movements

            $begin = strtotime((string)$placemark->TimeSpan->begin);
            $end = strtotime((string)$placemark->TimeSpan->end);

            $distance = (int)$data['Distance'] / 1000;

            $category = $data['Category'];

            if (isset($dayData[$category]))
            {
                $dayData[$category]['elapsedTime'] += $end - $begin;
                $dayData[$category]['distance'] += $distance;
            }
            else
            {
                $dayData[$category]['elapsedTime'] = $end - $begin;
                $dayData[$category]['distance'] = $distance;
            }

            $dayData[$category]['day'] = date('Y-m-d', $begin);
        }

        foreach ($dayData as $category => $data)
        {
            $dayData[$category]['speed'] = round($data['distance'] / ($data['elapsedTime'] / 3600), 2);
            $dayData[$category]['elapsedTimeStr'] = gmdate("H:i:s", $data['elapsedTime']);
        }

        return $dayData;
    }



}