<?php

namespace code;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;


class ProjectChecker extends NodeVisitorAbstract
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
    public function getFunctionCalls() {
        return $this->functioncalls;
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
}
