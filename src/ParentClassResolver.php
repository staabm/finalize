<?php

declare(strict_types=1);

namespace TomasVotruba\Finalize;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use Symfony\Component\Finder\SplFileInfo;
use TomasVotruba\Finalize\NodeVisitor\ParentClassNameCollectingNodeVisitor;

final class ParentClassResolver
{
    public function __construct(
        private CachedPhpParser $cachedPhpParser
    ) {
    }

    /**
     * @param SplFileInfo[] $phpFileInfos
     * @return string[]
     */
    public function resolve(array $phpFileInfos, \Closure $progressClosure): array
    {
        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor(new NameResolver());

        $parentClassNameCollectingNodeVisitor = new ParentClassNameCollectingNodeVisitor();
        $nodeTraverser->addVisitor($parentClassNameCollectingNodeVisitor);

        $this->traverseFileInfos($phpFileInfos, $nodeTraverser, $progressClosure);

        return $parentClassNameCollectingNodeVisitor->getParentClassNames();
    }

    /**
     * @param SplFileInfo[] $phpFileInfos
     */
    private function traverseFileInfos(
        array $phpFileInfos,
        NodeTraverser $nodeTraverser,
        callable $progressClosure
    ): void {
        foreach ($phpFileInfos as $phpFileInfo) {
            $stmts = $this->cachedPhpParser->parseFile($phpFileInfo->getRealPath());

            $nodeTraverser->traverse($stmts);
            $progressClosure();
        }
    }
}
