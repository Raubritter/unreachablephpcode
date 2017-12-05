<?php

namespace code;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;


class CodeAllocator extends NodeVisitorAbstract
{
    private $classcalls;
    private $functioncalls;
    private $functiondeclarations;
    private $classdeclarations;
    private $stack;
    private $prev;
    private $variables;
    private $classvariables;
    private $path;
    private $error;
    public function __construct($path) {
        $this->path = $path;
        $this->stack = [];
        $this->prev = null;
        $this->error = [];
        $this->variables = [];
        $this->classvariables = [];
        $this->classcalls = [];
        $this->curclass = [];
        $this->functioncalls[]["__construct"] = array("__construct"=>"");
    }
    public function beforeTraverse(array $nodes) {
        $this->filecalls = [];
    }
    public function getClassCalls() {
        return $this->classcalls;
    }
    public function getClassDeclarations() {
        return $this->classdeclarations;
    }
    public function getError() {
        return $this->error;
    }

    public function enterNode(Node $node) {
        if (!empty($this->stack)) {
            $node->setAttribute('myparent', $this->stack[count($this->stack)-1]);
        }
        if ($this->prev && $this->prev->getAttribute('parent') == $node->getAttribute('parent')) {
            $node->setAttribute('prev', $this->prev);
            $this->prev->setAttribute('next', $node);
        }
        $this->stack[] = $node;
    }
    public function leaveNode(Node $node) {
        if($node->getAttribute("prev") instanceof Node\Stmt\Return_ 
            || $node->getAttribute("prev") instanceof Node\Stmt\Break_
            || $node->getAttribute("prev") instanceof Node\Expr\Exit_
            || $node->getAttribute("prev") instanceof Node\Stmt\Continue_) {
            if(!($node instanceof Node\Stmt\ClassMethod) && !($node instanceof Node\Stmt\Class_)) {
                $this->error["deadcode"][] = array("name"=>"if","from"=>$node->getAttribute("startLine"),"to"=>$node->getAttribute("endLine"));
                $this->last = "";
            }
        }
        if($node instanceof Node\Stmt\Class_) {
            //echo "Klassenname: ".$node->name."<br/><br/>";
            $this->classdeclarations[$node->name] = array("name"=>$node->name,"from"=>$node->getAttribute("startLine"),"to"=>$node->getAttribute("endLine"));
        }
        if($node instanceof Node\Expr\Assign) {
            //echo "<pre>1";echo "</pre><br/>";
            
            $dataarray["name"] = $node->var->name;
            if($node->expr->var->name == "") {
                if($node->expr->name != "") {
                    $dataarray["value"] = $this->variables[$node->expr->name]["data"][0]["value"];
                    $dataarray["type"] = $this->variables[$node->expr->name]["data"][0]["type"];
                    $dataarray["vtype"] = $this->variables[$node->expr->name]["data"][0]["vtype"];
                    $dataarray["save"] = $this->variables[$node->expr->name]["data"][0]["save"];
                } else {
                    $dataarray["value"] = $node->expr->value;
                    $dataarray["type"] = gettype($dataarray["value"]);
                    $dataarray["vtype"] = "local";
                    $dataarray["save"] = true;
                }
            } else {
                if(in_array($node->expr->var->name,array("_GET","_POST","_SESSION"))){
                    $dataarray["value"] = "allpossiblevalues!";
                    $dataarray["type"] = "allpossibletypes!";
                    $dataarray["vtype"] = "global";
                    $dataarray["save"] = false;
                }
            }
            $dataarray["min"] = null;
            $dataarray["max"] = null;
            $this->variables[$node->var->name]["data"][] = $dataarray;
        }
        if($node instanceof Node\Stmt\If_) {
            $this->setiferrorcalls($node);
        }
        if($node instanceof Node\Stmt\Else_) {
            $this->setelseerrorcalls($node);
        }
        if($node instanceof Node\Stmt\Switch_) {
            $this->setswitcherrorcalls($node);
        }
        if($node instanceof Node\Stmt\While_) {
            $this->setwhileerrorcalls($node);
        }
        if($node instanceof Node\Stmt\Function_) {
            //echo "Funktionsnamen:".$node->name."<br/>";
            $this->functiondeclarations[0][$node->name] = $node->name;
        }
        if($node instanceof Node\Stmt\ClassMethod) {
            //echo "Methodennamen: ".$node->name."<br/>";
            $this->functiondeclarations[$node->getAttribute("parent")->name][$node->name] = array("name"=>$node->name,"from"=>$node->getAttribute("startLine"),"to"=>$node->getAttribute("endLine"));

        }
        if($node instanceof Node\Expr\FuncCall) {
            //echo "Funktionsaufruf:".$node->name."<br/>";
            $this->functioncalls[0][$node->name->parts[0]] = $node->name->parts[0];
        }
        if($node instanceof Node\Expr\New_) {
            $this->classvariables[$node->getAttribute("parent")->var->name] = $node->class->parts[0];
            if(get_class($node->getAttribute("myparent")) != "ClassAllocator" && $node->getAttribute("myparent")->getAttribute("myparent") != "") {
                if($node->getAttribute("myparent") && $node->getAttribute("myparent")->getAttribute("myparent") && $node->getAttribute("myparent")->getAttribute("myparent")->getAttribute("myparent") instanceof Node\Stmt\Class_){
                    $this->classcalls[$node->getAttribute("myparent")->getAttribute("myparent")->getAttribute("myparent")->name] = $node->class->parts[0];
                }
            }
            else {
                   $this->classcalls[$node->class->parts[0]] = $node->class->parts[0];
            }
        }
        if($node instanceof Node\Expr\MethodCall) {
            //echo "Methodenaufruf:".$node->name."<br/>";
            $this->functioncalls[$this->classvariables[$node->var->name]][$node->name] = $node->name;
        }
        $this->prev = $node;
        array_pop($this->stack);
    }

    public function afterTraverse(array $nodes) {
        $this->setfunctionerrorcalls();
        $this->setclasserrorcalls();
        
    }
    private function setfunctionerrorcalls() {
        //für jede Klasse durchgehen
        foreach($this->functiondeclarations as $key=>$onedeclaration) {
            $diff = array_diff_key($onedeclaration,$this->functioncalls[$key]);
            if(!empty($diff)) {
                foreach($diff as $onediff){
                    $this->error["nofunccall"] = $onediff;
                }
            }
        }
    }
    private function setclasserrorcalls() {
        $diff = array_diff_key($this->classdeclarations,$this->classcalls);
        if(!empty($diff)) {
            foreach($diff as $onediff){
                $this->error["noclasscall"] = $onediff;
            }
        }
    }
    private function getvariablevalue($value) {
        $valuearray = [];
        if($value instanceof Node\Scalar) {
            return $value->value;
        }elseif($value instanceof Node\Expr\Variable) {
            if(is_array($this->variables[$value->name]["data"])){
                foreach($this->variables[$value->name]["data"] as $key=>$onevalue) {
                    if($this->variables[$value->name]["data"][$key]["value"] == "allpossiblevalues!") {
                        return $this->variables[$value->name]["data"][$key]["value"];
                    } else {
                        $valuearray[] = $this->variables[$value->name]["data"][$key]["value"];
                    }
                }
                return $valuearray;
            }
            return $this->variables[$value->name]["data"][0]["value"];
        }
    }
    private function setiferrorcalls($if) {
        $valueleft = $this->getvariablevalue($if->cond->left);
        $valueright = $this->getvariablevalue($if->cond->right);
        
        print_r($valueleft);print_r($valueright);echo"<br/>";
        if(!is_array($valueleft) && !is_array($valueright)) {
            if($valueleft != "allpossiblevalues!" && $valueright != "allpossiblevalues!"){
                
                $valueistrue = $this->checkCondition($if->cond,$valueleft,$valueright);
                if($valueistrue === false) {
                    $this->error["noif"] = array("name"=>"if","from"=>$if->getAttribute("startLine"),"to"=>$if->getAttribute("endLine"));
                }
            }
        }
        elseif(is_array($valueleft) && is_array($valueright)) {
            foreach($valueleft as $onevalueleft) {
                foreach($valueright as $onevalueright) {
                    $valueistrue = $this->checkCondition($if->cond,$onevalueleft,$onevalueright);
                    if($valueistrue === false) {
                        break;
                    }
                }
            }
            if($valueistrue === false) {
                echo "2";
                $this->error["noif"] = array("name"=>"if","from"=>$if->getAttribute("startLine"),"to"=>$if->getAttribute("endLine"));
            }
        }
        elseif(is_array($valueleft)) {
            
            foreach($valueleft as $onevalueleft) {
                $valueistrue = $this->checkCondition($if->cond,$onevalueleft,$valueright);
                if($valueistrue === false) {
                    break;
                }
            }
            if($valueistrue === false) {
                echo "3";
                $this->error["noif"] = array("name"=>"if","from"=>$if->getAttribute("startLine"),"to"=>$if->getAttribute("endLine"));
            }
        }
        elseif(is_array($valueright)) {
            foreach($valueright as $onevalueright) {
                $valueistrue = $this->checkCondition($if->cond,$valueleft,$onevalueright);
                if($valueistrue === false) {
                    break;
                }
            }
            if($valueistrue === false) {
                $this->error["noif"] = array("name"=>"if","from"=>$if->getAttribute("startLine"),"to"=>$if->getAttribute("endLine"));
            }
        }
        
    }
    private function setelseerrorcalls($else) {
        $valueleft = $this->getvariablevalue($else->cond->left);
        $valueright = $this->getvariablevalue($else->cond->right);
        //@hotfix left und right vertauscht
        $valueistrue = $this->checkCondition($else->cond,$valueright,$valueleft);
        if($valueistrue === false) {
            $this->error["noelse"] = array("name"=>"else","from"=>$else->getAttribute("startLine"),"to"=>$else->getAttribute("endLine"));
        }
    }
    private function setswitcherrorcalls($switch) {
        foreach($switch->cases as $onecase) {
            if($onecase->cond->value != $this->variables[$switch->cond->name]) {
                $this->error["noswitch"] = array("name"=>"switch","from"=>$switch->getAttribute("startLine"),"to"=>$switch->getAttribute("endLine"));
            }
        }
    }
    
    private function setwhileerrorcalls($while) {
        $valueleft = $this->getvariablevalue($while->cond->left);
        $valueright = $this->getvariablevalue($while->cond->right);
        $valueistrue = $this->checkCondition($while->cond,$valueleft,$valueright);
        if($valueistrue === false) {
            $this->error["nowhile"] = array("name"=>"while","from"=>$while->getAttribute("startLine"),"to"=>$while->getAttribute("endLine"));
        }
    }
    private function checkCondition($cond,$valueleft,$valueright) {
        if($cond instanceof Node\Expr\BinaryOp\GreaterOrEqual && $valueleft < $valueright)
        {
            return false;
        }
        if($cond instanceof Node\Expr\BinaryOp\Greater && $valueleft <= $valueright)
        {
            return false;
        }
        if($cond instanceof Node\Expr\BinaryOp\Smaller && $valueleft >= $valueright)
        {
            return false;
        }
        if($cond instanceof Node\Expr\BinaryOp\SmallerOrEqual && $valueleft > $valueright)
        {
            return false;
        }
        return true;
    }
}