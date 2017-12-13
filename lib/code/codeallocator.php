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
    public function getFunctionCalls() {
        return $this->functioncalls;
    }
    public function getFunctionDeclarations() {
        return $this->functiondeclarations;
    }
    public function getError() {
        return $this->error;
    }
    /**
     * setup prev and parent node
     * @param Node $node
     */
    public function enterNode(Node $node) {
        if (!empty($this->stack)) {
            $node->setAttribute('myparent', $this->stack[count($this->stack)-1]);
        }
        if ($this->prev && $this->prev->getAttribute('parent') == $node->getAttribute('parent')) {
            $node->setAttribute('prev', get_class($this->prev));
        }
        $this->stack[] = $node;
    }
    /**
     * iterates through every node
     * @param Node $node
     */
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
        if($node instanceof Node\Stmt\For_) {
            $this->setforerrorcalls($node);
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
            if($node->name->parts[0] == "call_user_func" || $node->name->parts[0] == "call_user_func_array"){
                $this->functioncalls[0][$node->args[0]->value->value] = $node->args[0]->value->value;
            } else {
                $this->functioncalls[0][$node->name->parts[0]] = $node->name->parts[0];
            }
            
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
    /**
     * sets classcalls
     * @param type $node
     */
    private function setupcalls($node) {
        $this->classvariables[$node->getAttribute("parent")->var->name] = $node->class->parts[0];
        if(get_class($node->getAttribute("myparent")) != "ClassAllocator" && $node->getAttribute("myparent")->getAttribute("myparent") != "") {
            if($node->getAttribute("myparent") && $node->getAttribute("myparent")->getAttribute("myparent") && $node->getAttribute("myparent")->getAttribute("myparent")->getAttribute("myparent") instanceof Node\Stmt\Class_){
                //Select classnames
                $this->classcalls[$node->getAttribute("myparent")->getAttribute("myparent")->getAttribute("myparent")->name] = $node->class->parts[0];
            }
        }
        else {
               $this->classcalls[$node->class->parts[0]] = $node->class->parts[0];
        }
    }
    /**
     * if a return / break / exit or continue exists before the node => set error
     */
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
    /**
     * saves all interesting values of a variable
     * @param type $node
     */
    private function assignvar($node) {
        $dataarray["name"] = $node->var->name;
        $dataarray["parent"] = $node->getAttribute("myparent");
        
        if($node->expr instanceof Node\Expr\FuncCall) {
            $dataarray["value"] = "allpossiblevalues!";
            $dataarray["type"] = "allpossibletypes!";
            $dataarray["vtype"] = "local";
            $dataarray["save"] = false;
            if($node->expr->name->parts[0] == "rand") {
                $dataarray["min"] = $node->expr->args[0]->value->value;
                $dataarray["max"] = $node->expr->args[1]->value->value;
            } else {
                $dataarray["min"] = null;
                $dataarray["max"] = null;
            }
        } elseif($node->expr instanceof Node\Expr\New_) {           
            $dataarray["value"] = $node->expr->class->parts[0];
            $dataarray["type"] = "class";
            $dataarray["vtype"] = "local";
            $dataarray["save"] = false;
            
        } else {
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
        }
        //echo "<pre>";print_r($dataarray);
        $this->variables[$node->var->name]["data"][] = $dataarray;
    }
    /**
     * selects, if the value is an array or one value
     * @param type $value
     * @return type
     */
    private function getvariablevalue($value) {
        if($value instanceof Node\Scalar) {
            return $value->value;
        }elseif($value instanceof Node\Expr\Variable) {
            return $this->getvariablebynamevalue($value->name);
        }
    }
    /**
     * gets values from a variable
     * @param type $name
     * @return type
     */
    private function getvariablebynamevalue($name) {
        $valuearray = [];
        if(is_array($this->variables[$name]["data"])){
            foreach($this->variables[$name]["data"] as $key=>$onevalue) {
                if($this->variables[$name]["data"][$key]["value"] == "allpossiblevalues!") {
                    if($this->variables[$name]["data"][$key]["min"] != null) {
                        return new VarRange($this->variables[$name]["data"][$key]["min"],$this->variables[$name]["data"][$key]["max"]);
                    } else {
                        return $this->variables[$name]["data"][$key]["value"];
                    }
                    
                } else {
                    $valuearray[] = $this->variables[$name]["data"][$key]["value"];
                }
            }
            return $valuearray;
        }
        return $this->variables[$name]["data"][0]["value"];
    }
    private function getconditionvalue($if) {
        
        if($if instanceof Node\Expr\Instanceof_) {
            $class = $if->class->parts[0];
            if($this->variables[$if->expr->name]["data"][0]["value"] == $class) {
                $count = 0;
                $size = 1;
            } else {
                $count = 1;
                $size = 1;
            }
        } else {
            $valueleft = $this->getvariablevalue($if->left);
            $valueright = $this->getvariablevalue($if->right);
            print_r($valueright);
            list($count,$size) = $this->checkpossiblecond($if,$valueleft,$valueright);
            
        }
        return $count == $size;
    }
    private function splitconditions($if) {
        if($if->cond instanceof Node\Expr\BinaryOp\BooleanAnd) {
            $left = $this->getconditionvalue($if->cond->left);
            $right = $this->getconditionvalue($if->cond->right);
            return ($left && $right);
        }
    }
    /**
     * takes a look, if the if statment can be reached
     * @param type $if
     */
    private function setiferrorcalls($if) {
        
        $conditionsvalue = $this->splitconditions($if);
        if($conditionsvalue == false) {
            $diff = "";
            if($if->else) {
                $diff = $if->else->getAttribute("endLine")-$if->else->getAttribute("startLine")+1;
            }
            if($if->elseifs) {
                foreach($if->elseifs as $oneelseif) {
                    $diff = $diff+$oneelseif->getAttribute("endLine")-$oneelseif->getAttribute("startLine")+1;
                }
            }
            $this->error["noif"][] = array("name"=>"if","from"=>$if->getAttribute("startLine"),"to"=>$if->getAttribute("endLine")-$diff);
            $this->unsetvariables($if);
        }
    }
    public function unsetvariables($if) {
        foreach($this->variables as $key=>$onevariable) {
            if($onevariable["data"][0]["parent"] == $if) {
                unset($this->variables[$key]["data"][0]);
            }
        }
    }
    /**
     * creates an error, if the elsestatement cant be reached
     * @param type $else
     */
    private function setelseerrorcalls($else) {
        $valueleft = $this->getvariablevalue($else->getAttribute("myparent")->cond->left);
        $valueright = $this->getvariablevalue($else->getAttribute("myparent")->cond->right);
        list($count,$size) = $this->checkpossiblecond($else->getAttribute("myparent"),$valueright,$valueleft);
        if($count == $size) {
            $this->error["noelse"][] = array("name"=>"else","from"=>$else->getAttribute("startLine"),"to"=>$else->getAttribute("endLine"));
            unset($else->stmts);
        }
    }
    /**
     * looks, if switch statements can be reached
     * @param type $switch
     */
    private function setswitcherrorcalls($switch) {
        foreach($switch->cases as $onecase) {
            $values = $this->getvariablebynamevalue($switch->cond->name);
            if(is_array($values)) {
                if(!in_array($onecase->cond->value,$values)) {
                    $this->error["noswitch"][] = array("name"=>"switch","from"=>$onecase->getAttribute("startLine"),"to"=>$onecase->getAttribute("endLine"));
                }
            }
            else {
                if($values != "allpossiblevalues!" && $onecase->cond->value != $values) {
                    $this->error["noswitch"][] = array("name"=>"switch","from"=>$onecase->getAttribute("startLine"),"to"=>$onecase->getAttribute("endLine"));
                }
            }
        }
    }
    /**
     * looks, if the while statement can be reached
     * @param type $while
     */
    private function setwhileerrorcalls($while) {
        $valueleft = $this->getvariablevalue($while->cond->left);
        $valueright = $this->getvariablevalue($while->cond->right);
        list($count,$size) = $this->checkpossiblecond($while,$valueleft,$valueright);
        if($count == $size) {
            $this->error["nowhile"][] = array("name"=>"while","from"=>$while->getAttribute("startLine"),"to"=>$while->getAttribute("endLine"));
        }
    }
    /**
     * looks, if the for statement can be reached
     * @param type $for
     */
    private function setforerrorcalls($for) {
        $init = $for->init[0]->expr->value;
        if($for->loop[0] instanceof Node\Expr\PostInc || $for->loop[0] instanceof Node\Expr\PreInc) {
            $newval = $init+1;
        } else {
            $newval = $init-1;
        }
        $targetval = $for->cond[0]->right->value;
        if(abs($targetval-$newval) > abs($targetval-$init)) {
            $this->error["nofor"][] = array("name"=>"for","from"=>$for->getAttribute("startLine"),"to"=>$for->getAttribute("endLine"));
        }
    }
    /**
     * Checks, if a condition may be true
     * @param type $if
     * @param type $valueleft
     * @param type $valueright
     * @return type
     */
    private function checkpossiblecond($if,$valueleft,$valueright) {
        if(!is_array($valueleft) && !is_array($valueright)) {
            $count = 0;
            $size = 1;
            if((!($valueleft instanceof VarRange) && !($valueright instanceof VarRange)) 
                && $valueleft != "allpossiblevalues!" && $valueright != "allpossiblevalues!"){
                $valueistrue = $this->checkCondition($if->cond,$valueleft,$valueright);
                if($valueistrue === false) {
                    $count++;
                }
            } else {
                if(($valueleft instanceof VarRange) || ($valueright instanceof VarRange)){

                    $size=0;
                    if($valueleft->min != null) {
                        $size++;
                        $valueistrue = $this->checkCondition($if->cond,$valueleft->min,$valueright);
                        if($valueistrue === false) {
                            $count++;
                        }
                    }
                    if($valueleft->max != null){
                        $size++;
                        $valueistrue = $this->checkCondition($if->cond,$valueleft->max,$valueright);
                        if($valueistrue === false) {
                            $count++;
                        }
                    }
                    if($valueright->min != null) {
                        $size++;
                        $valueistrue = $this->checkCondition($if->cond,$valueleft,$valueright->min);
                        if($valueistrue === false) {
                            $count++;
                        }
                    }
                    if($valueright->max != null) {
                        $size++;
                        $valueistrue = $this->checkCondition($if->cond,$valueleft,$valueright->max);
                        if($valueistrue === false) {
                            $count++;
                        }
                    }
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
                echo "<pre>";print_r($if);
                $valueistrue = $this->checkCondition($if,$onevalueleft,$valueright);
                if($valueistrue === false) {
                    $count++;
                    echo "ja";
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
    /**
     * checks, if a condition is greater or lower
     * @param type $cond
     * @param type $valueleft
     * @param type $valueright
     * @return boolean
     */
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
        if($cond instanceof Node\Expr\BinaryOp\NotEqual && $valueleft == $valueright)
        {
            return false;
        }
        return true;
    }
}

class VarRange {
    public $min;
    public $max;
    public function __construct($min, $max) {
        $this->min = $min;
        $this->max = $max;
    }
}