<?php

require_once $config->get('sysRoot').'alpha/util/graphs/AlphaGraphNode.inc';

/**
 *
 * Maintains the geometry for a tree graph
 * 
 * @package util::graphs
 * @author John Collins <john@design-ireland.net>
 * @copyright 2009 John Collins
 * @version $Id$
 * 
 */
class AlphaTreeGraph {
	private $maxLevelHeight = array();
	private $maxLevelWidth = array();
	private $previousLevelNode = array();
	private $nodes = array();
	private $root;
	private $rowSpace;
	private $colSpace;
	private $branchSpace;
	private $isRendered = false;
	private $position = 0;
	private $height = 0;
	private $width = 0;	
	
	public function __construct($rowSpace=40, $colSpace=40, $branchSpace=80) {
		$this->root = new AlphaGraphNode(0, 0, 0, 0);
		$this->rowSpace = $rowSpace;
		$this->colSpace = $colSpace;
		$this->branchSpace = $branchSpace;
	}


	public function add($id, $pid, $message='', $w=0, $h=0, $nodeColour=null , $URL=null) {
		$node = new AlphaGraphNode($id, $pid, $w, $h, $message, $nodeColour, $URL);
		if(isset($this->nodes[$pid])) {
			$pnode = $this->nodes[$pid];
			$node->nodeParent = $pnode;
			$pnode->childs[] = $node;
		}else{
			$pnode = $this->root;
			$node->nodeParent = $pnode;
			$this->root->childs[] = $node;		
		}
		$this->nodes[$id] = $node;
	}
	
	private function firstScan($node, $level) {
		$this->setLevelHeight($node, $level);
        $this->setLevelWidth($node, $level);
        $this->setNeighbors($node, $level);
        
		if($node->numChilds()==0) {
            $leftSibling = $node->getLeftSibling();
            if($leftSibling) {
				$node->prelim = $leftSibling->prelim + $leftSibling->w + $this->colSpace;
			}else{
                $node->prelim = 0;
			}
        }else{
			$n = $node->numChilds();
			for($i = 0; $i < $n; $i++) {
				$this->firstScan($node->getChildAt($i), $level + 1);
            }

            $midPoint = $node->getChildrenCenter();
            $midPoint -= $node->w/2;
            $leftSibling = $node->getLeftSibling();
            if($leftSibling) {
				$node->prelim = $leftSibling->prelim + $leftSibling->w + $this->colSpace;
                $node->modifier = $node->prelim - $midPoint;

                $this->apportion($node, $level);
            }else{            	
                $node->prelim = $midPoint;
            }
        }			
	}
	
	private function secondScan($node, $level, $x=0, $y=0) {
		$xTmp = $node->prelim+$x;
		$yTmp = $y;
		$maxsizeTmp = $this->maxLevelHeight[$level];
		$nodesizeTmp = $node->h;
		$node->x = $xTmp+5;        
        $node->y = $yTmp;
		$this->height = ($this->height > $node->y+$node->w) ? $this->height : $node->y+$node->w;
		$this->width = ($this->width > $xTmp + $node->w) ? $this->width : $xTmp+$node->w+10;

        if($node->numChilds()) {
            $this->secondScan($node->getChildAt(0), $level + 1, $x + $node->modifier, $y + $maxsizeTmp + $this->rowSpace);
        }
        $rightSibling = $node->getRightSibling();
        if($rightSibling) {
            $this->secondScan($rightSibling, $level, $x, $y);
		}
	}
	
	private function apportion($node, $level) {
		$firstChild = $node->getChildAt(0);
        $firstChildLeftNeighbor = $firstChild->leftNeighbor;
        for($j = 1; $firstChild && $firstChildLeftNeighbor && $j <= $level;) {
			$modifierSumRight = 0;
            $modifierSumLeft = 0;
            $rightAncestor = $firstChild;
            $leftAncestor = $firstChildLeftNeighbor;
            for($l = 0; $l < $j; $l++) {
				$rightAncestor = $rightAncestor->nodeParent;
                $leftAncestor = $leftAncestor->nodeParent;
                $modifierSumRight += $rightAncestor->modifier;
                $modifierSumLeft += $leftAncestor->modifier;
            }

            $totalGap = ($firstChildLeftNeighbor->prelim + $modifierSumLeft + $firstChildLeftNeighbor->w + $this->branchSpace) - ($firstChild->prelim + $modifierSumRight);
			if($totalGap > 0) {
                $subtreeAux = $node;
                $numSubtrees = 0;

                for(; $subtreeAux && $subtreeAux !== $leftAncestor; $subtreeAux = $subtreeAux->getLeftSibling()) {
					$numSubtrees++;
				}

                if($subtreeAux) {
					$subtreeMoveAux = $node;
                    $singleGap = $totalGap / $numSubtrees;
                    for(; $subtreeMoveAux !== $leftAncestor; $subtreeMoveAux = $subtreeMoveAux->getLeftSibling()) {
                        $subtreeMoveAux->prelim += $totalGap;
                        $subtreeMoveAux->modifier += $totalGap;
                        $totalGap -= $singleGap;
                    }
                }
            }
			$j++;
            if($firstChild->numChilds() == 0) {
                $firstChild = $this->getLeftmost($node, 0, $j);
            }else{
                $firstChild = $firstChild->getChildAt(0);
            }
			if($firstChild)	{
                $firstChildLeftNeighbor = $firstChild->leftNeighbor;
			}
        }
}
	
	private function setLevelHeight($node, $level) {	
		if (!isset($this->maxLevelHeight[$level])) {
			$this->maxLevelHeight[$level] = 0;
		}
		if($this->maxLevelHeight[$level] < $node->h) {
			$this->maxLevelHeight[$level] = $node->h;
		}
	}
	
	private function setLevelWidth($node, $level) {
		if (!isset($this->maxLevelWidth[$level])) {
			$this->maxLevelWidth[$level] = 0;
		}
		if($this->maxLevelWidth[$level] < $node->w) {
			$this->maxLevelWidth[$level] = $node->w;		
		}
	}
	
	private function setNeighbors($node, $level) {
		$node->leftNeighbor = (isset($this->previousLevelNode[$level])) ? $this->previousLevelNode[$level] : 0 ;
		if($node->leftNeighbor)	{
			$node->leftNeighbor->rightNeighbor = $node;
		}
		$this->previousLevelNode[$level] = $node;	
	}
	
	private function getLeftmost($node, $level, $maxlevel) {
		if($level >= $maxlevel)	{
			return $node;
		}
		if(($n=$node->numChilds())==0) {
			return false;
		}
		for($i = 0; $i < $n; $i++) {
			$iChild = $node->getChildAt($i);
			$leftmostDescendant = $this->getLeftmost($iChild, $level + 1, $maxlevel);
			if($leftmostDescendant)	{
				return $leftmostDescendant;
			}
		}
		return 0;	
	}
	
	protected function render()
	{
		$this->firstScan($this->root, 0);
		$this->secondScan($this->root, 0);
		foreach($this->nodes as $node) {
			$node->getLinks();
		}
		$this->isRendered = true;
	}
	
	public function getWidth() {
		if(!$this->isRendered) {
			$this->render();
		}
		return $this->width;
	}
	
	public function getHeight()	{
		if(!$this->isRendered)
			$this->render();

		return $this->height;
	}
	
	public function count() {
        return count($this->nodes);
    }
	
	
	public function next() {
		if(!$this->isRendered) {
			$this->render();
		}
		if(isset($this->nodes[$this->position+1])) {
			$this->position++;
			return $this->nodes[$this->position];
		}else{
			return false;
		}
	}

	public function hasNext() {
		if(!isset($this->nodes[$this->position+1])) {
			return false;
		}
		return true;
	}
  
	public function getNodeAt($i) {
		if(!isset($this->nodes[$i])) {
			return false;
		}else{
			return $this->nodes[$i];
		}
	}
}

?>