<?php

require '../../vendor/autoload.php';

$tests = new Tests();
$tests->testunreachablecalls();

class Tests
{
    private function assertsame($expect,$code,$function){
        if($expect == $code) {
            echo "<font color='green'>alles ok</font> mit ".$function."<br/>";
        } else {
            echo "<font color='red'>fehler</font> mit ".$function."<br/>";
        }
    }
    public function testunreachablecalls(){
        
//        $tests->testfunctioncalls();
//        $tests->testclasscalls();
//        $this->testunreachablecall1();
//        
//        $this->testunreachablefor();
        $this->testunreachableif();
//        $this->testunreachableswitch();
//        $this->testunreachablewhile();
    }
    public function testunreachablefor() {
        $this->testunreachablefor1();
        $this->testunreachablefor2();
        $this->testunreachablefor3();
    }
    private function testunreachableif() {
//        $this->testunreachableif1();
//        $this->testunreachableif2();
//        $this->testunreachableif3();
//        $this->testunreachableif4();
//        $this->testunreachableif5();
//        $this->testunreachableif6();
//        $this->testunreachableif7();
//        $this->testunreachableif8();
//        $this->testunreachableif9();
//        $this->testunreachableif10();
//        $this->testunreachableif11();
        $this->testunreachableif12();
    }
    public function testunreachableswitch() {
        $this->testunreachableswitch1();
        $this->testunreachableswitch2();
        $this->testunreachableswitch3();
        $this->testunreachableswitch4();
        $this->testunreachableswitch5();
    }
    public function testunreachablewhile() {
        $this->testunreachablewhile1();
        $this->testunreachablewhile2();
        $this->testunreachablewhile3();
        $this->testunreachablewhile4();
        $this->testunreachablewhile5();
    }
    public function testfunctioncalls(){
        $this->testfunctioncall1();
        $this->testfunctioncall2();
    }
    public function testclasscalls(){
        $this->testclasscall1();
        $this->testclasscall2();
    }
    private function testunreachablecall1(){
        $proj = 'unreachable';
        $file = 'uploads/unreachable/unreachable1.php';
            
        $unrct = new general\unreachablecodetool();
        $expect = '{"noif":[{"name":"if","from":3,"to":4},{"name":"if","from":8,"to":10}]}';
        $code = $unrct->ajaxgetcodeproblems($file, $proj);
        $this->assertsame($expect, $code, __FUNCTION__);  
    }
    private function testunreachablefor1(){
        $proj = 'unreachable/for';
        $file = 'uploads/unreachable/for/unreachablefor1.php';
            
        $unrct = new general\unreachablecodetool();
        $expect = '{"nofor":[{"name":"for","from":3,"to":5}]}';
        $code = $unrct->ajaxgetcodeproblems($file, $proj);
        $this->assertsame($expect, $code, __FUNCTION__);  
    }
    private function testunreachablefor2(){
        $proj = 'unreachable/for';
        $file = 'uploads/unreachable/for/unreachablefor2.php';
            
        $unrct = new general\unreachablecodetool();
        $expect = '{"nofor":[{"name":"for","from":3,"to":5}]}';
        $code = $unrct->ajaxgetcodeproblems($file, $proj);
        $this->assertsame($expect, $code, __FUNCTION__);  
    }
    private function testunreachablefor3(){
        $proj = 'unreachable/for';
        $file = 'uploads/unreachable/for/unreachablefor3.php';
            
        $unrct = new general\unreachablecodetool();
        $expect = '[]';
        $code = $unrct->ajaxgetcodeproblems($file, $proj);
        $this->assertsame($expect, $code, __FUNCTION__);  
    }
    private function testunreachableif1(){
        $proj = 'unreachable/if';
        $file = 'uploads/unreachable/if/unreachableif1.php';
            
        $unrct = new general\unreachablecodetool();
        $expect = '{"noif":[{"name":"if","from":3,"to":5}]}';
        $code = $unrct->ajaxgetcodeproblems($file, $proj);
        $this->assertsame($expect, $code, __FUNCTION__);  
    }
    private function testunreachableif2(){
        $proj = 'unreachable/if';
        $file = 'uploads/unreachable/if/unreachableif2.php';
            
        $unrct = new general\unreachablecodetool();
        $expect = '{"noif":[{"name":"if","from":3,"to":5}]}';
        $code = $unrct->ajaxgetcodeproblems($file, $proj);
        $this->assertsame($expect, $code, __FUNCTION__);  
    }
    private function testunreachableif3(){
        $proj = 'unreachable/if';
        $file = 'uploads/unreachable/if/unreachableif3.php';
            
        $unrct = new general\unreachablecodetool();
        $expect = '{"noif":[{"name":"if","from":6,"to":8}]}';
        $code = $unrct->ajaxgetcodeproblems($file, $proj);
        $this->assertsame($expect, $code, __FUNCTION__);  
    }
    private function testunreachableif4(){
        $proj = 'unreachable/if';
        $file = 'uploads/unreachable/if/unreachableif4.php';
            
        $unrct = new general\unreachablecodetool();
        $expect = '{"noif":[{"name":"if","from":7,"to":9}]}';
        $code = $unrct->ajaxgetcodeproblems($file, $proj);
        $this->assertsame($expect, $code, __FUNCTION__);  
    }
    private function testunreachableif5(){
        $proj = 'unreachable/if';
        $file = 'uploads/unreachable/if/unreachableif5.php';
            
        $unrct = new general\unreachablecodetool();
        $expect = '{"noif":[{"name":"if","from":6,"to":8}]}';
        $code = $unrct->ajaxgetcodeproblems($file, $proj);
        $this->assertsame($expect, $code, __FUNCTION__);  
    }
    private function testunreachableif6(){
        $proj = 'unreachable/if';
        $file = 'uploads/unreachable/if/unreachableif6.php';
            
        $unrct = new general\unreachablecodetool();
        $expect = '{"noif":[{"name":"if","from":5,"to":7}]}';
        $code = $unrct->ajaxgetcodeproblems($file, $proj);
        $this->assertsame($expect, $code, __FUNCTION__);  
    }
    private function testunreachableif7(){
        $proj = 'unreachable/if';
        $file = 'uploads/unreachable/if/unreachableif7.php';
            
        $unrct = new general\unreachablecodetool();
        $expect = '{"noif":[{"name":"if","from":5,"to":7}]}';
        $code = $unrct->ajaxgetcodeproblems($file, $proj);
        $this->assertsame($expect, $code, __FUNCTION__);  
    }
    private function testunreachableif8(){
        $proj = 'unreachable/if';
        $file = 'uploads/unreachable/if/unreachableif8.php';
            
        $unrct = new general\unreachablecodetool();
        $expect = '{"noif":[{"name":"if","from":5,"to":7}]}';
        $code = $unrct->ajaxgetcodeproblems($file, $proj);
        $this->assertsame($expect, $code, __FUNCTION__);  
    }
    private function testunreachableif9(){
        $proj = 'unreachable/if';
        $file = 'uploads/unreachable/if/unreachableif9.php';
            
        $unrct = new general\unreachablecodetool();
        $expect = '{"noif":[{"name":"if","from":7,"to":9}]}';
        $code = $unrct->ajaxgetcodeproblems($file, $proj);
        print_r($code);
        $this->assertsame($expect, $code, __FUNCTION__);  
    }
    private function testunreachableif10(){
        $proj = 'unreachable/if';
        $file = 'uploads/unreachable/if/unreachableif10.php';
            
        $unrct = new general\unreachablecodetool();
        $expect = '{"noif":[{"name":"if","from":7,"to":9}]}';
        $code = $unrct->ajaxgetcodeproblems($file, $proj);
        print_r($code);
        $this->assertsame($expect, $code, __FUNCTION__);  
    }
    private function testunreachableif11(){
        $proj = 'unreachable/if';
        $file = 'uploads/unreachable/if/unreachableif11.php';
            
        $unrct = new general\unreachablecodetool();
        $expect = '{"noif":[{"name":"if","from":7,"to":9}]}';
        $code = $unrct->ajaxgetcodeproblems($file, $proj);
        print_r($code);
        $this->assertsame($expect, $code, __FUNCTION__);  
    }
    private function testunreachableif12(){
        $proj = 'unreachable/if';
        $file = 'uploads/unreachable/if/unreachableif12.php';
            
        $unrct = new general\unreachablecodetool();
        $expect = '{"noif":[{"name":"if","from":7,"to":9}]}';
        $code = $unrct->ajaxgetcodeproblems($file, $proj);
        print_r($code);
        $this->assertsame($expect, $code, __FUNCTION__);  
    }
    private function testunreachableswitch1(){
        $proj = 'unreachable/switch';
        $file = 'uploads/unreachable/switch/unreachableswitch1.php';
            
        $unrct = new general\unreachablecodetool();
        $expect = '{"noswitch":[{"name":"switch","from":10,"to":11}]}';
        $code = $unrct->ajaxgetcodeproblems($file, $proj);
        $this->assertsame($expect, $code, __FUNCTION__);  
    }
    private function testunreachableswitch2(){
        $proj = 'unreachable/switch';
        $file = 'uploads/unreachable/switch/unreachableswitch2.php';
            
        $unrct = new general\unreachablecodetool();
        $expect = '[]';
        $code = $unrct->ajaxgetcodeproblems($file, $proj);
        $this->assertsame($expect, $code, __FUNCTION__);  
    }
    private function testunreachableswitch3(){
        $proj = 'unreachable/switch';
        $file = 'uploads/unreachable/switch/unreachableswitch3.php';
            
        $unrct = new general\unreachablecodetool();
        $expect = '{"noswitch":[{"name":"switch","from":6,"to":7}]}';
        $code = $unrct->ajaxgetcodeproblems($file, $proj);
        $this->assertsame($expect, $code, __FUNCTION__);  
    }
    private function testunreachableswitch4(){
        $proj = 'unreachable/switch';
        $file = 'uploads/unreachable/switch/unreachableswitch4.php';
            
        $unrct = new general\unreachablecodetool();
        $expect = '[]';
        $code = $unrct->ajaxgetcodeproblems($file, $proj);
        $this->assertsame($expect, $code, __FUNCTION__);  
    }
    private function testunreachableswitch5(){
        $proj = 'unreachable/switch';
        $file = 'uploads/unreachable/switch/unreachableswitch5.php';
            
        $unrct = new general\unreachablecodetool();
        $expect = '{"noswitch":[{"name":"switch","from":8,"to":9}]}';
        $code = $unrct->ajaxgetcodeproblems($file, $proj);
        $this->assertsame($expect, $code, __FUNCTION__);  
    }
    private function testunreachablewhile1(){
        $proj = 'unreachable/while';
        $file = 'uploads/unreachable/while/unreachablewhile1.php';
            
        $unrct = new general\unreachablecodetool();
        $expect = '{"nowhile":[{"name":"while","from":3,"to":5}]}';
        $code = $unrct->ajaxgetcodeproblems($file, $proj);
        $this->assertsame($expect, $code, __FUNCTION__);  
    }
    private function testunreachablewhile2(){
        $proj = 'unreachable/while';
        $file = 'uploads/unreachable/while/unreachablewhile2.php';
            
        $unrct = new general\unreachablecodetool();
        $expect = '{"nowhile":[{"name":"while","from":4,"to":6}]}';
        $code = $unrct->ajaxgetcodeproblems($file, $proj);  
        $this->assertsame($expect, $code, __FUNCTION__);  
    }
    private function testunreachablewhile3(){
        $proj = 'unreachable/while';
        $file = 'uploads/unreachable/while/unreachablewhile3.php';
            
        $unrct = new general\unreachablecodetool();
        $expect = '{"nowhile":[{"name":"while","from":4,"to":6}]}';
        $code = $unrct->ajaxgetcodeproblems($file, $proj);
        $this->assertsame($expect, $code, __FUNCTION__);  
    }
    private function testunreachablewhile4(){
        $proj = 'unreachable/while';
        $file = 'uploads/unreachable/while/unreachablewhile4.php';
            
        $unrct = new general\unreachablecodetool();
        $expect = '{"nowhile":[{"name":"while","from":5,"to":7}]}';
        $code = $unrct->ajaxgetcodeproblems($file, $proj);
        $this->assertsame($expect, $code, __FUNCTION__);  
    }
    private function testunreachablewhile5(){
        $proj = 'unreachable/while';
        $file = 'uploads/unreachable/while/unreachablewhile5.php';
            
        $unrct = new general\unreachablecodetool();
        $expect = '[]';
        $code = $unrct->ajaxgetcodeproblems($file, $proj);
        $this->assertsame($expect, $code, __FUNCTION__);  
    }
    private function testclasscall1(){
        $proj = 'classcalls';
        $file = 'uploads/classcalls/classcall1.php';
            
        $unrct = new general\unreachablecodetool();
        $expect = '[]';
        $code = $unrct->ajaxgetcodeproblems($file, $proj);
        $this->assertsame($expect, $code, __FUNCTION__); 
    }    
    private function testclasscall2(){
        $proj = 'classcalls';
        $file = 'uploads/classcalls/classcall2.php';
            
        $unrct = new general\unreachablecodetool();
        $expect = '{"noclasscall":[{"name":"class2","from":8,"to":10}]}';
        $code = $unrct->ajaxgetcodeproblems($file, $proj);
        $this->assertsame($expect, $code, __FUNCTION__); 
    }
    private function testfunctioncall1(){
        $proj = 'functioncalls';
        $file = 'uploads/functioncalls/functioncall1.php';
            
        $unrct = new general\unreachablecodetool();
        $expect = '{"nofunccall":[{"name":"call1","visibility":2,"from":9,"to":11}]}';
        $code = $unrct->ajaxgetcodeproblems($file, $proj);
        $this->assertsame($expect, $code, __FUNCTION__); 
    }    
    private function testfunctioncall2(){
        $proj = 'functioncalls';
        $file = 'uploads/functioncalls/functioncall2.php';
            
        $unrct = new general\unreachablecodetool();
        $expect = '[]';
        $code = $unrct->ajaxgetcodeproblems($file, $proj);
        $this->assertsame($expect, $code, __FUNCTION__); 
    } 
}
