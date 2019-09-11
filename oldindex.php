<?php

parseKML(__DIR__ . "/tmp/123456/");

function parseKML($folder)
{
    $files = glob($folder . '*.xml');
echo $folder . '*.xml';


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

            echo $category . "<br>\n"; // . "\t" . implode("\t", $data) . "\n";
        }
    }


}