<?php

namespace file;

class filevisualizer
{    
    function __construct()
    {
        if($_GET['ajax'] != 'file' && $_GET['area'] == 'file') {
            include("tmpl/filevisualizer.html");
        }
    }
    
}

