<?php

namespace clazz;

class classvisualizer
{    
    function __construct()
    {
        if($_GET['ajax'] != 'class' && $_GET['area'] == 'class') {
            include("tmpl/classvisualizer.html");
        }
    }
    
}

