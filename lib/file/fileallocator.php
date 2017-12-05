<?php

namespace file;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;


class FileAllocator extends NodeVisitorAbstract
{
    private $filecalls;
    private $path;
    public function __construct($path) {
        $this->path = $path;
    }
    public function beforeTraverse(array $nodes) {
        $this->filecalls = [];
    }
    public function getFileCalls() {
        return $this->filecalls;
    }
    public function leaveNode(Node $node) {
        if($node instanceof Node\Expr\Include_) {
            $this->filecalls[$this->path."/".$node->expr->value] = $node->expr->value;
        }
    }
}