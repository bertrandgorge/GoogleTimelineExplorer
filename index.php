<?php

process();

function process()
{
    $start_date = mktime(0, 0, 0, 4, 1, 2019);
    $end_date = mktime(0, 0, 0, 9, 5, 2019);

    $curl = "https://www.google.fr/maps/timeline/_rpc/ma?hl=en^&authuser=0^&pb=^!1m9^!2m8^!1m3^!1i2019^!2i7^!3i11^!2m3^!1i2019^!2i7^!3i11^!2m3^!6b1^!7b1^!8b1^!3m11^!1m10^!1e0^!2m8^!1m3^!1i2019^!2i4^!3i9^!2m3^!1i2019^!2i8^!3i6^!5m0^!6b1^!7m3^!1s16lyXYGzMoKblwSfnq_oAg^!7e94^!15i12604\" -H \"sec-fetch-mode: cors\" -H \"accept-encoding: gzip, deflate, br\" -H \"accept-language: en-GB,en;q=0.9,en-US;q=0.8,fr;q=0.7\" -H \"user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.132 Safari/537.36\" -H \"accept: */*\" -H \"authority: www.google.fr\" -H \"cookie: SID=MgU9iInDIo13PgEn75VLB5jJyX7XVpcN729cTisQn0HRQdc6C59UF1NXvJ3Ufa7TM3nIoQ.; HSID=AnapScNTrHGZxXMRv; SSID=ANzNtrHMSO7_DCnY7; APISID=pT6Jnk3pWj_Mcxxb/AsfgE5vNuw7P9fyeQ; SAPISID=biNt_04iItSe9JF9/AwlR1l3XPQWiz-Wi4; SEARCH_SAMESITE=CgQI4Y0B; NID=188=VBtJQIVasWwVNC__NQTAK-MFBclm0qisMAN8b-c8bt9Ioh4LjmvVJz3sVH6PPTAihjFi9SoxJMRdkmXDxh04GcV1AewJG_N2xf1_ELYN8PcOBGFl1bEcuhNvYhQeCMucX6UcoO8yLHBresRt3PbL0narnInXcyimXvHUpmC3qgA; 1P_JAR=2019-09-06-19\" -H \"sec-fetch-site: same-origin\" -H \"x-client-data: CJa2yQEIpbbJAQjAtskBCKmdygEIqKPKAQjiqMoBCJetygEIza3KAQjMrsoBCMqvygEY8bDKAQ==\" --compressed";

    // Eg
    // https://www.google.com/maps/timeline/kml?authuser=0&pb=!1m8!1m3!1i2015!2i7!3i1!2m3!1i2015!2i7!3i8
    // gives 7 days.

    // Highlighting the parts of the date:
    // pb=!1m8!1m3!1i2015!2i7!3i8!2m3!1i2015!2i7!3i8


    $_SESSION['tempFolder'] = __DIR__ . '/tmp/' . '123456' . '/';

    try
    {
        $cookie = getCookie($curl);
        getKML($start_date, $end_date, $cookie,  $_SESSION['tempFolder']);

        echo "Category\tElapsed Time\tDistance (km)\tWhen\tSpeed (km/h)\n";

        parseKML($_SESSION['tempFolder']);
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

/**
 * Fetch all the KML and store them in temp xml files, because the whole process might take some time.
 */
function getKML($start_date, $end_date, $cookie, $tmpFolder)
{
    $ch = curl_init();

    while ($start_date < $end_date)
    {
        $d = date('j', $start_date);
        $m = date('n', $start_date);
        $y = date('Y', $start_date);

        $start_date = mktime(0, 0, 0, $m, $d + 1, $y);

        $tmpFilename = $tmpFolder . "$y-$m-$d.xml";

        if (file_exists($tmpFilename))
            continue;

        $google_month = $m - 1; // don't ask.

        $url = "https://www.google.fr/maps/timeline/kml?authuser=0&pb=!1m8!1m3!1i$y!2i$google_month!3i$d!2m3!1i$y!2i$google_month!3i$d";

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $xml = curl_exec($ch);

        if ($xml === false)
            throw new Exception('Erreur Curl : ' . curl_error($ch));

        file_put_contents($tmpFilename, $xml);
    }

    curl_close($ch);
}


/**
 * Parses the curl command that Google Dev Console will build, and returns the cookie part, ready to use in curl
 */
function getCookie($curlCommand)
{
    $matches = array();
    if (preg_match('@-H "cookie: ([^"]+)"@', $curlCommand, $matches))
        return $matches[1];

    throw new Exception("Please use a cURL command that matches the example. It should have a -H \"cookie: ... \" part.");
}


function parseKML($folder)
{
    $files = glob($folder . '*.xml');


    foreach ($files as $filename)
    {
        $xml = simplexml_load_file($filename);
        $dayData = array();

        foreach ($xml->Document->Placemark as $placemark)
        {
            $data= array();

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
            $data['speed'] = round($data['distance'] / ($data['elapsedTime'] / 3600), 2);
            $data['elapsedTime'] = gmdate("H:i:s", $data['elapsedTime']);

            echo $category . "\t" . implode("\t", $data) . "\n";
        }
    }


}