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

	/**
	 * Trace logger
	 * 
	 * @var Logger
	 */
	private static $logger = null;
	
	public function __construct($rowSpace=40, $colSpace=40, $branchSpace=80) {
		self::$logger = new Logger('AlphaTreeGraph');
		
		$this->root = new AlphaGraphNode(0, 0, 0, 0);
		$this->rowSpace = $rowSpace;
		$this->colSpace = $colSpace;
		$this->branchSpace = $branchSpace;
	}


	public function add($id, $pid, $message='', $w=0, $h=0, $nodeColour=null , $URL=null) {
		$node = new AlphaGraphNode($id, $pid, $w, $h, $message, $nodeColour, $URL);
		if(isset($this->nodes[$pid])) {
			$pnode = $this->nodes[$pid];
			$node->setParentNode($pnode);
			$pnode->addChild($node);
		}else{
			$pnode = $this->root;
			$node->setParentNode($pnode);
			$this->root->addChild($node);		
		}
		$this->nodes[$id] = $node;
	}
	
	private function firstScan($node, $level) {
		$this->setLevelHeight($node, $level);
        $this->setLevelWidth($node, $level);
        $this->setNeighbours($node, $level);
        
		if($node->numChilds()==0) {
            $leftSibling = $node->getLeftSibling();
            if($leftSibling) {
				$node->setOffset($leftSibling->getOffset() + $leftSibling->getWidth() + $this->colSpace);
			}else{
                $node->setOffset(0);
			}
        }else{
			$n = $node->numChilds();
			for($i = 0; $i < $n; $i++) {
				$this->firstScan($node->getChildAt($i), $level + 1);
            }

            $midPoint = $node->getChildrenCenter();
            $midPoint -= $node->getWidth()/2;
            $leftSibling = $node->getLeftSibling();
            if($leftSibling) {
				$node->setOffset($leftSibling->getOffset() + $leftSibling->getWidth() + $this->colSpace);
                $node->setModifier($node->getOffset() - $midPoint);

                $this->apportion($node, $level);
            }else{            	
                $node->setOffset($midPoint);
            }
        }

        self::$logger->debug('Memory usage at first scan ['.((memory_get_usage(true)/1024)/1024).' MB]');
	}
	
	private function secondScan($node, $level, $x=0, $y=0) {
		$xTmp = $node->getOffset()+$x;
		$yTmp = $y;
		$maxsizeTmp = $this->maxLevelHeight[$level];
		$nodesizeTmp = $node->getHeight();
		$node->setX($xTmp+5);
        $node->setY($yTmp);
		$this->height = ($this->height > $node->getY()+$node->getWidth()) ? $this->height : $node->getY()+$node->getWidth();
		$this->width = ($this->width > $xTmp + $node->getWidth()) ? $this->width : $xTmp+$node->getWidth()+10;

        if($node->numChilds()) {
            $this->secondScan($node->getChildAt(0), $level + 1, $x + $node->getModifier(), $y + $maxsizeTmp + $this->rowSpace);
        }
        $rightSibling = $node->getRightSibling();
        if($rightSibling) {
            $this->secondScan($rightSibling, $level, $x, $y);
		}
		
		self::$logger->debug('Memory usage at second scan ['.((memory_get_usage(true)/1024)/1024).' MB]');
	}
	
	private function apportion($node, $level) {
		$firstChild = $node->getChildAt(0);
        $firstChildLeftNeighbor = $firstChild->getLeftSibling();
        for($j = 1; $firstChild && $firstChildLeftNeighbor && $j <= $level;) {
			$modifierSumRight = 0;
            $modifierSumLeft = 0;
            $rightAncestor = $firstChild;
            $leftAncestor = $firstChildLeftNeighbor;
            for($l = 0; $l < $j; $l++) {
				$rightAncestor = $rightAncestor->getParentNode();
                $leftAncestor = $leftAncestor->getParentNode();
                $modifierSumRight += $rightAncestor->getModifier();
                $modifierSumLeft += $leftAncestor->getModifier();
            }

            $totalGap = ($firstChildLeftNeighbor->getOffset() + $modifierSumLeft + $firstChildLeftNeighbor->getWidth() + $this->branchSpace) - ($firstChild->getOffset() + $modifierSumRight);
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
                        $subtreeMoveAux->setOffset($subtreeMoveAux->getOffset() + $totalGap);
                        $subtreeMoveAux->setModifier($subtreeMoveAux->getModifier() + $totalGap);
                        //$subtreeMoveAux->offset += $totalGap;
                        //$subtreeMoveAux->modifier += $totalGap;
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
                $firstChildLeftNeighbor = $firstChild->getLeftSibling();
			}
        }
}
	
	private function setLevelHeight($node, $level) {	
		if (!isset($this->maxLevelHeight[$level])) {
			$this->maxLevelHeight[$level] = 0;
		}
		if($this->maxLevelHeight[$level] < $node->getHeight()) {
			$this->maxLevelHeight[$level] = $node->getHeight();
		}
	}
	
	private function setLevelWidth($node, $level) {
		if (!isset($this->maxLevelWidth[$level])) {
			$this->maxLevelWidth[$level] = 0;
		}
		if($this->maxLevelWidth[$level] < $node->getWidth()) {
			$this->maxLevelWidth[$level] = $node->getWidth();		
		}
	}
	
	private function setNeighbours($node, $level) {
		$node->setLeftSibling((isset($this->previousLevelNode[$level])) ? $this->previousLevelNode[$level] : 0 );
		if($node->getLeftSibling())	{
			$node->getLeftSibling()->setRightSibling($node);
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
	
	protected function render()	{
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