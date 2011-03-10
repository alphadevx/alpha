<?php

require_once $config->get('sysRoot').'alpha/model/AlphaDAO.inc';

/**
 *
 * An article vote class for user ratings
 * 
 * @package alpha::model
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
class ArticleVoteObject extends AlphaDAO {
	/**
	 * The article this comment belongs to
	 * 
	 * @var Relation
	 * @since 1.0
	 */
	protected $articleOID;
	
	/**
	 * The person who cast the vote
	 * 
	 * @var Relation
	 * @since 1.0
	 */
	protected $personOID;
	
	/**
	 * The actual vote score (default 1-10)
	 * 
	 * @var Integer
	 * @since 1.0
	 */	
	protected $score;
	
	/**
	 * An array of data display labels for the class properties
	 * 
	 * @var array
	 * @since 1.0
	 */
	protected $dataLabels = array("OID"=>"Article Vote ID#","articleOID"=>"Article","personOID"=>"Voter","score"=>"Article Score");
	
	/**
	 * The name of the database table for the class
	 * 
	 * @var string
	 * @since 1.0
	 */
	const TABLE_NAME = 'ArticleVote';
	
	/**
	 * Constructor for the class
	 * 
	 * @since 1.0
	 */
	public function __construct() {
		// ensure to call the parent constructor
		parent::__construct();
		
		$this->articleOID = new Relation();
		$this->articleOID->setRelatedClass('ArticleObject');
		$this->articleOID->setRelatedClassField('OID');
		$this->articleOID->setRelatedClassDisplayField('description');
		$this->articleOID->setRelationType('MANY-TO-ONE');
		
		$this->personOID = new Relation();
		$this->personOID->setRelatedClass('PersonObject');
		$this->personOID->setRelatedClassField('OID');
		$this->personOID->setRelatedClassDisplayField('email');
		$this->personOID->setRelationType('MANY-TO-ONE');
		
		$this->score = new Integer();
	}	
}

?>