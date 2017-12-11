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
            $node->setAttribute('prev', get_class($this->prev));
        }
        $this->stack[] = $node;
    }
    public function leaveNode(Node $node) {
        $this->checkfordeadcode($node);
        
        if($node instanceof Node\Stmt\Class_) {
            $this->classdeclarations[$node->name] = array("name"=>$node->name,"from"=>$node->getAttribute("startLine"),"to"=>$node->getAttribute("endLine"));
        }
        if($node instanceof Node\Expr\Assign) {
            $this->assignvar($node);
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
            unset($this->variables);
            $this->functiondeclarations[0][$node->name] = $node->name;
        }
        if($node instanceof Node\Stmt\ClassMethod) {
            unset($this->variables);
            $this->functiondeclarations[$node->getAttribute("parent")->name][$node->name] = array("name"=>$node->name,"from"=>$node->getAttribute("startLine"),"to"=>$node->getAttribute("endLine"));
        }
        if($node instanceof Node\Expr\FuncCall) {
            $this->functioncalls[0][$node->name->parts[0]] = $node->name->parts[0];
        }
        if($node instanceof Node\Expr\New_) {
            $this->setupcalls($node);
        }
        if($node instanceof Node\Expr\MethodCall) {
            $this->functioncalls[$this->classvariables[$node->var->name]][$node->name] = $node->name;
        }
        $this->prev = $node;
        array_pop($this->stack);
    }

    public function afterTraverse(array $nodes) {
        $this->setfunctionerrorcalls();
        $this->setclasserrorcalls();
        
    }
    private function setupcalls($node) {
        $this->classvariables[$node->getAttribute("parent")->var->name] = $node->class->parts[0];
        if(get_class($node->getAttribute("myparent")) != "ClassAllocator" && $node->getAttribute("myparent")->getAttribute("myparent") != "") {
            if($node->getAttribute("myparent") && $node->getAttribute("myparent")->getAttribute("myparent") && $node->getAttribute("myparent")->getAttribute("myparent")->getAttribute("myparent") instanceof Node\Stmt\Class_){
                //Klassenname raussuchen
                $this->classcalls[$node->getAttribute("myparent")->getAttribute("myparent")->getAttribute("myparent")->name] = $node->class->parts[0];
            }
        }
        else {
               $this->classcalls[$node->class->parts[0]] = $node->class->parts[0];
        }
    }
    //if a return / break / exit or continue exists before the node => set error
    private function checkfordeadcode($node) {
        if($node->getAttribute("prev") == "PhpParser\Node\Stmt\Return_" 
            || $node->getAttribute("prev") == "PhpParser\Node\Stmt\Break_"
            || $node->getAttribute("prev") == "PhpParser\Node\Expr\Exit_"
            || $node->getAttribute("prev") == "PhpParser\Node\Stmt\Continue_") {
            if(!($node instanceof Node\Stmt\ClassMethod) && !($node instanceof Node\Stmt\Class_)) {
                $this->error["deadcode"][] = array("name"=>"if","from"=>$node->getAttribute("startLine"),"to"=>$node->getAttribute("endLine"));
                $this->last = "";
            }
        }
    }
    private function assignvar($node) {
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

        list($count,$size) = $this->checkpossiblecond($if,$valueleft,$valueright);
        if($count == $size) {
            $diff = "";
            if($if->else) {
                $diff = $if->else->getAttribute("endLine")-$if->else->getAttribute("startLine")+1;
            }
            if($if->elseifs) {
                foreach($if->elseifs as $oneelseif) {
                    $diff = $diff+$oneelseif->getAttribute("endLine")-$oneelseif->getAttribute("startLine")+1;
                }
            }
            $this->error["noif"] = array("name"=>"if","from"=>$if->getAttribute("startLine"),"to"=>$if->getAttribute("endLine")-$diff);
            unset($if->stmts);
        }
    }
    private function setelseerrorcalls($else) {
        $valueleft = $this->getvariablevalue($else->getAttribute("myparent")->cond->left);
        $valueright = $this->getvariablevalue($else->getAttribute("myparent")->cond->right);
        list($count,$size) = $this->checkpossiblecond($else->getAttribute("myparent"),$valueright,$valueleft);
        if($count == $size) {
            $this->error["noelse"] = array("name"=>"else","from"=>$else->getAttribute("startLine"),"to"=>$else->getAttribute("endLine"));
            unset($else->stmts);
        }
    }
    
    private function setswitcherrorcalls($switch) {
        //echo "<pre>";print_r($switch);
        foreach($switch->cases as $onecase) {
            if(is_array($this->variables[$switch->cond->name])) {
                if(!in_array($onecase->cond->value,$this->variables[$switch->cond->name])) {
                    $this->error["noswitch"] = array("name"=>"switch","from"=>$onecase->getAttribute("startLine"),"to"=>$onecase->getAttribute("endLine"));
                }
            }
            else {
                if($onecase->cond->value != $this->variables[$switch->cond->name]) {
                    $this->error["noswitch"] = array("name"=>"switch","from"=>$onecase->getAttribute("startLine"),"to"=>$onecase->getAttribute("endLine"));
                }
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
    
    private function checkpossiblecond($if,$valueleft,$valueright) {

        if(!is_array($valueleft) && !is_array($valueright)) {
            $count = 0;
            $size = 1;
            if($valueleft != "allpossiblevalues!" && $valueright != "allpossiblevalues!"){
                $valueistrue = $this->checkCondition($if->cond,$valueleft,$valueright);
                if($valueistrue === false) {
                    $count++;
                }
            }
        }
        elseif(is_array($valueleft) && is_array($valueright)) {
            $count=0;
            $size=sizeof($valueleft)*sizeof($valueright);
            foreach($valueleft as $onevalueleft) {
                foreach($valueright as $onevalueright) {
                    $valueistrue = $this->checkCondition($if->cond,$onevalueleft,$onevalueright);
                    if($valueistrue === false) {
                        $count++;
                    }
                }
            }
        }
        elseif(is_array($valueleft)) {
            $size = sizeof($valueleft);
            $count = 0;
            foreach($valueleft as $onevalueleft) {
                $valueistrue = $this->checkCondition($if->cond,$onevalueleft,$valueright);
                if($valueistrue === false) {
                    $count++;
                }
            }
        }
        elseif(is_array($valueright)) {
            $count = 0;
            $size = sizeof($valueright);
            foreach($valueright as $onevalueright) {
                $valueistrue = $this->checkCondition($if->cond,$valueleft,$onevalueright);
                if($valueistrue === false) {
                    $count++;
                }
            }
        }
        return array($count,$size);
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