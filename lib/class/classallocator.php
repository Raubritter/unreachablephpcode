<?php

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;


class ClassAllocator extends NodeVisitorAbstract
{
    private $classcalls;
    private $functioncalls;
    private $functiondeclarations;
    private $classdeclarations;
    private $stack;
    private $variables;
    private $classvariables;
    private $path;
    public function __construct($path) {
        $this->path = $path;
        $this->stack = [];
        $this->variables = [];
        $this->classvariables = [];
        $this->curclass = [];
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

    public function enterNode(Node $node) {
        if (!empty($this->stack)) {
            $node->setAttribute('myparent', $this->stack[count($this->stack)-1]);
        }
        $this->stack[] = $node;
    }
    public function leaveNode(Node $node) {
        if($node instanceof Node\Stmt\Class_) {
            //echo "Klassenname: ".$node->name."<br/><br/>";
            $this->classdeclarations[$node->name] = $node->name;
        }
        if($node instanceof Node\Expr\Assign) {
            //print_r($node->expr->dim->value);
            //print_r($node->expr->var->name);
            //$node->expr->value <= konstante
//            if($node->var->name=="y"){
//            print_r($node);
//            echo "<br/>";
//            }
//            $this->variables[$node->var->name]["name"] = $node->var->name;
//            $dataarray["type"] = T_STRING;
//            $dataarray["vtype"] = "local";
//            $dataarray["value"] = $node->expr->var->name;
//            $dataarray["min"] = null;
//            $dataarray["max"] = null;
//            $dataarray["save"] = false;
//            $this->variables[$node->var->name]["data"][] = $node->expr->value;
        }
        if($node instanceof Node\Stmt\Function_) {
            //echo "Funktionsnamen:".$node->name."<br/>";
            $this->functiondeclarations[0][$node->name] = $node->name;
        }
        if($node instanceof Node\Stmt\ClassMethod) {
            //echo "Methodennamen: ".$node->name."<br/>";
            $this->functiondeclarations[$node->getAttribute("parent")->name][$node->name] = $node->name;
        }
        if($node instanceof Node\Expr\FuncCall) {
            //echo "Funktionsaufruf:".$node->name."<br/>";
            $this->functioncalls[0][$node->name->parts[0]] = $node->name->parts[0];
        }
        if($node instanceof Node\Expr\New_) {
            $this->classvariables[$node->getAttribute("parent")->var->name] = $node->class->parts[0];
            if(get_class($node->getAttribute("myparent")) != "ClassAllocator") {
                if($node->getAttribute("myparent")->getAttribute("myparent") && $node->getAttribute("myparent")->getAttribute("myparent")->getAttribute("myparent") instanceof Node\Stmt\Class_){
                    $this->classcalls[$node->getAttribute("myparent")->getAttribute("myparent")->getAttribute("myparent")->name] = $node->class->parts[0];
                }
            }
            else {
                   $this->classcalls[] = $node->class->parts[0];
            }
        }
        if($node instanceof Node\Expr\MethodCall) {
            //echo "Methodenaufruf:".$node->name."<br/>";
            $this->functioncalls[$this->classvariables[$node->var->name]][$node->name] = $node->name;
        }
        array_pop($this->stack);
    }

    public function afterTraverse(array $nodes) {
//
//        foreach($this->functiondeclarations as $key=>$onedeclaration) {
//            $diff = array_diff($onedeclaration,$this->functioncalls[$key]);
//            if(!empty($diff)) {
//                echo "Die Funktionen ";
//                print_r($diff);
//                echo "wurden nicht aufgerufen<br/>";
//            }
//        }
//        
//        $diff = array_diff($this->classdeclarations,$this->classcalls);
//        if(!empty($diff)) {
//            echo "Die Klassen ";
//            print_r($diff);
//            echo "wurden nicht aufgerufen<br/>";
//        }
    }
}