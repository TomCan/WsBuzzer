<?php
/**
 * Created by PhpStorm.
 * User: Tom
 * Date: 7/10/2014
 * Time: 19:16
 */

require_once('WsBuzzerServer.php');
require_once('config.php');

    $server = new buzzerServer($config, "0.0.0.0", 9000);

    try {
        $server->run();
    } catch (Exception $e) {
        var_dump($e);
    }