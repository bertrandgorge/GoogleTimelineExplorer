<?php

$date = strtotime($_POST['date']);

$ret = array('date' => $_POST['date'],
             'month' => date('n', $date));

echo json_encode($ret);