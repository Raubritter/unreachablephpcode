<?php

namespace file;

if($_GET['ajax'] != 'file' && $_GET['area'] == 'file') {
    include("tmpl/filevisualizer.html");
}
class filevisualizer
{    
    function __construct($filecalls)
    {
        $this->filecalls = $filecalls;
    }
    
}

