<?php

namespace code;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Checks code for unreachable Code
 */
class CodeChecker extends NodeVisitorAbstract
{
    private $classcalls;
    private $functioncalls;
    private $functiondeclarations;
    private $classdeclarations;
    private $stack;
    private $prev;
    private $variables;
    private $equivalenceclasses;
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
        $this->functioncalls = [];
        $this->equivalenceclasses = [];
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
        
        //$this->checkfordeadcode($node);
        
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
            $this->initvars($node->params);
            
        }
        if($node instanceof Node\Stmt\ClassMethod) {
            unset($this->variables);
            $this->functiondeclarations[$node->getAttribute("myparent")->name][$node->name] = array("name"=>$node->name,"visibility"=>$node->type,"from"=>$node->getAttribute("startLine"),"to"=>$node->getAttribute("endLine"));
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
    }
    /**
     * prepares initparams of functions and methods
     * @param type $params
     */
    private function initvars($params) {
        foreach($params as $oneparam) {
            $dataarray["name"] = $oneparam->name;
            $dataarray["equivalenceclass"] = $this->setnewequivalenceclass();
            $dataarray["value"] = "allpossiblevalues!";
            $dataarray["type"] = "allpossibletypes!";
            $dataarray["vtype"] = "global";
            $dataarray["save"] = false;
            $this->variables[$oneparam->name][] = $dataarray;
        }
            
    }
    /**
     * iterates through every node
     * @param Node $node
     */
    public function leaveNode(Node $node) {
        
        $this->prev = $node;
        array_pop($this->stack);
    }
    /**
     * sets classcalls and constructorcall
     * @param type $node
     */
    private function setupcalls($node) {
        $this->classvariables[$node->getAttribute("myparent")->var->name] = $node->class->parts[0];
        $this->functioncalls[$node->getAttribute("myparent")->var->name] = array("__construct"=>array("__construct"=>""));
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
        //@todo: trigger_error
        if($node->getAttribute("prev") == "PhpParser\Node\Stmt\Return_" 
            || $node->getAttribute("prev") == "PhpParser\Node\Stmt\Break_"
            || $node->getAttribute("prev") == "PhpParser\Node\Expr\Exit_"
            || $node->getAttribute("prev") == "PhpParser\Node\Stmt\Throw_"
            || $node->getAttribute("prev") == "PhpParser\Node\Stmt\Continue_") {
            if(!($node instanceof Node\Stmt\ClassMethod) && !($node instanceof Node\Stmt\Class_)) {
                $this->error["deadcode"][] = array("name"=>"deadcode","from"=>$node->getAttribute("startLine"),"to"=>$node->getAttribute("endLine"));
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
        } elseif($node->expr instanceof Node\Expr\Array_) {
            $valuearray = [];
            foreach($node->expr->items as $oneitem){
                $valuearray[$oneitem->value->value] = $oneitem->value->value;
            }
            $dataarray["value"] = $valuearray;
            $dataarray["type"] = gettype($valuearray["value"]["value"]);
            $dataarray["vtype"] = "local";
            $dataarray["save"] = true;
        } elseif($node->expr instanceof Node\Expr\New_) {           
            $dataarray["value"] = $node->expr->class->parts[0];
            $dataarray["type"] = "class";
            $dataarray["vtype"] = "local";
            $dataarray["save"] = false;
            
        } else {
            if($node->expr->var->name == "") {
                // assign an other var
                if($node->expr instanceof Node\Expr\Cast) {
                    if($node->expr instanceof Node\Expr\Cast\Int_) {
                        $dataarray["type"] = "integer";
                        $dataarray["value"] = (int) $this->variables[$node->expr->expr->name][0]["value"];
                    }
                    if($node->expr instanceof Node\Expr\Cast\Array_) {
                        $dataarray["type"] = "array";
                        $dataarray["value"] = (array) $this->variables[$node->expr->expr->name][0]["value"];
                    }
                    if($node->expr instanceof Node\Expr\Cast\Bool_) {
                        $dataarray["type"] = "boolean";
                        $dataarray["value"] = (bool) $this->variables[$node->expr->expr->name][0]["value"];
                    }
                    if($node->expr instanceof Node\Expr\Cast\Object_) {
                        $dataarray["type"] = "object";
                        $dataarray["value"] = (object) $this->variables[$node->expr->expr->name][0]["value"];
                    }
                    if($node->expr instanceof Node\Expr\Cast\String_) {
                        $dataarray["type"] = "string";
                        $dataarray["value"] = (string) $this->variables[$node->expr->expr->name][0]["value"];
                    }
                    if($node->expr instanceof Node\Expr\Cast\Double) {
                        $dataarray["type"] = "double";
                        $dataarray["value"] = (double) $this->variables[$node->expr->expr->name][0]["value"];
                    }
                    $expr = $node->expr->expr;
                    $cast = true;
                } else {
                    $expr = $node->expr;
                }
                if($expr->name != "") {
                    $dataarray["equivalenceclass"] = $this->variables[$expr->name][0]["equivalenceclass"];
                    $dataarray["novalue"] = $this->variables[$expr->name][0]["novalue"];
                    $dataarray["length"] = $this->variables[$expr->name][0]["length"];
                    $dataarray["maxlength"] = $this->variables[$expr->name][0]["maxlength"];
                    $dataarray["minlength"] = $this->variables[$expr->name][0]["minlength"];
                    if(!$cast){
                        $dataarray["value"] = $this->variables[$expr->name][0]["value"];
                        $dataarray["type"] = $this->variables[$expr->name][0]["type"];
                        $cast = false;
                    }
                    $dataarray["vtype"] = $this->variables[$expr->name][0]["vtype"];
                    $dataarray["save"] = $this->variables[$expr->name][0]["save"];
                } else {
                    //one value
                    $dataarray["equivalenceclass"] = $this->setnewequivalenceclass();
                    $dataarray["value"] = $expr->value;
                    if(gettype($dataarray["value"]) == "boolean"
                        || gettype($dataarray["value"]) == "integer"
                        || gettype($dataarray["value"]) == "double"
                        || gettype($dataarray["value"]) == "string") {
                        $dataarray["length"] = strlen($dataarray["value"]);
                    } elseif(gettype($dataarray["value"]) == "array") {
                        $dataarray["length"] = sizeof($dataarray["value"]);
                    } elseif(gettype($dataarray["value"]) == "object") {
                        $dataarray["class"] = get_class($dataarray["value"]);
                    }
                    $dataarray["type"] = gettype($dataarray["value"]);
                    $dataarray["vtype"] = "local";
                    $dataarray["save"] = true;
                }
            } else {
                if(in_array($node->expr->var->name,array("_GET","_POST","_SESSION"))){
                    $dataarray["equivalenceclass"] = $this->setnewequivalenceclass();
                    $dataarray["value"] = "allpossiblevalues!";
                    $dataarray["type"] = "allpossibletypes!";
                    $dataarray["vtype"] = "global";
                    $dataarray["save"] = false;
                } 
            }
            
            $dataarray["min"] = null;
            $dataarray["max"] = null;
        }
        $this->variables[$node->var->name][] = $dataarray;
    }
    /**
     * sets up a new equivalenceclass with name v1,v2,...
     * @return string
     */
    private function setnewequivalenceclass() {
        $number = sizeof($this->equivalenceclasses)+1;
        $name = "v".$number;
        $this->equivalenceclasses[] = $name;
        
        return $name;
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
        } elseif($value instanceof Node\Expr\FuncCall) {
            if($value->name->parts[0] == "strlen") {
                $this->differentlength = true;
                return $this->variables[$value->args[0]->value->name][0]["length"];
            }elseif($value->name->parts[0] == "get_type") {
                return $this->variables[$value->args[0]->value->name][0]["type"];
            }
        }
    }
    /**
     * gets values from a variable
     * @param type $name
     * @return type
     */
    private function getvariablebynamevalue($name) {
        $valuearray = [];
        
        if(is_array($this->variables[$name])){
            foreach($this->variables[$name] as $key=>$onevalue) {
                if($this->variables[$name][$key]["value"] == "allpossiblevalues!") {
                    if($this->variables[$name][$key]["min"] != null || $this->variables[$name][$key]["max"] != null) {
                        return new VarRange($this->variables[$name][$key]["min"],$this->variables[$name][$key]["max"]);
                    }else {
                        return new AllPossibleValues($name,$this->variables[$name][$key]["min"],$this->variables[$name][$key]["max"]);
                    }
                    
                } else {
                    $valuearray[$name] = $this->variables[$name][$key]["value"];
                }
            }
            return $valuearray;
        }
        return $this->variables[$name][0]["value"];
    }
    /**
     * 
     * @param type $cond
     * @return type
     */
    private function getconditionvalue($cond) {
        if($cond instanceof Node\Expr\Instanceof_) {
            $class = $cond->class->parts[0];
            $varvalue = $this->variables[$cond->expr->name][0]["value"];
            if($varvalue == $class) {
                $count = 0;
                $size = 1;
            } else {
                $count = 1;
                $size = 1;
            }
        } else {
            if($cond->left) {
                $valueleft = $this->getvariablevalue($cond->left);
                $valueright = $this->getvariablevalue($cond->right);
                list($count,$size) = $this->checkpossiblecond($cond,$valueleft,$valueright);
            } else {
                if($cond instanceof Node\Expr\FuncCall) {
                    //@todo
                    include($this->path."/unreachableif13.php");
                    if($cond->name->parts[0]($this->getvariablevalue($cond->args[0]->value->name))){
                       $count = 1;
                        $size = 1;
                    } else {
                        $count = 0;
                        $size = 1;
                    }    
                } else {
                    $valueleft = $this->getvariablevalue($cond->args[0]->value);
                    $valueright = "";
                    list($count,$size) = $this->checkpossiblecond($cond,$valueleft,$valueright);
                }
            }
            
        }
        return !($count == $size);
    }
    /**
     * Splits Conditions on &&, || and !
     * @param type $cond
     * @return type
     */
    private function splitconditions($cond) {
        if($cond instanceof Node\Expr\BinaryOp\BooleanAnd) {
            $left = $this->splitconditions($cond->left);
            $right = $this->splitconditions($cond->right);
            return ($left && $right);
        } elseif($cond instanceof Node\Expr\BinaryOp\BooleanOr) {
            $left = $this->splitconditions($cond->left);
            $right = $this->splitconditions($cond->right);
            return ($left || $right);
        } elseif($cond instanceof Node\Expr\BooleanNot) {
            return !$this->splitconditions($cond->expr);
        } else {
            return $this->getconditionvalue($cond);
        }
    }
    /**
     * takes a look, if the if statement can be reached
     * @param type $if
     */
    private function setiferrorcalls($if) {
        $conditionsvalue = $this->splitconditions($if->cond);
        if($conditionsvalue == false) {
            $diff = "";
            //setup the right start (startLine) and endposition (endLine) of the failure
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
    /**
     * unsets Variables after one scope
     * @param type $if
     */
    public function unsetvariables($if) {
        foreach($this->variables as $key=>$onevariable) {
            //todo: neu
//            if($onevariable["data"][0]["parent"] == $if) {
//                unset($this->variables[$key]["data"][0]);
//            }
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
            
            $values = $this->getvariablebynamevalue($switch->cond->name)[$switch->cond->name];
            if(is_array($values)) {
                if(!in_array($onecase->cond->value,$values) && $onecase->cond->value != "") {
                    $this->error["noswitch"][] = array("name"=>"switch","from"=>$onecase->getAttribute("startLine"),"to"=>$onecase->getAttribute("endLine"));
                }
            }
            else {
                if($values != "allpossiblevalues!" && $onecase->cond->value != $values && $onecase->cond->value != "") {
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
        $condvalue = $this->splitconditions($while->cond);
        if($condvalue == false) {
            $this->error["nowhile"][] = array("name"=>"while","from"=>$while->getAttribute("startLine"),"to"=>$while->getAttribute("endLine"));
        } else {
            $end = $this->checkendlessloop($while);
            if($end == false) {
                $this->error["endlessloop"][] = array("name"=>"while","from"=>$while->getAttribute("startLine"),"to"=>$while->getAttribute("endLine"));
            }
        }
    }
    /**
     * check a while-loop for endless loop
     * @param type $while
     * @return boolean
     */
    private function checkendlessloop($while) {
        if($while->cond instanceof Node\Expr\BinaryOp\Greater || $while->cond instanceof Node\Expr\BinaryOp\GreaterOrEqual) {
            $loopends = $this->checkstatements($while);
            if($loopends < 0) {
                return true;
            } else {
                return false;
            }
        }
        if($while->cond instanceof Node\Expr\BinaryOp\Smaller || $while->cond instanceof Node\Expr\BinaryOp\SmallerOrEqual) {
            $loopends = $this->checkstatements($while);
            if($loopends < 0) {
                return false;
            } else {
                return true;
            }
        }
        if($while->cond instanceof Node\Expr\BinaryOp\Equal || $while->cond instanceof Node\Expr\BinaryOp\Identical) {
            $loopends = $this->checkstatementsforequals($while);
            if($loopends != 0) {
                return true;
            } else {
                return false;
            }
        }
        if($while->cond instanceof Node\Expr\BinaryOp\NotEqual || $while->cond instanceof Node\Expr\BinaryOp\NotIdentical) {
            $loopends = $this->checkstatementsforunequals($while);
            if($loopends < 0) {
                return true;
            } else {
                return false;
            }
        }

    }
    /**
     * check unequal while-loops for problems
     * @param type $while
     * @return int
     */
    private function checkstatementsforunequals($while) {
        $loopends = 0;
        foreach($while->stmts as $onestmt) {
            if($onestmt->var->name == $while->cond->right->name) {
                if($while->cond->left instanceof Node\Expr\Variable) {
                    $targetval = $this->getvariablebynamevalue($while->cond->left->name);
                    $targetval = $targetval[$while->cond->left->name];
                } else {
                    $targetval = $while->left->right->value;
                }
                if($onestmt instanceof Node\Expr\PostInc || $onestmt instanceof Node\Expr\PreInc) {
                    $loopends--;
                }
                if($onestmt instanceof Node\Expr\PostDec || $onestmt instanceof Node\Expr\PreDec) {
                    $loopends++;
                }
                if($onestmt instanceof Node\Expr\Assign){
                    if($onestmt->expr instanceof Node\Expr\BinaryOp\Plus) {
                        if($onestmt->expr->left instanceof Node\Expr\Variable) {
                            $curval = $this->getvariablebynamevalue($onestmt->expr->left->name);
                            $curval = $curval[$onestmt->expr->left->name];
                        } elseif($onestmt->expr->left instanceof Node\Scalar\LNumber) {
                            $curval = $onestmt->expr->left->value;
                        }
                        if($onestmt->expr->right instanceof Node\Expr\Variable) {
                            $subval = $this->getvariablebynamevalue($onestmt->expr->right->name);
                            $subval = $subval[$onestmt->expr->right->name];
                        } elseif($onestmt->expr->right instanceof Node\Scalar\LNumber) {
                            $subval = $onestmt->expr->right->value;
                        }
                        if(is_int(($targetval-$curval)/$subval)) {
                            $loopends--;
                        }
                    }
                    if($onestmt->expr instanceof Node\Expr\BinaryOp\Minus) {
                        if($onestmt->expr->left instanceof Node\Expr\Variable) {
                            $curval = $this->getvariablebynamevalue($onestmt->expr->left->name);
                            $curval = $curval[$onestmt->expr->left->name];
                        } elseif($onestmt->expr->left instanceof Node\Scalar\LNumber) {
                            $curval = $onestmt->expr->left->value;
                        }
                        if($onestmt->expr->right instanceof Node\Expr\Variable) {
                            $subval = $this->getvariablebynamevalue($onestmt->expr->right->name);
                            $subval = $subval[$onestmt->expr->right->name];
                        } elseif($onestmt->expr->right instanceof Node\Scalar\LNumber) {
                            $subval = $onestmt->expr->right->value;
                        }
                        if(is_int(($targetval-$curval)/$subval)) {
                            $loopends--;
                        }
                    }
                }
            }
            if($onestmt->var->name == $while->cond->left->name) {
                if($while->cond->right instanceof Node\Expr\Variable) {
                    $targetval = $this->getvariablebynamevalue($while->cond->right->name);
                    $targetval = $targetval[$while->cond->right->name];
                } else {
                    $targetval = $while->cond->right->value;
                }
                if($onestmt instanceof Node\Expr\PostInc || $onestmt instanceof Node\Expr\PreInc) {
                        $loopends++;
                }
                if($onestmt instanceof Node\Expr\PostDec || $onestmt instanceof Node\Expr\PreDec) {
                    $loopends--;
                }
                if($onestmt instanceof Node\Expr\Assign){
                    if($onestmt->expr instanceof Node\Expr\BinaryOp\Plus) {
                        if($onestmt->expr->left instanceof Node\Expr\Variable) {
                            $curval = $this->getvariablebynamevalue($onestmt->expr->left->name);
                            $curval = $curval[$onestmt->expr->left->name];
                        } elseif($onestmt->expr->left instanceof Node\Scalar\LNumber) {
                            $curval = $onestmt->expr->left->value;
                        }
                        if($onestmt->expr->right instanceof Node\Expr\Variable) {
                            $subval = $this->getvariablebynamevalue($onestmt->expr->right->name);
                            $subval = $subval[$onestmt->expr->right->name];
                        } elseif($onestmt->expr->right instanceof Node\Scalar\LNumber) {
                            $subval = $onestmt->expr->right->value;
                        }
                        if(is_int(($targetval-$curval)/$subval)) {
                            $loopends--;
                        }
                    }
                    if($onestmt->expr instanceof Node\Expr\BinaryOp\Minus) {
                        if($onestmt->expr->left instanceof Node\Expr\Variable) {
                            $curval = $this->getvariablebynamevalue($onestmt->expr->left->name);
                            $curval = $curval[$onestmt->expr->left->name];
                        } elseif($onestmt->expr->left instanceof Node\Scalar\LNumber) {
                            $curval = $onestmt->expr->left->value;
                        }
                        if($onestmt->expr->right instanceof Node\Expr\Variable) {
                            $subval = $this->getvariablebynamevalue($onestmt->expr->right->name);
                            $subval = $subval[$onestmt->expr->right->name];
                        } elseif($onestmt->expr->right instanceof Node\Scalar\LNumber) {
                            $subval = $onestmt->expr->right->value;
                        }
                        if(is_int(($targetval-$curval)/$subval)) {
                            $loopends--;
                        }
                    }

                }
            }
        }
        return $loopends;        
    }
    /**
     * check equal while-loops for problems
     * @param type $while
     * @return int
     */
    private function checkstatementsforequals($while) {
        $loopends = 0;
        foreach($while->stmts as $onestmt) {
            if($onestmt instanceof Node\Expr\PostInc || $onestmt instanceof Node\Expr\PreInc){
                if($onestmt->var->name == $while->cond->left->name) {
                    $loopends++;
                }
                if($onestmt->var->name == $while->cond->right->name) {
                    $loopends--;
                }
            }
            if($onestmt instanceof Node\Expr\Assign){
                if($onestmt->var->name == $while->cond->left->name
                    && $onestmt->expr instanceof Node\Expr\BinaryOp\Plus) {
                    $loopends++;
                }
                if($onestmt->var->name == $while->cond->right->name
                    && $onestmt->expr instanceof Node\Expr\BinaryOp\Plus) {
                    $loopends--;
                }
                if($onestmt->var->name == $while->cond->right->name
                    && $onestmt->expr instanceof Node\Expr\BinaryOp\Minus) {
                    $loopends++;
                }
                if($onestmt->var->name == $while->cond->left->name
                    && $onestmt->expr instanceof Node\Expr\BinaryOp\Minus) {
                    $loopends--;
                }
            }
            if($onestmt instanceof Node\Expr\PostDec || $onestmt instanceof Node\Expr\PreDec){
                if($onestmt->var->name == $while->cond->right->name) {
                    $loopends++;
                }
                if($onestmt->var->name == $while->cond->left->name) {
                    $loopends--;
                }
            }
            
        }
        return $loopends;
    }
    /**
     * check statements for >,>=, <,<=
     * @param type $while
     * @return int
     */
    private function checkstatements($while) {
        $loopends = 0;
        foreach($while->stmts as $onestmt) {
            if($onestmt->var->name == $while->cond->left->name) {
                if($onestmt instanceof Node\Expr\PostInc || $onestmt instanceof Node\Expr\PreInc){
                    $loopends++;
                }
                if($onestmt instanceof Node\Expr\PostDec || $onestmt instanceof Node\Expr\PreDec){
                    $loopends--;
                }
                if($onestmt instanceof Node\Expr\Assign){
                    if($onestmt->expr instanceof Node\Expr\BinaryOp\Plus) {
                        $loopends++;
                    }
                    if($onestmt->expr instanceof Node\Expr\BinaryOp\Minus) {
                        $loopends--;
                    }
                }
            }
            if($onestmt->var->name == $while->cond->right->name) {            
                if($onestmt instanceof Node\Expr\PostInc || $onestmt instanceof Node\Expr\PreInc) {
                    $loopends--;
                }
                if($onestmt instanceof Node\Expr\PostDec || $onestmt instanceof Node\Expr\PreDec){
                    $loopends++;
                }
                if($onestmt instanceof Node\Expr\Assign){
                    if($onestmt->expr instanceof Node\Expr\BinaryOp\Plus) {
                        $loopends--;
                    }
                    if($onestmt->expr instanceof Node\Expr\BinaryOp\Minus) {
                        $loopends++;
                    }
                }
            }
            
        }
        return $loopends;
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
                && !($valueleft instanceof AllPossibleValues) && !($valueright instanceof AllPossibleValues)) {
                $valueistrue = $this->checkCondition($if,$valueleft,$valueright);
                if($valueistrue === false) {
                    $count++;
                }
            } elseif(($valueleft instanceof VarRange) || ($valueright instanceof VarRange)){
                $size=0;
                if($valueleft->min != null) {
                    $size++;
                    $valueistrue = $this->checkCondition($if,$valueleft->min,$valueright);
                    if($valueistrue === false) {
                        $count++;
                    }
                }
                if($valueleft->max != null){
                    $size++;
                    $valueistrue = $this->checkCondition($if,$valueleft->max,$valueright);
                    if($valueistrue === false) {
                        $count++;
                    }
                }
                if($valueright->min != null) {
                    $size++;
                    $valueistrue = $this->checkCondition($if,$valueleft,$valueright->min);
                    if($valueistrue === false) {
                        $count++;
                    }
                }
                if($valueright->max != null) {
                    $size++;
                    $valueistrue = $this->checkCondition($if,$valueleft,$valueright->max);
                    if($valueistrue === false) {
                        $count++;
                    }
                }
            } else {
                // one time allpossiblevalues!
                if($valueleft instanceof AllPossibleValues){
                    foreach($this->variables[$valueleft->name] as $key=>$onedata) {
                        if($this->differentlength) {
                            $this->differentlength = false;
                            if($if instanceof Node\Expr\BinaryOp\Greater) {
                                $equivclass = $this->variables[$valueleft->name][$key]["equivalenceclass"];
                                $this->updateallequivalenceclasses(null, null, null,null,null,$valueright+1,null,$equivclass);
                            }
                            if($if instanceof Node\Expr\BinaryOp\Smaller){
                                $equivclass = $this->variables[$valueleft->name][$key]["equivalenceclass"];
                                $this->updateallequivalenceclasses(null, null, null,null,null,null,$valueright-1,$equivclass);
                            }
                            if($if instanceof Node\Expr\BinaryOp\Equal){
                                $equivclass = $this->variables[$valueleft->name][$key]["equivalenceclass"];
                                $this->updateallequivalenceclasses(null, null, null,null,$valueright,null,null,$equivclass);
                            }
                        }
                        if($if instanceof Node\Expr\BinaryOp\Greater) {
                            $equivclass = $this->variables[$valueleft->name][$key]["equivalenceclass"];
                            $this->updateallequivalenceclasses($valueright+1, null, null,null,null,null,null,$equivclass);
                        }
                        if($if instanceof Node\Expr\BinaryOp\GreaterOrEqual) {
                            $equivclass = $this->variables[$valueleft->name][$key]["equivalenceclass"];
                            $this->updateallequivalenceclasses($valueright, null, null,null,null,null,null,$equivclass);
                        }
                        if($if instanceof Node\Expr\BinaryOp\Smaller){
                            $equivclass = $this->variables[$valueleft->name][$key]["equivalenceclass"];
                            $this->updateallequivalenceclasses(null, $valueright-1, null,null,null,null,null,$equivclass);
                        }
                        if($if instanceof Node\Expr\BinaryOp\SmallerOrEqual){
                            $equivclass = $this->variables[$valueleft->name][$key]["equivalenceclass"];
                            $this->updateallequivalenceclasses(null, $valueright, null, null,null,null,null,$equivclass);
                        }
                        if($if instanceof Node\Expr\BinaryOp\Equal){
                            $equivclass = $this->variables[$valueleft->name][$key]["equivalenceclass"];
                            $this->updateallequivalenceclasses(null, null, $valueright, null,null,null,null,$equivclass);
                        }
                        if($if instanceof Node\Expr\BinaryOp\NotEqual){
                            $equivclass = $this->variables[$valueleft->name][$key]["equivalenceclass"];
                            $this->updateallequivalenceclasses(null, null, null, $valueright,null,null,null,$equivclass);
                        }
                    }
                } elseif ($valueright instanceof AllPossibleValues){
                    foreach($this->variables[$valueright->name] as $key=>$onedata) {
                        
                        if($if instanceof Node\Expr\BinaryOp\Greater) {
                            $equivclass = $this->variables[$valueright->name][$key]["equivalenceclass"];
                            $this->updateallequivalenceclasses($valueleft+1, null, null, null,null,null,null,$equivclass);
                        }
                        if($if instanceof Node\Expr\BinaryOp\GreaterOrEqual) {
                            $equivclass = $this->variables[$valueright->name][$key]["equivalenceclass"];
                            $this->updateallequivalenceclasses($valueleft, null, null, null, null,null,null,$equivclass);
                        }
                        if($if instanceof Node\Expr\BinaryOp\Smaller){
                            $equivclass = $this->variables[$valueright->name][$key]["equivalenceclass"];
                            $this->updateallequivalenceclasses(null, $valueleft-1, null, null, null,null,null,$equivclass);
                        }
                        if($if instanceof Node\Expr\BinaryOp\SmallerOrEqual){
                            $equivclass = $this->variables[$valueright->name][$key]["equivalenceclass"];
                            $this->updateallequivalenceclasses(null, $valueleft, null, null, null,null,null,$equivclass);
                        }
                        if($if instanceof Node\Expr\BinaryOp\Equal){
                            $equivclass = $this->variables[$valueright->name][$key]["equivalenceclass"];
                            $this->updateallequivalenceclasses(null, null, $valueleft,null,null,null,null,$equivclass);
                        }
                        if($if instanceof Node\Expr\BinaryOp\NotEqual){
                            $equivclass = $this->variables[$valueright->name][$key]["equivalenceclass"];
                            $this->updateallequivalenceclasses(null, null, null,$valueleft,null,null,null,$equivclass);
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
                    $valueistrue = $this->checkCondition($if,$onevalueleft,$onevalueright);
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
                $valueistrue = $this->checkCondition($if,$onevalueleft,$valueright);
                //true = everything is right, false = something is wrong
                if($valueistrue === false) {
                    $count++;
                }
            }
        }
        elseif(is_array($valueright)) {
            $count = 0;
            $size = sizeof($valueright);
            foreach($valueright as $onevalueright) {
                $valueistrue = $this->checkCondition($if,$valueleft,$onevalueright);
                if($valueistrue === false) {
                    $count++;
                }
            }
        }
        return array($count,$size);
    }
    /**
     * update every member of a equivalenceclass:
     * @param type $min Minimum
     * @param type $max Maximum
     * @param type $value Value of the Variable
     * @param type $novalue No Value of the Variable
     * @param type $length Length of the Variable
     * @param type $minlength Minimum length of the Variable
     * @param type $maxlength Maximum length of the Variable
     * @param type $equivalenceclass Equivalenceclass
     */
    private function updateallequivalenceclasses($min,$max,$value,$novalue,$length,$minlength,$maxlength,$equivalenceclass) {
        if($min != null) {
            foreach($this->variables as $name=>$onevariable) {
                foreach($this->variables[$name] as $key=>$onevariable) {
                    if($this->variables[$name][$key]["equivalenceclass"] == $equivalenceclass){
                        $this->variables[$name][$key]["min"] = $min;
                    }
                }
            }
        }
        if($max != null) {
            foreach($this->variables as $name=>$onevariable) {
                foreach($this->variables[$name] as $key=>$onevariable) {
                    if($this->variables[$name][$key]["equivalenceclass"] == $equivalenceclass){
                   
                        $this->variables[$name][$key]["max"] = $max;
                    }
                }
            }
        }
        if($value != null) {
            foreach($this->variables as $name=>$onevariable) {
                foreach($this->variables[$name] as $key=>$onevariable) {
                    if($this->variables[$name][$key]["equivalenceclass"] == $equivalenceclass){
                        $this->variables[$name][$key]["value"] = $value;
                    }
                }
            }
        }
        if($novalue != null) {
            foreach($this->variables as $name=>$onevariable) {
                foreach($this->variables[$name] as $key=>$onevariable) {
                    if($this->variables[$name][$key]["equivalenceclass"] == $equivalenceclass){
                        $this->variables[$name][$key]["novalue"] = $novalue;
                    }
                }
            }
        }
        if($length != null) {
            foreach($this->variables as $name=>$onevariable) {
                foreach($this->variables[$name] as $key=>$onevariable) {
                    if($this->variables[$name][$key]["equivalenceclass"] == $equivalenceclass){
                        $this->variables[$name][$key]["length"] = $length;
                    }
                }
            }
        }
        if($minlength != null) {
            foreach($this->variables as $name=>$onevariable) {
                foreach($this->variables[$name] as $key=>$onevariable) {
                    if($this->variables[$name][$key]["equivalenceclass"] == $equivalenceclass){
                        $this->variables[$name][$key]["minlength"] = $minlength;
                    }
                }
            }
        }
        if($maxlength != null) {
            foreach($this->variables as $name=>$onevariable) {
                foreach($this->variables[$name] as $key=>$onevariable) {
                    if($this->variables[$name][$key]["equivalenceclass"] == $equivalenceclass){
                        $this->variables[$name][$key]["maxlength"] = $maxlength;
                    }
                }
            }
        }
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
        if($cond instanceof Node\Expr\BinaryOp\Equal && $valueleft != $valueright) 
        {
            return false;
        }
        if($cond instanceof Node\Expr\BinaryOp\NotEqual && $valueleft == $valueright)
        {
            return false;
        }
        if($cond instanceof Node\Expr\FuncCall)
        {
            if($cond->name->parts[0] == "is_a"){
                return is_a($valueleft);
            }
            if($cond->name->parts[0] == "is_array"){
                return is_array($valueleft);
            }
            if($cond->name->parts[0] == "is_boolean" || $cond->name->parts[0] == "is_bool"){
                return is_boolean($valueleft);
            }
            if($cond->name->parts[0] == "is_float"){
                return is_float($valueleft);
            }
            if($cond->name->parts[0] == "is_int"){
                return is_int($valueleft);
            }
            if($cond->name->parts[0] == "is_null"){
                return is_null($valueleft);
            }
            if($cond->name->parts[0] == "is_numberic"){
                return is_numeric($valueleft);
            }
            if($cond->name->parts[0] == "is_object"){
                return is_float($valueleft);
            }
            if($cond->name->parts[0] == "is_resource"){
                return is_resource($valueleft);
            }
            if($cond->name->parts[0] == "is_scalar"){
                return is_scalar($valueleft);
            }
            if($cond->name->parts[0] == "is_subclass_of"){
                //todo
                return is_subclass_of($valueleft);
            }
            if($cond->name->parts[0] == "empty"){
                return empty($valueleft);
            }
            if($cond->name->parts[0] == "isset"){
                return isset($valueleft);
            }
            if($cond->name->parts[0] == "count"){
                return count($valueleft);
            }
        }
        return true;
    }
}

/**
 * Class to Save Range between a minimum and maximum
 */
class VarRange {
    public $min;
    public $max;
    public function __construct($min, $max) {
        $this->min = $min;
        $this->max = $max;
    }
}
/**
 * Class to qualify all possible values of a variable
 */
class AllPossibleValues {
    public $name;
    public $min;
    public $max;
    public function __construct($name,$min,$max) {
        $this->name = $name;
        $this->min = $min;
        $this->max = $max;
    }
}