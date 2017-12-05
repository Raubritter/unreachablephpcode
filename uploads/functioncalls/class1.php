<?php

$class31 = new class1();
$class31->aufruf2();
$class31->aufruf3();

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
