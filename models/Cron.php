<?php
/*
This Class defines asynchronous action to be executed by a recurent Cron Process
things like : 
- sending email 
- background batch jobs 
- data analysis & statistic calculation 
- data clean ups
- background reminders and notifications
*/
class Cron {

	const COLLECTION = "cron";																					
	const TYPE_MAIL = "mail";

	const STATUS_PENDING = "pending";
	const STATUS_FAIL = "fail";
	const STATUS_DONE = "done";
	const STATUS_UPDATE = "update";

	const EXEC_COUNT = 10;
	/**
	 * adds an entry into the cron collection
	 * @param $params : a set of information for a proper cron entry
	*/
	public static function save($params, $update=null){
		//echo "adding Cron entry";
		$userId=null;
		if(@Yii::app()->session['userId'])
			$userId=Yii::app()->session['userId'];
		else if(@$params['tplParams'] && @$params['tplParams']["user"])
			$userId=$params['tplParams']["user"];

		$status = ( ( !empty($update) && $update == true ) ?  self::STATUS_UPDATE : self::STATUS_PENDING );

	    $new = array(
			"userId" => $userId,
			"status" => $status,
	  		"type"   => $params['type'],
	  		//contextType
	  		//contextId
	  		//just in case can help us out 
	    );
	    
	    if( isset( $params['execTS'] ) ) 
	    	$new['execTS'] = $params['execTS'];

	    if( $params['type'] == self::TYPE_MAIL )
	    	$new = array_merge($new , self::addMailParams($params) );

	    //Rest::json($new); exit ;
	    if(!empty($new["to"])){

	    	$entity = PHDB::findOne( Person::COLLECTION ,array("email" => $new["to"]), array("preferences"));

	    	if(!empty($entity)){ 
	    		if( (!empty($entity["preferences"]["sendMail"]) && $entity["preferences"]["sendMail"]===true) || $params["tpl"] == "invitation" || $params["tpl"] == "validation" || $params["tpl"] == "passwordRetreive"){
	    			PHDB::insert(self::COLLECTION,$new);
	    		}
	    		
	    	}else
	    		PHDB::insert(self::COLLECTION,$new);
	    }

	    
	}
	
    /**
	 * generic mail fields 
	*/
	private static function addMailParams($params){
	    return array(
			//mail specific parameters
	  		"tpl" => $params['tpl'],
	  		"subject" => $params['subject'],
	  		"from" => $params['from'],
	  		"to" => $params['to'],
	  		"tplParams" => $params['tplParams']
	    );
	}

	//TODO return result 
	public static function processMail($params){
	    $forceMail = Yii::app()->params['forceMailSend'];
	    try{
	    	return Mail::send(array("tpl"=>$params['tpl'],
		         "subject" => $params['subject'],
		         "from"=>$params['from'],																																								
		         "to" => $params['to'],
		         "tplParams" => $params['tplParams']
		    ), $forceMail);																																																																						
	    }catch (Exception $e) {
	    	//throw new CTKException("Problem sending Email : ".$e->getMessage());
			return array( "result"=> false, "msg" => "Problem sending Email : ".$e->getMessage() );
	    }
	    
	}									
	
	public static function processEntry($params){
		//echo "<br/>processing entry ".$params["type"].", id".$params["_id"];
	    if($params["type"] == self::TYPE_MAIL){
			$res = self::processMail( $params );
			//echo "<br/>sendmail : ".$params["subject"].", <br/>result :".((is_array($res)) ? $res["msg"]  : $res);
		}
		if(!is_array($res) && $res){
			//echo "<br/>processing entry ".$params["type"];
			PHDB::remove(self::COLLECTION, array("_id" => new MongoId($params["_id"])));
		}
		else
		{
			//something went wrong with the process
			$msg = ( is_array($res) && isset($res["msg"])) ? $res["msg"] : "";
			PHDB::update(self::COLLECTION, 
    	        		 array("_id" => new MongoId($params["_id"])), 
    	        		 array('$set' => array( "status" =>self::STATUS_FAIL,
    	        								"executedTS" => new MongoDate(),
    	        								"errorMsg" => $msg
    	        								)
    	        		 ));
			//TODO : add notification to system admin
			//explaining the fail
		}

	}
    
	/**
	 * Retreive a limited list of pending cron jobs 
	 * and execute them 
	 * @param $params : a set of information for the document (?to define)
	*/
	public static function processCron($count=5){
		

		$regex = Search::accentToRegex("fake.");
		// $where = array( "status" => self::STATUS_PENDING,
		// 				"userId" => array('$ne' => null),
		// 				"to" => array('$ne' => null),
		// 				"to" => array('$not' => new MongoRegex("/".$regex."/i")),
		// 				"tpl" => array('$ne' =>"priorisationCTE"),
		// 				/*'$or' => array( array( "execTS" => array( '$gt' => time())),
		// 								array( "execTS" => array( '$exists'=>-1 ) ) )*/
		// 			);
		$tpl = array("invitation", "passwordRetreive", "validation");

		$where = array('$and'=> array(
                        array( "status" => self::STATUS_PENDING), 
                        //array("userId" => array('$ne' => null)),
                        array("to" => array('$ne' => null)),
                        array("to" => array('$not' => new MongoRegex("/".$regex."/i"))),
                        array("tpl" => array('$ne' =>"priorisationCTE")),
                        array("tpl" => array('$in' => $tpl)) ) ) ;
		$jobs = PHDB::findAndSort( self::COLLECTION, $where, array('execDate' => 1), self::EXEC_COUNT);
		//Rest::json($jobs); exit ;
		$reste = self::EXEC_COUNT - count($jobs) ;
		//Rest::json($reste); exit ;
		if($reste > 0){
			$valID = array();
			foreach ($jobs as $key => $value) {
				$valID[] = new MongoId($key) ;
			}

			$where2 = array('$and'=> array(
                        array( "status" => self::STATUS_PENDING),
                        array("to" => array('$ne' => null)),
                        array("to" => array('$not' => new MongoRegex("/".$regex."/i"))),
                        array("tpl" => array('$ne' =>"priorisationCTE")),
                        array("_id" => array('$nin' => $valID)) )  ) ;
			$others = PHDB::findAndSort( self::COLLECTION, $where2, array('execDate' => 1), $reste);
			//Rest::json($others); exit ;
			$jobs = array_merge($jobs, $others);
		}

		
		//Rest::json($jobs); exit ;

		foreach ($jobs as $key => $value) {
			//TODO : cumulé plusieur message au meme email 
			try {
				self::processEntry($value);
			} catch (Exception $e) {
				error_log("processCron : ".$e);
			}
			
		}
	}

	public static function processUpdateToPending(){
		$where = array( "status" => self::STATUS_UPDATE);
		$mails = PHDB::find( self::COLLECTION, $where);
		
		foreach ($mails as $key => $value) {
			$set = array("status" => self::STATUS_PENDING);
			$res = PHDB::update(self::COLLECTION, 
				  	array("_id"=>new MongoId($key)),
					array('$set' => $set) );
		}
	}

	public static function getCron($where = array()){
		$cron = PHDB::find( self::COLLECTION , $where );

		return $cron;
	}



}
?>