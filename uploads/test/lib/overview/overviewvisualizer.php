<?php

namespace overview;

class overviewvisualizer {
    function __construct()
    {
        if(!$_GET['ajax'] && !$_GET['area']) {
            include("tmpl/overview.html");
        }
    }
}