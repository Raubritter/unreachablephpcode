<?php   function foo($a,$b) {
            // v1{x}, v2{y}, v3{z}
            $x = $a + $b;
            // v1{x,y}, v3{z}
            $y = $a + $b;
            // v1{x,y,z}
            $z = $x;
            //v1{x,y} v4{z}
            if($z > 0) {
                //v1{x,y} v4{z}
                $z = 123;
            }
            //v1{x,y} v5{z}
        }


        $i = 5;
    ?><!DOCTYPE html><html></html>
