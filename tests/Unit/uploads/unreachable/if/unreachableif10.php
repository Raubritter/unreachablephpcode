<?php

    $y = $_POST['test'];
    $x = $y;

    if(strlen($y) < 3){
        if(strlen($x) > 3) {
            echo "nicht erreichbar";
        }
    }
    
    $z = 3;
