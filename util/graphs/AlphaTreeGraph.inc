<?php

require_once $config->get('sysRoot').'alpha/util/graphs/AlphaGraphNode.inc';

/**
 *
 * Maintains the geometry for a tree graph
 * 
 * @package alpha::util::graphs
 * @since 1.0
 * @author John Collins <john@design-ireland.net>
 * @version $Id$
 * @license http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @copyright Copyright (c) 2010, John Collins (founder of Alpha Framework).  
 * All rights reserved.
 * 
 * <pre>
 * Redistribution and use in source and binary forms, with or 
 * without modification, are permitted provided that the 
 * following conditions are met:
 * 
 * * Redistributions of source code must retain the above 
 *   copyright notice, this list of conditions and the 
 *   following disclaimer.
 * * Redistributions in binary form must reproduce the above 
 *   copyright notice, this list of conditions and the 
 *   following disclaimer in the documentation and/or other 
 *   materials provided with the distribution.
 * * Neither the name of the Alpha Framework nor the names 
 *   of its contributors may be used to endorse or promote 
 *   products derived from this software without specific 
 *   prior written permission.
 *   
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND 
 * CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, 
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF 
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE 
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR 
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, 
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT 
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) 
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN 
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE 
 * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS 
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * </pre>
 *  
 */
class AlphaTreeGraph {
	/**
	 * An array of nodes on the previous level
	 * 
	 * @var array
	 * @since 1.0
	 */
	private $previousLevelNodes = array();
	
	/**
	 * An array of nodes in this graph
	 * 
	 * @var array
	 * @since 1.0
	 */
	private $nodes = array();
	
	/**
	 * The root node of the graph
	 * 
	 * @var AlphaGraphNode
	 * @since 1.0
	 */
	private $root;
	
	/**
	 * The amount of space between graph rows
	 * 
	 * @var integer
	 * @since 1.0
	 */
	private $rowSpace;
	
	/**
	 * The amount of space between graph columns
	 * 
	 * @var integer
	 * @since 1.0
	 */
	private $colSpace;
	
	/**
	 * The amount of space between graph branches
	 * 
	 * @var integer
	 * @since 1.0
	 */
	private $branchSpace;
	
	/**
	 * Flag to track whether the chart is rendered or not
	 * 
	 * @var boolean
	 * @since 1.0
	 */
	private $isRendered = false;
	
	/**
	 * The index of the current node in the graph we are inspecting
	 * 
	 * @var integer
	 * @since 1.0
	 */
	private $position = 0;
	
	/**
	 * The height of the graph
	 * 
	 * @var integer
	 * @since 1.0
	 */
	private $height = 0;
	
	/**
	 * The width of the graph
	 * 
	 * @var integer
	 * @since 1.0
	 */
	private $width = 0;

	/**
	 * Trace logger
	 * 
	 * @var Logger
	 * @since 1.0
	 */
	private static $logger = null;
	
	/**
	 * Constructor
	 * 
	 * @param integer $rowSpace
	 * @param integer $colSpace
	 * @param integer $branchSpace
	 * @since 1.0
	 */
	public function __construct($rowSpace = 40 , $colSpace = 40, $branchSpace = 80) {
		self::$logger = new Logger('AlphaTreeGraph');
		
		$this->root = new AlphaGraphNode(0, 0, 0);
		$this->rowSpace = $rowSpace;
		$this->colSpace = $colSpace;
		$this->branchSpace = $branchSpace;
	}


	/**
	 * Add a new node to the graph
	 * 
	 * @param integer $id
	 * @param integer $pid
	 * @param string $message
	 * @param integer $w
	 * @param integer $h
	 * @param string $nodeColour
	 * @param string $URL
	 * @since 1.0
	 */
	public function add($id, $pid, $message = '', $w = 0, $h = 0, $nodeColour, $URL) {
		$node = new AlphaGraphNode($id, $w, $h, $message, $nodeColour, $URL);
		
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
	
	/**
	 * The first pass of the graph
	 * 
	 * @param AlphaGraphNode $node
	 * @param integer $level
	 * @since 1.0
	 */
	private function firstPass($node, $level) {
        $this->setNeighbours($node, $level);
        
		if($node->childCount() == 0) {
            $leftSibling = $node->getLeftSibling();
            
            if(isset($leftSibling)) {
				$node->setOffset($leftSibling->getOffset() + $leftSibling->getWidth() + $this->colSpace);
			}else{
                $node->setOffset(0);
			}
        }else{
			$childCount = $node->childCount();
			
			for($i = 0; $i < $childCount; $i++) {
				$this->firstPass($node->getChildAt($i), $level + 1);
            }

            $midPoint = $node->getChildrenCenter();
            $midPoint -= $node->getWidth()/2;
            $leftSibling = $node->getLeftSibling();
            
            if(isset($leftSibling)) {
				$node->setOffset($leftSibling->getOffset() + $leftSibling->getWidth() + $this->colSpace);
                $node->setModifier($node->getOffset() - $midPoint);

                $this->layout($node, $level);
            }else{            	
                $node->setOffset($midPoint);
            }
        }

        self::$logger->debug('Memory usage at first scan ['.((memory_get_usage(true)/1024)/1024).' MB]');
	}
	
	/**
	 * The second pass of the graph
	 * 
	 * @param AlphaGraphNode $node
	 * @param integer $level
	 * @param integer $x
	 * @param integer $y
	 * @since 1.0
	 */
	private function secondPass($node, $level, $x = 0, $y = 0) {
		$nodeX = $node->getOffset()+$x;
		$nodeY = $y;
		
		$node->setX($nodeX);
        $node->setY($nodeY);
        
		$this->height = ($this->height > $node->getY() + $node->getWidth()) ? $this->height : $node->getY() + $node->getWidth();
		$this->width = ($this->width > $nodeX + $node->getWidth()) ? $this->width : $nodeX + $node->getWidth()+10;

        if($node->childCount() > 0) {
            $this->secondPass($node->getChildAt(0), $level + 1, $x + $node->getModifier(), $y + $node->getHeight() + $this->rowSpace);
        }
        
        $rightSibling = $node->getRightSibling();
        
        if(isset($rightSibling)) {
            $this->secondPass($rightSibling, $level, $x, $y);
		}
		
		self::$logger->debug('Memory usage at second scan ['.((memory_get_usage(true)/1024)/1024).' MB]');
	}
	
	/**
	 * Handles the laying out of multi-branch trees
	 * 
	 * @param AlphaGraphNode $node
	 * @param integer $level
	 * @since 1.0
	 * @todo refactor!
	 */
	private function layout($node, $level) {
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
                        $totalGap -= $singleGap;
                    }
                }
            }
            
			$j++;
			
            if($firstChild->childCount() == 0) {
                $firstChild = $this->getLeftmost($node, 0, $j);
            }else{
                $firstChild = $firstChild->getChildAt(0);
            }
            
			if(isset($firstChild))	{
                $firstChildLeftNeighbor = $firstChild->getLeftSibling();
			}
        }
	}
	
	/**
	 * Setup neighbour nodes
	 * 
	 * @param AlphaGraphNode $node
	 * @param integer $level
	 * @since 1.0
	 */
	private function setNeighbours($node, $level) {
		if(isset($this->previousLevelNodes[$level]))
			$node->setLeftSibling($this->previousLevelNodes[$level]);
			
		if($node->getLeftSibling())	{
			$node->getLeftSibling()->setRightSibling($node);
		}
		$this->previousLevelNodes[$level] = $node;	
	}
	
	/**
	 * Get left most node in the branch
	 * 
	 * @param AlphaGraphNode $node
	 * @param integer $level
	 * @param integer $maxlevel
	 * @return AlphaGraphNode
	 * @since 1.0
	 */
	private function getLeftmost($node, $level, $maxlevel) {
		if($level >= $maxlevel)	{
			return $node;
		}
		
		$childCount = $node->childCount();
		
		if($childCount == 0) {
			return null;
		}
		
		for($i = 0; $i < $childCount; $i++) {
			$child = $node->getChildAt($i);
			
			$leftmostDescendant = $this->getLeftmost($child, $level + 1, $maxlevel);
			
			if(isset($leftmostDescendant)) {
				return $leftmostDescendant;
			}
		}
		
		return null;
	}
	
	/**
	 * Render the chart in memory
	 * 
	 * @since 1.0
	 */
	protected function render()	{
		$this->firstPass($this->root, 0);
		$this->secondPass($this->root, 0);
		
		foreach($this->nodes as $node) {
			$node->setUpLinks();
		}
		
		$this->isRendered = true;
	}
	
	/**
	 * Get the width of the graph, will invoke render() if not already rendered
	 * 
	 * @since 1.0
	 */
	public function getWidth() {
		if(!$this->isRendered) {
			$this->render();
		}
		return $this->width;
	}
	
	/**
	 * Get the heith of the graph, will invoke render() if not already rendered
	 * 
	 * @since 1.0
	 */
	public function getHeight()	{
		if(!$this->isRendered)
			$this->render();

		return $this->height;
	}	
	
	/**
	 * Get the next AlphaGraphNode instance in the graph, will invoke render() if not already rendered
	 * 
	 * @return AlphaGraphNode
	 * @since 1.0
	 */
	public function next() {
		if(!$this->isRendered) {
			$this->render();
		}
		
		if(isset($this->nodes[$this->position+1])) {
			$this->position++;
			return $this->nodes[$this->position];
		}else{
			return null;
		}
	}

	/**
	 * Check to see if another AlphaGraphNode instance in the graph is available
	 * 
	 * @return boolean
	 * @since 1.0
	 */
	public function hasNext() {
		if(isset($this->nodes[$this->position+1]))
			return true;
		else		
			return false;
	}
}

?>