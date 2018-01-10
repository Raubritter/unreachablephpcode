<?php

    function isittrue($x) {
        if($x > 3) {
            return true;
        }
        else {
            return false;
        }
    }
    $x = 4;
    if(isittrue($x)){
        echo "ich werde nicht erreicht";
    }
