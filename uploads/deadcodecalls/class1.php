<?php

$class1 = new class1();
$class1->exitaufruf(1,2);
$class1->returnaufruf();
$class1->continueaufruf();
$class1->breakaufruf();
class class1
{
    public function exitaufruf($x,$y){
        if($x != $y) {
            exit;
            echo "du";
        }
    }
    public function returnaufruf(){
        return;
        echo "du";
    }
    
    public function continueaufruf(){
        for($i = 1;$i<4;$i++) {
            continue;
            echo "du";
        }
    }
    public function breakaufruf(){
        for($i = 1;$i<4;$i++) {
            break;
            echo "du";
        }
    }
}
