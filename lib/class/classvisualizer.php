<?php
if($_GET['ajax'] != 'class' && $_GET['area'] == 'class') {
    include("tmpl/classvisualizer.html");
}
class classvisualizer
{    
    function __construct($filecalls)
    {
        $this->filecalls = $filecalls;
    }
    
}

