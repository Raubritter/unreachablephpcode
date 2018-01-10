<?php

namespace file;

use PhpParser\Error;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;

class fileparser
{
    private $filecalls;
    public function getFileCalls(){
        return $this->filecalls;
    }
    public function __construct($code,$path)
    {
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        try {
            $ast = $parser->parse($code);
            $traverser = new NodeTraverser;
            $allocator = new FileChecker($path);
            $traverser->addVisitor($allocator);
            $traverser->traverse($ast);

        } catch (Error $error) {
            echo "Parse error: {$error->getMessage()}\n";
            return;
        }
        $this->filecalls = $allocator->getFileCalls();
    }
}
