<?php
$class1 = new class1();
$class1->call2();
$class1->call3();

class class1
{
    protected function call1() {
        echo "ich werde nicht aufgerufen";
    }
    private function call2(){
        echo "ich werde aufgerufen";
    }
    public function call3() {
        echo "ich werde aufgerufen";
    }
}

