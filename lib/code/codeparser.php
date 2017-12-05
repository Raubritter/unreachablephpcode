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
use code\codeallocator;

class codeparser
{
    private $error;
    public function getError(){
        return $this->error;
    }
    public function __construct($code,$path)
    {
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        try {
            $ast = $parser->parse($code);
            $traverser = new NodeTraverser;
            $allocator = new CodeAllocator($path);
            $traverser->addVisitor($allocator);
            $traverser->traverse($ast);

        } catch (Error $error) {
            echo "Parse error: {$error->getMessage()}\n";
            return;
        }
        
        $this->error = $allocator->getError();
    }
}
