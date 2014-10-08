<?php
/**
 * Created by PhpStorm.
 * User: Tom
 * Date: 7/10/2014
 * Time: 20:43
 */

    $config = array(

        "mustwait" => 500,

        "users" => array(
            "admin" => array(
                "password" => "admin",
                "role" => BuzzerServer::UR_ADMIN
            ),
            "viewer" => array(
                "password" => "",
                "role" => BuzzerServer::UR_VIEWER
            ),
            "p1" => array(
                "password" => "pass1",
                "role" => BuzzerServer::UR_BUZZER
            ),
            "p2" => array(
                "password" => "pass2",
                "role" => BuzzerServer::UR_BUZZER
            ),
            "p3" => array(
                "password" => "pass3",
                "role" => BuzzerServer::UR_BUZZER
            ),
            "p4" => array(
                "password" => "pass4",
                "role" => BuzzerServer::UR_BUZZER
            ),
            "p5" => array(
                "password" => "pass5",
                "role" => BuzzerServer::UR_BUZZER
            ),
            "p6" => array(
                "password" => "pass6",
                "role" => BuzzerServer::UR_BUZZER
            ),
        ),
    );