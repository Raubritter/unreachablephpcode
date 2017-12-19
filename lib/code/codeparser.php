<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of fileparser
 *
 * @author Robin
 */
namespace code;

use PhpParser\Error;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;

class codeparser
{
    private $error;
    private $classdeclarations;
    private $classcalls;
    private $functiondeclarations;
    private $functioncalls;
    public function getError(){
        return $this->error;
    }
    public function getClassDeclarations() {
        return $this->classdeclarations;
    }
    public function getClassCalls() {
        return $this->classcalls;
    }
    public function getFunctionDeclarations() {
        return $this->functiondeclarations;
    }
    public function getFunctionCalls() {
        return $this->functioncalls;
    }
    public function __construct($code,$path)
    {
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        try {
            $ast = $parser->parse($code);
            $traverser = new NodeTraverser;
            $checker = new CodeChecker($path);
            $traverser->addVisitor($checker);
            $traverser->traverse($ast);

        } catch (Error $error) {
            echo "Parse error: {$error->getMessage()}\n";
            return;
        }
        
        $this->error = $checker->getError();
        $this->classdeclarations = $checker->getClassDeclarations();
        $this->classcalls = $checker->getClassCalls();
        $this->functiondeclarations = $checker->getFunctionDeclarations();
        $this->functioncalls = $checker->getFunctionCalls();
    }
}
