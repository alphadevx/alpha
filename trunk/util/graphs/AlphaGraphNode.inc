<?php

/**
 *
 * Maintains the geometry for a tree node
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
class AlphaGraphNode {
	/**
	 * The id of the node
	 * 
	 * @var integer
	 * @since 1.0
	 */
	private $id = 0;
	
	/**
	 * The height of the node
	 * 
	 * @var integer
	 * @since 1.0
	 */
	private $height = 0;
	
	/**
	 * The width of the node
	 * 
	 * @var integer
	 * @since 1.0
	 */
	private $width = 0;
	
	/**
	 * The x position of the node
	 * 
	 * @var integer
	 * @since 1.0
	 */
	private $x = 0;
	
	/**
	 * The y position of the node
	 * 
	 * @var integer
	 * @since 1.0
	 */
	private $y = 0;
	
	/**
	 * The node to the left of this one
	 * 
	 * @var AlphaGraphNode
	 * @since 1.0
	 */
	private $leftNode;
	
	/**
	 * The node to the right of this one
	 * 
	 * @var AlphaGraphNode
	 * @since 1.0
	 */
	private $rightNode;
	
	/**
	 * An array of child nodes of this node
	 * 
	 * @var array
	 * @since 1.0
	 */
	private $children = array();
	
	/**
	 * The margin offset of the current node
	 * 
	 * @var integer
	 * @since 1.0
	 */
	private $offset = 0;
	
	/**
	 * Optional positional modifier
	 * 
	 * @var integer
	 * @since 1.0
	 */
	private $modifier = 0;
	
	public $parentNode = 0; 
	public $message;
	public $links = array();
	public $nodeColour;
	public $URL;
	
	public function __construct($id, $pid, $width, $height, $message = '', $nodeColour = null, $URL = null) {
		$this->id = $id;
		$this->pid = $pid;
		$this->width = $width;
		$this->height = $height;
		$this->message = $message;
		$this->nodeColour = $nodeColour;
		$this->URL = $URL;
	}
	
	public function getOffset() {
		return $this->offset;
	}
	
	public function setOffset($offset) {
		$this->offset = $offset;
	}
	
	public function getModifier() {
		return $this->modifier;
	}
	
	public function setModifier($modifier) {
		$this->modifier = $modifier;
	}
	
	
	
	public function numChilds()	{
		return count($this->children);
	}
	
	public function getParentNode() {
		return $this->parentNode;
	}
	
	public function setParentNode($node) {
		$this->parentNode = $node;
	}
	
	public function getLeftSibling() {
		if($this->leftNode && $this->leftNode->getParentNode() === $this->parentNode) {
			return $this->leftNode;
		}else{
			return false;	
		}
	}
	
	public function setLeftSibling($node) {
		$this->leftNode = $node;
	}
	
	public function getRightSibling() {
		if($this->rightNode && $this->rightNode->getParentNode() === $this->parentNode) {
			return $this->rightNode;
		}else{
			return false;
		}
	}
	
	public function setRightSibling($node) {
		$this->rightNode = $node;
	}
	
	public function getChildAt($i) {
		if(isset($this->children[$i])) {
			return $this->children[$i];
		}else{
			return false;
		}
	}
	
	public function getChildrenCenter()	{
		$node = $this->getChildAt(0);
		$node1 = $this->getChildAt(count($this->children)-1);
		return $node->getOffset() + (($node1->getOffset() - $node->getOffset()) + $node1->getWidth()) / 2;
	}
	
	public function getChildren() {
		return $this->children;
	}
	
	public function addChild($node) {
		array_push($this->children, $node);
	}
	
	public function getLinks() {
		$xa = 0; $ya = 0; $xb = 0; $yb = 0; $xc = 0; $yc = 0; $xd = 0; $yd = 0;
		$xa = $this->x + ($this->width / 2);
		$ya = $this->y + $this->height;
	
		foreach($this->children as $child) {
			$xd = $xc = $child->getX() + ($child->getWidth() / 2);
			$yd = $child->getY();
			$xb = $xa;
			$yb = $yc = $ya + ($yd - $ya) / 2;
			$this->links[$child->id]['xa'] = $xa;
			$this->links[$child->id]['ya'] = $ya;
			$this->links[$child->id]['xb'] = $xb;
			$this->links[$child->id]['yb'] = $yb;
			$this->links[$child->id]['xc'] = $xc;
			$this->links[$child->id]['yc'] = $yc;
			$this->links[$child->id]['xd'] = $xd;
			$this->links[$child->id]['yd'] = $yd;
		}
	}

	public function getHeight() {
		return $this->height;
	}
	
	public function getWidth() {
		return $this->width;
	}
	
	public function getX() {
		return $this->x;
	}
	
	public function getY() {
		return $this->y;
	}
	
	public function setX($x) {
		$this->x = $x;
	}
	
	public function setY($y) {
		$this->y = $y;
	}
} 

?>