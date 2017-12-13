<?php

include("class2.php");
include("class3.php");

$class1 = new class1();
$class1->aufruf2();
$class1->aufruf3();

class class1
{
    public function aufruf1() {
        echo "ich werde nicht aufgerufen";
    }
    public function aufruf2(){
        echo "ich werde aufgerufen";
    }
    public function aufruf3() {
        echo "ich werde aufgerufen";
    }
}

