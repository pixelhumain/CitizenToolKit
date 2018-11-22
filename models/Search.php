<?php

class Search {
	const ICON = "fa-search";
	/**
	 * Find elements of collection based on criteria (field contains value)
	 * By default the criterias will be separated bay a "OR"
	 * @param array $criterias array (field=>value)
	 * @param String $sortOnField sort on this field name
	 * @param integer $nbResultMax number of results max to return
	 * @return array of elements of collection
	 */
	public static function findByCriterias($collection, $criterias, $sortOnField="", $nbResultMax = 10) {

	  	$seprator = '$or';
	  	$query = array();

	  	//Add the criterias 
	  	foreach ($criterias as $field => $value) {
	  		$aCriteria = array();
	  		$aCriteria[$field] = new MongoRegex("/$value/i");
	  		array_push($query, $aCriteria);
	  	}

	  	if (count($criterias) > 1) {
	  		$where = array($seprator => $query);
	  	} else {
	  		$where = $query;
	  	}

	  	$res = PHDB::findAndSort($collection, $where, array($sortOnField => 1), $nbResultMax);
	  	//$res = PHDB::find($collection, $where);
	  	return $res;
	 }

	public static function accentToRegex($text) {

		$from = str_split(utf8_decode('ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËẼÌÍÎÏĨÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëẽìíîïĩðñòóôõöøùúûüýÿ---\''));
		$to   = str_split(strtolower('SOZsozYYuAAAAAAACEEEEEIIIIIDNOOOOOOUUUUYsaaaaaaaceeeeeiiiiionoooooouuuuyy    '));
		//‘ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËẼÌÍÎÏĨÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëẽìíîïĩðñòóôõöøùúûüýÿaeiouçAEIOUÇ';
		//‘SOZsozYYuAAAAAAACEEEEEIIIIIDNOOOOOOUUUUYsaaaaaaaceeeeeiiiiionoooooouuuuyyaeioucAEIOUÇ';
		$text = utf8_decode($text);
		$regex = array();

		foreach ($to as $key => $value)
		{
			if (isset($regex[$value]))
				$regex[$value] .= $from[$key];
			else 
				$regex[$value] = $value;
		}
		
		foreach ($regex as $rg_key => $rg)
		{
			$text = preg_replace("/[$rg]/", "_{$rg_key}_", $text);
		}

		foreach ($regex as $rg_key => $rg)
		{
			$text = preg_replace("/_{$rg_key}_/", "[$rg]", $text);
		}
		return utf8_encode($text);
	}
	public static function accentToRegexSimply($text) {

		$from = str_split(utf8_decode('ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËẼÌÍÎÏĨÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëẽìíîïĩðñòóôõöøùúûüýÿ'));
		$to   = str_split(strtolower('SOZsozYYuAAAAAAACEEEEEIIIIIDNOOOOOOUUUUYsaaaaaaaceeeeeiiiiionoooooouuuuyy'));
		//‘ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËẼÌÍÎÏĨÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëẽìíîïĩðñòóôõöøùúûüýÿaeiouçAEIOUÇ';
		//‘SOZsozYYuAAAAAAACEEEEEIIIIIDNOOOOOOUUUUYsaaaaaaaceeeeeiiiiionoooooouuuuyyaeioucAEIOUÇ';
		$text = utf8_decode($text);

		foreach ($from as $key => $value){
			$text = str_replace($value, $to[$key], $text);
		}

		return utf8_encode($text);
	}

	public static function networkAutoComplete($post, $filter = null){
		$search = isset($post['name']) ? trim(urldecode($post['name'])) : null;
		$locality = isset($post['locality']) ? trim(urldecode($post['locality'])) : null;
		$searchType = isset($post['searchType']) ? $post['searchType'] : null;
		$searchTags = isset($post['searchTag']) ? $post['searchTag'] : null;
		$searchPrefTag = isset($post['searchPrefTag']) ? $post['searchPrefTag'] : null;
		$searchBy = isset($post['searchBy']) ? $post['searchBy'] : "INSEE";
		$indexMin = isset($post['indexMin']) ? $post['indexMin'] : 0;
		$indexMax = isset($post['indexMax']) ? $post['indexMax'] : 100;
		$country = isset($post['country']) ? $post['country'] : "";
		$sourceKey = isset($post['sourceKey']) ? $post['sourceKey'] : null;
		$mainTag = isset($post['mainTag']) ? $post['mainTag'] : null;
		$paramsFiltre = isset($post['paramsFiltre']) ? $post['paramsFiltre'] : null;
		$countType = isset($post['countType']) ? $post['countType'] : null;
		$indexStep = $indexMax - $indexMin;

		$searchTypeOrga = ""; /* used in CO2 to find different organisation type */
		
		if( sizeOf($searchType) == 1 &&
			@$searchType[0] == Organization::TYPE_NGO ||
			@$searchType[0] == Organization::TYPE_BUSINESS ||
			@$searchType[0] == Organization::TYPE_GROUP ||
			@$searchType[0] == Organization::TYPE_GOV) {
				$searchTypeOrga = $searchType[0];
				$searchType = array(Organization::COLLECTION);
		}

		$query = array();
		$query = self::searchString($search, $query);

		$verbTag = ( (!empty($paramsFiltre) && '$all' == $paramsFiltre) ? '$all' : '$in' ) ;
		$queryTags =  self::searchTags($searchTags, $verbTag) ;

		if( !empty($queryTags) )
			$query = array('$and' => array( $query , $queryTags) );

		$query = array('$and' => array( $query , array("state" => array('$ne' => "uncomplete")) ));

		if(!empty($mainTag)){
			$verbMainTag = ( (!empty($searchPrefTag) && '$or' == $searchPrefTag) ? '$or' : '$and' );
			$queryTags =  self::searchTags($mainTag, $verbMainTag) ;
			if( !empty($queryTags) )
				$query = array('$and' => array( $query , $queryTags) );
		}

		$query = self::searchSourceKey($sourceKey, $query);
		//$query = self::searchLocalityNetworkOld($query, $post);

		$allRes = array();
	}

	public static function globalAutoComplete($post,  $filter = null, $api=false){

		$search = (@$post['name']) ? trim(urldecode($post['name'])) : "";
        $searchLocality = isset($post['locality']) ? $post['locality'] : null;
        $searchType = isset($post['searchType']) ? $post['searchType'] : null;
        $searchTags = isset($post['searchTags']) ? $post['searchTags'] : null;
        $country = isset($post['country']) ? $post['country'] : "";
        $priceMin = isset($_POST['priceMin']) ? $_POST['priceMin'] : null;
        $priceMax = isset($_POST['priceMax']) ? $_POST['priceMax'] : null;
        $devise = isset($_POST['devise']) ? $_POST['devise'] : null;
        $latest = isset($_POST['latest']) ? $_POST['latest'] : null;
        $startDate = isset($_POST['startDate']) ? $_POST['startDate'] : null;
        $endDate = isset($_POST['endDate']) ? $_POST['endDate'] : null;
        $searchSType = !empty($post['searchSType']) ? $post['searchSType'] : "";
        $subType = !empty($post['subType']) ? $post['subType'] : "";
        $category = !empty($post['category']) ? $post['category'] : "";
        $section = !empty($post['section']) ? $post['section'] : "";
        $sourceKey = !empty($post['sourceKey']) ? $post['sourceKey'] : "";
        $countResult = (@$post["count"]) ? true : false;
        $initType = isset($_POST['initType']) ? $_POST['initType'] : null;
        $searchOnAll = (@$post['initType'] && $post['initType']=="all") ? true : false;
        $ranges = @$post['ranges'] ? $post['ranges'] : null;
        $countType = isset($post['countType']) ? $post['countType'] : null;
        $indexMin = isset($post['indexMin']) ? $post['indexMin'] : 0;
		$indexStep = isset($post['indexStep']) ? $post['indexStep'] : 30;
		$lastTimes = (!empty($post["lastTimes"])) ? $post["lastTimes"] : false;
		$onlyCount = isset($post['onlyCount']) ? true : false;
		$searchGeo = (@$post["geoSearch"]) ? $post["geoSearch"] : null; 
		
       $searchTypeOrga = ""; /* used in CO2 to find different organisation type */
		
		if( sizeOf($searchType) == 1 &&
			@$searchType[0] == Organization::TYPE_NGO ||
			@$searchType[0] == Organization::TYPE_BUSINESS ||
			@$searchType[0] == Organization::TYPE_GROUP ||
			@$searchType[0] == Organization::TYPE_GOV) {
				$searchTypeOrga = $searchType[0];
				$searchType = array(Organization::COLLECTION);
		}
		//*********************************  DEFINE GLOBAL QUERY   ******************************************
		$arrayIds=[];
		if(@Yii::app()->session["userId"]){
			$user=Element::getElementSimpleById(Yii::app()->session["userId"],Person::COLLECTION,null, array("links"));
			$arrayIds=[Yii::app()->session["userId"]];
			if(@$user["links"]["memberOf"] && !empty($user["links"]["memberOf"])){
				foreach ($user["links"]["memberOf"] as $key => $data){
					if(!@$data[Link::TO_BE_VALIDATED])
						array_push($arrayIds,$key);
				}
			}
			if(@$user["links"]["projects"] && !empty($user["links"]["projects"])){
				foreach ($user["links"]["projects"] as $key => $data){
					if(!@$data[Link::TO_BE_VALIDATED])
						array_push($arrayIds,$key);
				}
			}
			if(@$user["links"]["events"] && !empty($user["links"]["events"])){
				foreach ($user["links"]["events"] as $key => $data){
					if(!@$data[Link::TO_BE_VALIDATED])
						array_push($arrayIds,$key);
				}
			}
		}
		$query = array();
      	$queryNews=array();
      	$query = Search::searchString($search, $query);
		$query = array('$and' => 
							array( $query , 
								array("state" => array('$nin' => array("uncomplete", "deleted")),
									'$or'=>array(
											array('preferences.private'=>array('$exists'=>false)), 
											array('preferences.private'=>false),
											array("parentId" => array('$in' => $arrayIds)),
										 )
								)	
							)
						);

      	//$queryNews = Search::searchNewsString($search, $query);
      	//$queryNews = array('$and' => array( $queryNews , array("type"=>News::COLLECTION, "scope.type"=>News::TYPE_PUBLIC, "target.type"=>array('$ne'=>"pixels"))));
      	if($latest)
  			$query = array('$and' => array($query, array("updated"=>array('$exists'=>1))));

  		if(!empty($lastTimes))
  			$query = array('$and' => array($query, array("updated"=>array('$gt' => $lastTimes))));
  		
  		//if($sourceKey!="")
  		//	$query['$and'][] = array("source.key"=>$sourceKey);
  		$query = self::searchSourceKey($sourceKey, $query);
  		//if($api == true){
  			//$query = array('$and' => array($query, array("preferences.isOpenData"=> true)));
  		//}
  		
  		
        //*********************************  TAGS   ******************************************


  		if( count($searchTags) > 1  || count($searchTags) == 1 && $searchTags[0] != "" ){
  			if( (strcmp($filter, Classified::COLLECTION) != 0 && self::typeWanted(Classified::COLLECTION, $searchType)) ||
  				(strcmp($filter, Ressource::COLLECTION) != 0 && self::typeWanted(Ressource::COLLECTION, $searchType)) 
  				//|| (strcmp($filter, Place::COLLECTION) != 0 && self::typeWanted(Place::COLLECTION, $searchType)) 
  				){
        		$queryTags =  self::searchTags($searchTags, '$in') ;
	  		}
  			else if( (strcmp($filter, Service::COLLECTION) != 0 )){
  				$queryTags =  self::searchTags($searchTags, '$in', 'type') ;
  			}

  			if(!empty($queryTags))
  				$query = array('$and' => array( $query , $queryTags) );
  		}
  		$queryPersons=$query;
  		//*********************************  DEFINE LOCALITY QUERY   ****************************************
  		if(!empty($searchLocality) || !empty($searchGeo)){
  			$query = self::searchLocality($searchLocality, $query, $searchGeo);
  			$queryPersons=$query;
  			array_push( $queryPersons[ '$and' ], array("preferences.publicFields"=>array('$in' =>array("locality") )));
  			//$queryNews = self::searchLocalityNews($searchLocality, $queryNews);
  		}
  		$queryEvents = Search::getQueryEvents($query, $searchSType, $startDate, $endDate);
  		$queryClassifieds = Search::getQueryClassifieds($query, $searchSType, $section, $category, $subType,@$priceMin, @$priceMax, @$devise);
  		$queryRessources=Search::getQueryRessources($query, $searchSType, $subType, $section);
  		$allRes = array();
  		if(!$onlyCount){
	  		//*********************************  CITIES   ******************************************
	  		if(!empty($search) /*&& !empty($locality) */){
				if(strcmp($filter, City::COLLECTION) != 0 && self::typeWanted(City::COLLECTION, $searchType))
			  		$allCitiesRes = City::searchCity($country, $search, false, false);
			  	
			  	if(count($allRes) < $indexStep){
			  		if(isset($allCitiesRes)) 
			  			$allRes = array_merge($allRes, $allCitiesRes);
			  	}
			}

		  		
			//*********************************  PERSONS   ******************************************
	       	if(strcmp($filter, Person::COLLECTION) != 0 && (self::typeWanted(Person::COLLECTION, $searchType) || self::typeWanted("persons", $searchType) ) ) {
				$prefLocality = (!empty($searchLocality) ? true : false);
				if(@$ranges){
					$indexMin=$ranges[Person::COLLECTION]["indexMin"];
					$indexStep=$ranges[Person::COLLECTION]["indexMax"]-$ranges[Person::COLLECTION]["indexMin"];
				}
				$allRes = array_merge($allRes, self::searchPersons($queryPersons, $indexStep, $indexMin, $prefLocality));

		  	}

		  	//*********************************  ORGANISATIONS   ******************************************
			if(strcmp($filter, Organization::COLLECTION) != 0 && self::typeWanted(Organization::COLLECTION, $searchType)){
				if(@$ranges){
					$indexMin=$ranges[Organization::COLLECTION]["indexMin"];
					$indexStep=$ranges[Organization::COLLECTION]["indexMax"]-$ranges[Organization::COLLECTION]["indexMin"];
				}
				$allRes = array_merge($allRes, self::searchOrganizations($query, $indexStep, $indexMin,  $searchType, $searchTypeOrga));
		  	}

		  	date_default_timezone_set('UTC');
					
		  	//*********************************  EVENT   ******************************************
			if(strcmp($filter, Event::COLLECTION) != 0 && self::typeWanted(Event::COLLECTION, $searchType)){
				$searchAll=false;
				if(@$ranges){
					$indexMin=$ranges[Event::COLLECTION]["indexMin"];
					$indexStep=$ranges[Event::COLLECTION]["indexMax"]-$ranges[Event::COLLECTION]["indexMin"];
				}
				
	       		if($search != "")
	       			$searchAll=true;
				$allRes = array_merge($allRes, self::searchEvents($queryEvents, $indexStep, $indexMin, $searchOnAll, $searchAll));
		  	}
		  	//*********************************  PROJECTS   ******************************************
			if(strcmp($filter, Project::COLLECTION) != 0 && self::typeWanted(Project::COLLECTION, $searchType)){
				if(@$ranges){
					$indexMin=$ranges[Project::COLLECTION]["indexMin"];
					$indexStep=$ranges[Project::COLLECTION]["indexMax"]-$ranges[Project::COLLECTION]["indexMin"];
				}
				$allRes = array_merge($allRes, self::searchProject($query, $indexStep, $indexMin));
		  	}
			//*********************************  CLASSIFIED   ******************************************
			if(strcmp($filter, Classified::COLLECTION) != 0 && self::typeWanted(Classified::COLLECTION, $searchType)){
				//var_dump($query) ; exit;
				if(@$ranges){
					$indexMin=$ranges[Classified::COLLECTION]["indexMin"];
					$indexStep=$ranges[Classified::COLLECTION]["indexMax"]-$ranges[Classified::COLLECTION]["indexMin"];
				}
				if(!empty($searchTags) && in_array("favorites", $searchTags))
					$allRes = array_merge($allRes, self::searchFavorites(Classified::COLLECTION));
				else 
					$allRes = array_merge($allRes, self::searchClassified($queryClassifieds, $indexStep, $indexMin, @$priceMin, @$priceMax, @$devise));
		  	}
		  	//*********************************  POI   ******************************************
			if(strcmp($filter, Poi::COLLECTION) != 0 && self::typeWanted(Poi::COLLECTION, $searchType)){
				if(@$ranges){
					$indexMin=$ranges[Poi::COLLECTION]["indexMin"];
					$indexStep=$ranges[Poi::COLLECTION]["indexMax"]-$ranges[Poi::COLLECTION]["indexMin"];
				}
				$allRes = array_merge($allRes, self::searchPoi($query, $indexStep, $indexMin));
		  	}
		  	//*********************************  PRODUCT  ******************************************
	        if(strcmp($filter, Product::COLLECTION) != 0 && self::typeWanted(Product::COLLECTION, $searchType)){
	        	$allRes = array_merge($allRes, self::searchProduct($query, $indexStep, $indexMin));
		  	}
		  	//*********************************  SERVICE  ******************************************
	        if(strcmp($filter, Service::COLLECTION) != 0 && self::typeWanted(Service::COLLECTION, $searchType)){
	        	$allRes = array_merge($allRes, self::searchService($query, $indexStep, $indexMin));
		  	}
		  	//*********************************  SERVICE  ******************************************
	        if(strcmp($filter, Circuit::COLLECTION) != 0 && self::typeWanted(Circuit::COLLECTION, $searchType)){
	        	$allRes = array_merge($allRes, self::searchCircuit($query, $indexStep, $indexMin));
		  	}
		  	//*********************************  PLACE   ******************************************
	        if(strcmp($filter, Place::COLLECTION) != 0 && self::typeWanted(Place::COLLECTION, $searchType)){
	        	if(@$ranges){
					$indexMin=$ranges[Place::COLLECTION]["indexMin"];
					$indexStep=$ranges[Place::COLLECTION]["indexMax"]-$ranges[Place::COLLECTION]["indexMin"];
				}
	        	$allRes = array_merge($allRes, self::searchAny(Place::COLLECTION,$query, $indexStep, $indexMin));
		  	}

		  	//*********************************  RESSOURCE   ******************************************
	        if(strcmp($filter, Ressource::COLLECTION) != 0 && self::typeWanted(Ressource::COLLECTION, $searchType)){
	        	if(@$ranges){
					$indexMin=$ranges[Ressource::COLLECTION]["indexMin"];
					$indexStep=$ranges[Ressource::COLLECTION]["indexMax"]-$ranges[Ressource::COLLECTION]["indexMin"];
				}
	        	$allRes = array_merge($allRes, self::searchAny(Ressource::COLLECTION, $queryRessources, $indexStep, $indexMin));
		  	}

		  	//*********************************  NEWS   ******************************************
		  	if(strcmp($filter, News::COLLECTION) != 0 && self::typeWanted(News::COLLECTION, $searchType)){
		  		if(@$ranges){
					$indexMin=$ranges[News::COLLECTION]["indexMin"];
					$indexStep=$ranges[News::COLLECTION]["indexMax"]-$ranges[News::COLLECTION]["indexMin"];
				}
				$allRes = array_merge($allRes, self::searchNews($queryNews, $indexStep, $indexMin));
		  	}

		  	//*********************************  DDA   ******************************************
			/*if(strcmp($filter, ActionRoom::COLLECTION) != 0 && self::typeWanted(ActionRoom::COLLECTION, $searchType)){
				$allRes = array_merge($allRes, self::searchDDA($query, $indexMax));
		  	}
		  	if(strcmp($filter, ActionRoom::COLLECTION) != 0 && self::typeWanted(ActionRoom::COLLECTION, $searchType)){
				$allRes = array_merge($allRes, self::searchDDA($query, $indexMax));
		  	}*/
		  	

			//*********************************  VOTES / propositions   ******************************************
			//error_log(print_r($searchType)); 
			//error_log("filter : ".$filter);
			if(isset(Yii::app()->session["userId"]) && 
				//(strcmp($filter, ActionRoom::TYPE_VOTE) != 0 && 
					self::typeWanted(ActionRoom::TYPE_VOTE, $searchType) ||
				//(strcmp($filter, ActionRoom::TYPE_ACTIONS) != 0 && 
					self::typeWanted(ActionRoom::TYPE_ACTIONS, $searchType)
				 ){    
				$allRes = array_merge($allRes, self::searchVotes($query, $indexStep, $indexMin, $searchType));
			}

		  	if(@$post['tpl'] == "/pod/nowList"){
		  		usort($allRes, "self::mySortByUpdated");
		  	}
		  	foreach ($allRes as $key => $value) {
		  		if($searchOnAll){
		  			if($value["type"]==News::COLLECTION){
		  				if(@$value["updated"])
							$allRes[$key]["sorting"] = @$value["updated"]->sec;
						else if(@$value["created"])
							$allRes[$key]["sorting"] = @$value["created"]->sec;
		  			}
					else{
						if(is_object(@$value["updated"]))
							$allRes[$key]["sorting"] = @$value["updated"]->sec;
						else
							$allRes[$key]["sorting"] = @$value["updated"];
					}
		  		}
				if(@$value["updated"]) {
					if($initType=="agenda")
						$allRes[$key]["updatedLbl"] = Translate::pastTime(@$value["startDate"],"date");
					else if($value["type"]==News::COLLECTION){
						if(@$value["updated"])
							$allRes[$key]["updatedLbl"] = Translate::pastTime(@$value["updated"]->sec, "timestamp");
						else if(@$value["created"])
							$allRes[$key]["updatedLbl"] = Translate::pastTime(@$value["created"]->sec, "timestamp");
					}
					else
						$allRes[$key]["updatedLbl"] = Translate::pastTime(@$value["updated"],"timestamp");
		  		}
		  	}
		}
	  	//$params["results"]["count"][$_POST["type"]] = PHDB::count( $_POST["type"] , $queryNews);
	  	//print_r($allRes);
	  	//echo $search;
	  	$results["results"]=$allRes;
	  	if($countResult && !empty($countType))
	  		$results["count"]=search::countResultsByCollection($countType, $query, $queryPersons, $queryNews, $queryEvents, $queryClassifieds, $queryRessources);
	  	//$element["gallery"] = Document::listMyDocumentByIdAndType((string)@$element["_id"], @$type);
	  	//var_dump($allRes);
	  	return $results ;
    }

    //*********************** Count search results********************************************************//
    // params countTYpe is an array defining collection searching in modules context
    // params query is array of condition general
    // params queryPersons is array of condition specific for people
    // params queryNews is array of condition specific for news
    // params queryEvents is array of condition specific for events
    public static function countResultsByCollection($countType, $query, $queryPersons, $queryNews, $queryEvents, $queryClassifieds, $queryRessources){
    	$count=array();
    	foreach($countType as $value){
	  			$countQuery=$query;
	  			$col=$value;
	  			if($value==Person::COLLECTION) $countQuery=$queryPersons;
	  			else if($value==News::COLLECTION) $countQuery=$queryNews;
	  			else if($value==Event::COLLECTION) $countQuery=$queryEvents;
	  			else if($value==Classified::COLLECTION) $countQuery=$queryClassifieds;
	  			else if($value==Ressource::COLLECTION) $countQuery=$queryRessources;
	  			else if(in_array($value, ["Group", "NGO", "LocalBusiness", "GovernmentOrganization"])){
          			array_push( $countQuery[ '$and' ], array( "type" => $value ) );
          			$col=Organization::COLLECTION;
	  			}
	  			$count[$value] = PHDB::count( $col , $countQuery);
	  	}
	  	return $count;
    }
    //*********************************  Search   ******************************************
	public static function searchString($search, $query){

        if(strpos($search, "#") > -1){
        	$searchTagText = substr($search, 1, strlen($search)); 
        	$tags=explode("#", $searchTagText);
        	$query = self::searchTags($tags);//array($searchTagText));
  		}else{
  			$searchRegExp = self::accentToRegex($search);
  			$query = array( '$or' => array(
  									array( "name" => new MongoRegex("/.*{$searchRegExp}.*/i")),
  									array( "slug" => new MongoRegex("/.*{$searchRegExp}.*/i")),
  									array( "shortDescription" => new MongoRegex("/.*{$searchRegExp}.*/i"))
  						));
	        $explodeSearchRegExp = explode(" ", $searchRegExp);
	        if(count($explodeSearchRegExp)>1){
		        $andArray=array();
		        foreach($explodeSearchRegExp as $data){
			        array_push($andArray,array("name" => new MongoRegex("/.*{$data}.*/i")));
		        }
		        $query = array('$or' => array($query,array('$and'=> $andArray)));
	        }
  		}
  		return $query;
  		
	}
	//*********************************  Search  in news ******************************************
	public static function searchNewsString($search, $query){
		if(strpos($search, "#") > -1){
        	$searchTagText = substr($search, 1, strlen($search)); 
        	$query = self::searchTags(array($searchTagText));
  		}else{
  			$searchRegExp = self::accentToRegex($search);
  			$query = //array('$or'=>array(
			        	//	array("text" => new MongoRegex("/.*{$searchRegExp}.*/i")),
			        	//	array("media.name" => new MongoRegex("/.*{$searchRegExp}.*/i"))
			        //		)
			        	//);
					array( "text" => new MongoRegex("/.*{$searchRegExp}.*/i"));
  		    $explodeSearchRegExp = explode(" ", $searchRegExp);
	        if(count($explodeSearchRegExp)>1){
		        $andArray=array();
		        foreach($explodeSearchRegExp as $data){
			        array_push($andArray,
			        	array('$or'=>array(
			        		array("text" => new MongoRegex("/.*{$data}.*/i")),
			        		array("media.name" => new MongoRegex("/.*{$data}.*/i"))
			        		)
			        	)
			        );
		        }
		        $query = array('$or' => array($query,array('$and'=> $andArray)));
	        }
  		}
        //$query["type"]="news";
	    //$query["scope"]["type"]="public";
  		return $query;
  		
	}

	//*********************************  TAGS   ******************************************
	public static function searchTags($searchTags, $verb = '$in' ){
		$isString = false;
		$tmpTags = array();
		$query = array();
		if(!empty($searchTags)){
			if(is_array(@$searchTags)){
				foreach ($searchTags as $value) {
					if(trim($value) != ""){
						$value=rtrim(rtrim($value)," ,");
						$tmpTags[] = new MongoRegex("/^".self::accentToRegex($value)."$/i");
					}
				}
			} else {
				$tmpTags[] = new MongoRegex("/^".Search::accentToRegex(@$searchTags)."$/i");							
				$isString = true;
			}

			if(count($tmpTags)){
				$allverb = array('$in', '$all');
				if(!in_array($verb, $allverb))
					$verb = '$in';
				$query = array("tags" => array($verb => $tmpTags)) ;
			}
		}

		if($isString && count($tmpTags))
			$query = array('$and' => array( $query , array("tags" => array('$in' => $tmpTags)))) ;
		
		return $query;
	}

	public static function concatQuery($q1, $q2, $verb){
		if( empty($q1) ){
			$query = $q2 ;
		}else{
			$query = array($verb => array( $q1 , $q2 ) ) ;
		}
		return $query ;
	}

	public static function searchSourceKey($sourceKey, $query){
		$tmpSourceKey = array();
		if($sourceKey != null && $sourceKey != ""){
			//Several Sourcekey
			if(is_array($sourceKey)){
				foreach ($sourceKey as $value) {
					$tmpSourceKey[] = $value;
				}
			}//One Sourcekey
			else{
				$tmpSourceKey[] = $sourceKey;
			}

			if(count($tmpSourceKey)){
				$query = array('$and' => array( $query , array("source.keys" => array('$in' => $tmpSourceKey))));
			}
			unset($tmpSourceKey);
		}
		return $query ;
	}

	//*********************************  Zones   ******************************************
	public static function searchZones($localities){
		$query = array();
		foreach ($localities as $key => $locality){

			$zone = PHDB::findOne( "zones", array("_id"=>new MongoId($locality["id"])));
			//if($zone["level"] == 0){
				$queryLocality = array( /*"address.addressCountry" => $zone["country"],*/
										"address.codeInsee" => $zone["insee"],
										'geoPosition' => array(
											'$geoWithin'  => array(
												'$polygon' => $zone["geoShape"]["coordinates"][0]) ) );
			//}
			if(empty($query))
				$query = $queryLocality;
			else if(!empty($queryLocality))
				$query = array('$or' => array($query ,$queryLocality));

		}
		return $query ;
	}

	//****************************DEFINE LOCALITY QUERY   ***************************************
	//*********************************  ZONES   *************************************************
	//************************ LOCALITY QUERY FOR ELEMENT ****************************************
	public static function searchLocality($localities, $query, $geo=null){
		$allQueryLocality = array();
		if(!empty($localities) && empty($geo)){
			foreach ($localities as $key => $locality){
				if(!empty($locality)){
					if( @$locality["type"] == City::COLLECTION){
						$queryLocality = array("address.localityId" => @$locality["id"]);
						if(!empty($locality["postalCode"]))
							$queryLocality = array_merge($queryLocality, array("address.postalCode" => new MongoRegex("/^".$locality["postalCode"]."/i")));
					}
					else if(@$locality["type"] == "cp"){
						$queryLocality = array("address.postalCode" => new MongoRegex("/^".$locality["name"]."/i"));
						if(!empty($locality["countryCode"]))
							$queryLocality = array_merge($queryLocality, array("address.addressCountry" => $locality["countryCode"]));
					}
					else if(@$locality["type"] == "country"){
						$queryLocality = array("address.addressCountry" => $locality["countryCode"]);
					}
					else
						$queryLocality = array("address.".$locality["type"] => @$locality["id"]);
					
					if(empty($allQueryLocality))
						$allQueryLocality = $queryLocality;
					else if(!empty($queryLocality))
						$allQueryLocality = array('$or' => array($allQueryLocality ,$queryLocality));
				}
			}
		}else if (!empty($geo)){
			$allQueryLocality = SIG::getGeoQuery($geo, "geoPosition.coordinates");
		}
		//modifié le 21/10/2017 by Tango, en espérant que ça ne casse aucun autre process
		//(la query originale était perdu => pb pour les tags)
		if(!empty($allQueryLocality)){
			if(!empty($query)) $query = array('$and' => array( $query , $allQueryLocality ) );
			else $query = array('$and' => array($allQueryLocality));
		}
		return $query ;
	}


	//****************************DEFINE LOCALITY QUERY   ***************************************
	//*********************************  ZONES   *************************************************
	//************************ LOCALITY QUERY FOR ELEMENT ****************************************
	public static function searchLocalityNetwork($localities, $query){
		$allQueryLocality = array();
		if(!empty($localities))
		foreach ($localities as $key => $locality){
			if(!empty($locality)){
				if($locality["type"] == City::COLLECTION){
					$queryLocality = array("address.localityId" => $locality["id"]);
					if(!empty($locality["postalCode"]))
						$queryLocality = array_merge($queryLocality, array("address.postalCode" => new MongoRegex("/^".$locality["postalCode"]."/i")));
				}
				else if($locality["type"] == "cp"){
					$queryLocality = array("address.postalCode" => new MongoRegex("/^".$locality["name"]."/i"));
					if(!empty($locality["countryCode"]))
						$queryLocality = array_merge($queryLocality, array("address.addressCountry" => $locality["countryCode"]));
				}
				else
					$queryLocality = array("address.".$locality["type"] => $locality["id"]);
				
				if(empty($allQueryLocality))
					$allQueryLocality = $queryLocality;
				else if(!empty($queryLocality))
					$allQueryLocality = array('$or' => array($allQueryLocality ,$queryLocality));
			}
		}

		//modifié le 21/10/2017 by Tango, en espérant que ça ne casse aucun autre process
		//(la query originale était perdu => pb pour les tags)
		if(!empty($allQueryLocality)){
			if(!empty($query)) $query = array('$and' => array( $query , $allQueryLocality ) );
			else $query = array('$and' => array($allQueryLocality));
		}
		return $query ;
	}

	//***************************** LOCALITY QUERY FOR NEWS********************************************
	public static function searchLocalityNews($localities, $query){
		$allQueryLocality = array();
		if(!empty($localities)){
  			foreach ($localities as $key => $locality){
				if(!empty($locality)){
					if($locality["type"] == City::COLLECTION){
						$queryLocality = array("scope.localities.parentId" => $locality["id"], "scope.localities.parentType" =>  City::COLLECTION);
						if(!empty($locality["postalCode"]))
							$queryLocality = array_merge($queryLocality, array("scope.localities.postalCode" => new MongoRegex("/^".$locality["postalCode"]."/i")));
					}
					else if($locality["type"] == "cp")
						$queryLocality = array("scope.localities.postalCode" => new MongoRegex("/^".$locality["name"]."/i"));
					else
						$queryLocality =  array('$or'=>array(
									array("scope.localities.parentId" => $locality["id"]),
									array("scope.localities.".$locality["type"] => $locality["id"]))
							);
				
					if(empty($allQueryLocality))
						$allQueryLocality = $queryLocality;
					else if(!empty($queryLocality))
						$allQueryLocality = array('$or' => array($allQueryLocality ,$queryLocality));
				}
			}
  		}
  		//modifié le 21/10/2017 by Tango, en espérant que ça ne casse aucun autre process
		//(la query originale était perdu => pb pour les tags)
		if(!empty($allQueryLocality)){
			if(!empty($query)) $query = array('$and' => array( $query , $allQueryLocality ) );
			else $query = array('$and' => array($allQueryLocality));
		}
		return $query ;
	}
	//*********************************  END DEFINE LOCALITY QUERY   ****************************************

	//*********************************  Specific queries   ****************************************
  	public static function getQueryEvents($queryEvent, $searchSType, $startDate, $endDate){
  		// var_dump($startDate);
  		// echo "<br/>";
  		// var_dump(date("d.m.y H:i:s", $startDate));
  		// echo "<br/>";
  		// var_dump($endDate);
  		// echo "<br/>";
  		// var_dump(date("d.m.y H:i:s", $endDate));
  		// echo "<br/>";
  		// echo "<br/>";
  		/*array('$or'=> array(array("startDate" => array( '$gte' => new MongoDate( (float)$startDate ) ) ),
													array("endDate"=>array('$exists'=>true),"startDate" => array( '$gte' => new MongoDate( (float)$startDate ) ), "endDate" => array( '$lte' => new MongoDate( (float)$startDate ) ) ))) );*/
  		if($startDate!=null){
			if(!empty($endDate))
				array_push( $queryEvent[ '$and' ], array("startDate" => array( '$gte' => new MongoDate( (float)$startDate ) ) ));
			else{
				array_push( $queryEvent[ '$and' ], 
					array('$or'=> array(
						array("startDate" => array( '$gte' => new MongoDate( (float)$startDate ) ) ),
						array("endDate"=>array('$exists'=>true),
							"endDate" => array( '$gte' => new MongoDate( (float)$startDate ) ) ))) );
							//"startDate" => array( '$gte' => new MongoDate( (float)$startDate ) ), 
			}
  		}
		if($endDate!=null)
       		array_push( $queryEvent[ '$and' ], array( "endDate" => array( '$lte' => new MongoDate( (float)$endDate ) ) ) );
  		if(isset($searchSType) && $searchSType != "")
        	array_push( $queryEvent[ '$and' ], array( "type" => $searchSType ) );
        
    	// $queryEvent = array('$and' => 
					// 	array( $queryEvent , 
					// 	array( '$or' => array( 
					// 		array("public" => true ),
					// 		array( '$and' => array(
					// 			array("public" => false ),
					// 			array("links.attendees.".Yii::app()->session["userId"] => array('$exists' => 1) )
					// 			) ) ) ) ) );
  		return $queryEvent;
  	}
  	public static function getQueryClassifieds($query, $searchSType, $section, $category, $subType, $priceMin, $priceMax, $devise){
  		$queryPrice = array('$and' =>	array(array('devise' => $devise)) ) ;	
		//if(@$priceMin) $queryPrice[] = array('price' => array('$gte' => (int)$priceMin));
		//if(@$priceMax) $queryPrice[] = array('price' => array('$lte' => (int)$priceMax));
		//if(@$priceMin || @$priceMax || @$devise) 
		//	$query = array('$and' => array( $query , $queryPrice) );
		if(@$priceMax)
			array_push( $query[ '$and' ], array( 'price' => array('$lte' => (int)$priceMax)) );
		if(@$priceMin)
			array_push( $query[ '$and' ], array( 'price' => array('$gte' => (int)$priceMin)) );
		if(@$devise)
			array_push( $query[ '$and' ], array( 'devise' => $devise) );
		if(isset($searchSType) && $searchSType != "")
        	array_push( $query[ '$and' ], array( "type" => $searchSType ) );
  		if(isset($subType) && $subType != "")
        	array_push( $query[ '$and' ], array( "subtype" => $subType ) );
  		if(isset($category) && $category != "")
        	array_push( $query[ '$and' ], array( "category" => $category ) );
  		if(isset($section) && $section != "")
        	array_push( $query[ '$and' ], array( "section" => $section ) );
  		return $query;

  	}
  	public static function getQueryRessources($query, $searchSType, $subType, $section){
  		if(isset($searchSType) && $searchSType != "")
        	array_push( $query[ '$and' ], array( "type" => $searchSType ) );
  		if(isset($subType) && $subType != "")
        	array_push( $query[ '$and' ], array( "subtype" => $subType ) );
  		if(isset($section) && $section != "")
        	array_push( $query[ '$and' ], array( "section" => $section ) );
  		return $query;
  	}
  	//*********************************  END Specific squeries   ****************************************
  	//trie les éléments dans l'ordre alphabetique par name
  	public static function mySortByName($a, $b){ // error_log("sort : ");//.$a['name']);
  		if(isset($a["_id"]) && isset($b["name"])){
	   		return ( strtolower($b["name"]) < strtolower($a["name"]) );
	    } else{
			return false;
		}
	}
  	
  	//trie les éléments dans l'ordre alphabetique par updated
  	public static function mySortByUpdated($a, $b){ // error_log("sort : ");//.$a['name']);
  		if(isset($a["updated"]) && isset($b["updated"])){
	   		return ( strtolower($b["updated"]) > strtolower($a["updated"]) );
	    } else{
			return false;
		}
	}

	//supprime les accents (utilisé pour la recherche de ville pour améliorer les résultats)
    public static function wd_remove_accents($str, $charset='utf-8')
	{
		return $str;
	    $str = htmlentities($str, ENT_NOQUOTES, $charset);
	    
	    $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
	    $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'
	    $str = preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères
	    
	    return $str;
	}

	public static function getTypeOfLocalisation($locStr){
		//le cas des localisation intégralement numérique (code postal, insee, departement)
		if(intval($locStr) > 0){
			if(strlen($locStr) <= 3) return "DEPARTEMENT";
			if(strlen($locStr) == 4 || strlen($locStr) == 5) return "CODE_POSTAL_INSEE";
			return "UNDEFINED";
		}else{
			//le cas où le lieu est demandé en toute lettre
			return "NAME";
		}
	}

	public static function typeWanted($type, $searchType){
		if($searchType == null) return false;
		if($searchType[0] == "all") return true;
		return in_array($type, $searchType);
	}



	public static function checkScopeParent($parentObj){ //error_log("checkScopeParent");
		$localityReferences['CITYKEY'] = "";
  		$localityReferences['CODE_POSTAL'] = "address.postalCode";
  		$localityReferences['DEPARTEMENT'] = "address.postalCode";
  		$localityReferences['REGION'] = ""; //Spécifique

  		$countScope = 0;
  		foreach ($localityReferences as $key => $value){
  			if(isset($_POST["searchLocality".$key]) && count($_POST["searchLocality".$key])>0 && $_POST["searchLocality".$key][0] != "" ){ 
  				$countScope++; 
  			}
  		}
  		if($countScope==0){ //error_log("return true EMPTY"); 
  			return true; }
  		
		foreach ($localityReferences as $key => $value) 
  		{

  			if(isset($_POST["searchLocality".$key]) 
  				&& is_array($_POST["searchLocality".$key])
  				&& count($_POST["searchLocality".$key])>0)
  			{
  				foreach ($_POST["searchLocality".$key] as $localityRef) 
  				{
  					if(isset($localityRef) && $localityRef != ""){
	  					//OneRegion
	  					if($key == "CITYKEY"){
	  						
	  						$city = City::getByUnikey($localityRef);
			        		if (empty($city["cp"])) {
				        		if(@$parentObj["address"]["addressCountry"] == $city["country"] &&
				        		   @$parentObj["address"]["codeInsee"] == $city["insee"]) return true;
			        		}else{
			        			if(@$parentObj["address"]["addressCountry"] == $city["country"] &&
				        		   @$parentObj["address"]["codeInsee"] == $city["insee"] &&
				        		   @$parentObj["address"]["postalCode"] == $city["cp"]) return true;
			        		}
			        		
		  				}
		  				elseif($key == "CODE_POSTAL"){
		  					if(@$parentObj["address"]["postalCode"] == $localityRef) return true;
			        		//$queryLocality = array($value => new MongoRegex("/".$localityRef."/i"));
		  				}
		  				elseif($key == "DEPARTEMENT"){
		  					$dep = PHDB::findOne( City::COLLECTION, array("depName" => $localityRef), array("dep"));	
		        			if(preg_match("/^{$dep['dep']}/i", @$parentObj["address"]["postalCode"])) return true;
		  				}
		  				elseif($key == "REGION"){
		  					$deps = PHDB::find( City::COLLECTION, array("regionName" => $localityRef), array("dep"));
		        			$departements = array();
		        			$inQuest = array();
		        			if(is_array($deps))foreach($deps as $index => $value)
		        			{
		        				if(!in_array($value["dep"], $departements)){
			        				$departements[] = $value["dep"];
			        				if(preg_match("/^{$value['dep']}/i", @$parentObj["address"]["postalCode"])) return true;
						        }
		        			}		        		
		  				}
		  			}
  				}
  			}
  		}
  		return false;
	}

	public static function checkTagsParent($parentObj, $tags){ //return true;
		if(count($tags)<=0) return true;
		foreach ($tags as $key => $tag) { error_log("checkTagsParent tag : " .$tag);
			if(@$parentObj["tags"]){
				foreach ($parentObj["tags"] as $key => $parentTag) { error_log("checkTagsParent parentTag : " .$parentTag);
					if(preg_match("/.*{$tag}.*/i", $parentTag)){
						error_log("checkTagsParent return true");
						return true;
					}
				}
			}
		}error_log("checkTagsParent return false");
						
		return false;
	}

	//*********************************  PERSONS   ******************************************
  	public static function searchPersons($query, $indexStep, $indexMin, $prefLocality=false){
       	$res = array();
       	$query = array('$and' => array( $query , array("roles.tobeactivated" => array('$exists' => 0)))) ;

       	$allCitoyen = PHDB::findAndSortAndLimitAndIndex ( Person::COLLECTION , $query, 
  										  array("updated" => -1), $indexStep, $indexMin);
       	//print_r($allCitoyen);

  		foreach ($allCitoyen as $key => $value) {

  			//if( $prefLocality == false ||  
  			//	Preference::showPreference($value, Person::COLLECTION, "locality", Yii::app()->session["userId"])){
  				$person = Person::getSimpleUserById($key,$value);
	  			$person["type"] = Person::COLLECTION;
				$person["typeSig"] = "citoyens";
				if( @$value["links"]["followers"][Yii::app()->session["userId"]] )
		  			$person["isFollowed"] = true;
				$res[$key] = $person;
  			//}


  			
  		}
  		return $res;
	}


	//*********************************  ORGANIZATIONS   ******************************************
 //  	public static function searchOrganizations($query, $indexStep, $indexMin, $searchType, $searchTypeOrga){
 //       	$res = array();
 //    	$queryOrganization = $query;
 //    	if( !isset( $queryOrganization['$and'] ) ) 
 //    		$queryOrganization['$and'] = array();

 //    	var_dump($queryOrganization);
 //    	array_push( $queryOrganization[ '$and' ], array( "disabled" => array('$exists' => false) ) );
	// }

	//*********************************  ORGANIZATIONS   ******************************************
  	public static function searchOrganizations($query, $indexStep, $indexMin, $searchType, $searchTypeOrga){
       	$res = array();
		$queryOrganization = $query;
		if( !isset( $queryOrganization['$and'] ) ) 
			$queryOrganization['$and'] = array();
		array_push( $queryOrganization[ '$and' ], array( "disabled" => array('$exists' => false) ) );
		if(sizeof($searchType)==1 && @$searchTypeOrga != "")
			array_push( $queryOrganization[ '$and' ], array( "type" => $searchTypeOrga ) );

  		$allOrganizations = PHDB::findAndSortAndLimitAndIndex ( Organization::COLLECTION ,$queryOrganization, 
  												array("updated" => -1), $indexStep, $indexMin);

  		foreach ($allOrganizations as $key => $value) 
  		{
  			if(!empty($value)){
	  			$orga = Organization::getSimpleOrganizationById($key,$value);
	  			if( @$value["links"]["followers"][Yii::app()->session["userId"]] )
		  			$orga["isFollowed"] = true;
		  		if(@$orga["type"] != "")
					$orga["typeOrga"] = $orga["type"];
				$orga["type"] = "organizations";
				$orga["typeSig"] = Organization::COLLECTION;
				$res[$key] = $orga;
			}
  		}
  		return $res;
	}
	//*********************************  NEWS   ******************************************
  	public static function searchNews($query, $indexStep, $indexMin){
       	$res = array();
  		$allNews = PHDB::findAndSortAndLimitAndIndex ( News::COLLECTION ,$query, 
  												array("sharedBy.updated"=>-1), $indexStep, $indexMin);
  		//print_r($allNews);
  		foreach ($allNews as $key => $value){
  			if(!empty($value)){
				$value=NewsTranslator::convertParamsForNews($value, false);			 
				$value["type"] = News::COLLECTION;
				$value["typeSig"] = News::COLLECTION;
				$res[$key]=$value;
			}
  		}
  		return $res;
	}
	

	//*********************************  EVENT   ******************************************
	public static function searchEvents($query, $indexStep, $indexMin, $searchOnAll=null, $all=null){
		date_default_timezone_set('UTC');
		$queryEvent = $query;

    	if($searchOnAll)
    		$sort=array("updated" => -1);
    	else if(@$all && $all)
    		$sort=array("startDate" => -1);
    	else
    		$sort=array("startDate" => 1);
		$allEvents = PHDB::findAndSortAndLimitAndIndex( PHType::TYPE_EVENTS, $queryEvent, 
  										$sort , $indexStep, $indexMin);
  		foreach ($allEvents as $key => $value) {
  			$allEvents[$key]["typeEvent"] = @$allEvents[$key]["type"];
			$allEvents[$key]["type"] = "events";
			$allEvents[$key]["typeSig"] = Event::COLLECTION;
			if(@$value["links"]["attendees"][Yii::app()->session["userId"]]){
	  			$allEvents[$key]["isFollowed"] = true;
  			}
			if(@$allEvents[$key]["startDate"] && @$allEvents[$key]["startDate"]->sec){
				$allEvents[$key]["startDateTime"] = date(DateTime::ISO8601, $allEvents[$key]["startDate"]->sec);
				$allEvents[$key]["startDate"] = date(DateTime::ISO8601, $allEvents[$key]["startDate"]->sec);
			}
			if(@$allEvents[$key]["endDate"] && @$allEvents[$key]["endDate"]->sec){
				$allEvents[$key]["endDateTime"] = date(DateTime::ISO8601, $allEvents[$key]["endDate"]->sec);
				$allEvents[$key]["endDate"] = date(DateTime::ISO8601, $allEvents[$key]["endDate"]->sec);
			}
			if(@$allEvents[$key]["organizerId"] &&
			   @$allEvents[$key]["organizerType"] &&
			   @$allEvents[$key]["organizerId"] != "dontKnow"){ 

				$allEvents[$key]["organizerObj"] = 
				Element::getElementById(@$allEvents[$key]["organizerId"], @$allEvents[$key]["organizerType"]);
				$allEvents[$key]["organizerObj"]["type"] = @$allEvents[$key]["organizerType"];
			}

			$el = $value;
			if(@$el["links"]) 
			foreach(array("attendees") as $k) 
				if(@$value["links"][$k])
				$allEvents[$key]["counts"][$k] = count(@$value["links"][$k]);
  		}
  		return $allEvents;
	  	
	}


	//*********************************  PROJECTS   ******************************************
	public static function searchProject($query, $indexStep, $indexMin){
		date_default_timezone_set('UTC');
		$allProject = PHDB::findAndSortAndLimitAndIndex(Project::COLLECTION, $query, 
	  												array("updated" => -1), $indexStep, $indexMin);
  		foreach ($allProject as $key => $value) {
  			if(@$project["links"]["followers"][Yii::app()->session["userId"]]){
	  			$allProject[$key]["isFollowed"] = true;
  			}
			$allProject[$key]["type"] = Project::COLLECTION;
			$allProject[$key]["typeSig"] = Project::COLLECTION;
			
			if(@$allProject[$key]["startDate"])
				$allProject[$key]["startDate"] = date('Y-m-d H:i:s', @$allProject[$key]["startDate"]->sec);
			if(@$allProject[$key]["endDate"])
				$allProject[$key]["endDate"] = date('Y-m-d H:i:s', @$allProject[$key]["endDate"]->sec);


			$el = $value;
			if(@$el["links"]) foreach(array("contributors", "followers") as $k)
				if(@$el["links"][$k])
				$allProject[$key]["counts"][$k] = count($el["links"][$k]);
  		}
  		return $allProject;	
	}

	//*********************************  CLASSIFIED   ******************************************
	public static function searchClassified($query, $indexStep, $indexMin, $priceMin, $priceMax, $devise){
		
		$allClassified = PHDB::findAndSortAndLimitAndIndex(Classified::COLLECTION, $query, 
	  												array("updated" => -1), $indexStep, $indexMin);

  		foreach ($allClassified as $key => $value) {
			if(@$value["parentId"] && @$value["parentType"])
				$parent = Element::getElementSimpleById(@$value["parentId"], @$value["parentType"]);
			else
				$parent=array();
			$allClassified[$key]["parent"] = $parent;
			$allClassified[$key]["typeClassified"] = @$value["type"];
			$allClassified[$key]["type"] = "classifieds";

			$allClassified[$key]["gallery"] = Document::listMyDocumentByIdAndType(@$key, "classifieds");
			//if(@$value["type"])
			//	$allClassified[$key]["typeSig"] = Classified::COLLECTION.".".$value["type"];
			//else
			$allClassified[$key]["typeSig"] = Classified::COLLECTION;
		}
		return $allClassified;
	}

	//*********************************  CLASSIFIED   ******************************************
	public static function searchFavorites($type){

		$person = Person::getById(Yii::app()->session["userId"]);
		$res = array();

		if( @$person["collections"] && @$person["collections"]["favorites"] && @$person["collections"]["favorites"][$type] ){
			foreach ($person["collections"]["favorites"][$type] as $key => $value) {
				$el = PHDB::findOne($type, array("_id" => new MongoId($key)) );
				$el["type"] = $type;
				$el["typeSig"] = $type;
				$res[$key] = $el;
			}
		}
		return $res;
	}

	//*********************************  POI   ******************************************
	public static function searchPoi($query, $indexStep, $indexMin){
		//var_dump($query); exit;
		
    	$allPoi = PHDB::findAndSortAndLimitAndIndex(Poi::COLLECTION, $query, 
  												array("updated" => -1), $indexStep, $indexMin);

  		//var_dump($query); exit;
    	foreach ($allPoi as $key => $value) {
	  		if(@$value["parentId"] && @$value["parentType"])
	  			$parent = Element::getElementSimpleById(@$value["parentId"], @$value["parentType"]);
	  		else
	  			$parent=array();
			$allPoi[$key]["parent"] = $parent;
			if(@$value["type"])
				$allPoi[$key]["typeSig"] = Poi::COLLECTION;//.".".$value["type"];
			else
				$allPoi[$key]["typeSig"] = Poi::COLLECTION;
			
			$allPoi[$key]["typePoi"] = @$allPoi[$key]["type"];
			$allPoi[$key]["type"] = Poi::COLLECTION;
  		}
  		return $allPoi;
  	}
	//*********************************  PRODUCT   ******************************************
	public static function searchProduct($query, $indexStep, $indexMin){
    	$allProduct = PHDB::findAndSortAndLimitAndIndex(Product::COLLECTION, $query, 
  												array("updated" => -1), $indexStep, $indexMin);
  		foreach ($allProduct as $key => $value) {
	  		if(@$value["parentId"] && @$value["parentType"])
	  			$parent = Element::getElementSimpleById(@$value["parentId"], @$value["parentType"]);
	  		else
	  			$parent=array();
			$allProduct[$key]["parent"] = $parent;
			if(@$value["type"])
				$allProduct[$key]["typeSig"] = Product::COLLECTION;//.".".$value["type"];
			else
				$allProduct[$key]["typeSig"] = Product::COLLECTION;
			
			$allProduct[$key]["typePoi"] = @$allProduct[$key]["type"];
			$allProduct[$key]["type"] = Product::COLLECTION;
  		}
  		return $allProduct;
  	}
  	//*********************************  SERVICE   ******************************************
	public static function searchService($query, $indexStep, $indexMin){
		$allService = PHDB::findAndSortAndLimitAndIndex(Service::COLLECTION, $query, 
  												array("updated" => -1), $indexStep, $indexMin);
		
  		foreach ($allService as $key => $value) {
	  		if(@$value["parentId"] && @$value["parentType"])
	  			$parent = Element::getElementSimpleById(@$value["parentId"], @$value["parentType"]);
	  		else
	  			$parent=array();
			$allService[$key]["parent"] = $parent;
			// if(@$value["type"])
			// 	$allService[$key]["typeSig"] = Service::COLLECTION.".".@$allService[$key]["type"];//.".".$value["type"];
			// else
			$allService[$key]["typeSig"] = Service::COLLECTION.".".@$allService[$key]["type"];

			$allService[$key]["typePoi"] = @$allProduct[$key]["type"];
			$allService[$key]["type"] = Service::COLLECTION;
		}
		return $allService;
	}	
	 //*********************************  CIRCUIT   ****************************************** 
    public static function searchCircuit($query, $indexStep, $indexMin){ 
    $allCircuit = PHDB::findAndSortAndLimitAndIndex(Circuit::COLLECTION, $query,  
                          array("updated" => -1), $indexStep, $indexMin); 
      foreach ($allCircuit as $key => $value) { 
        if(@$value["parentId"] && @$value["parentType"]) 
          $parent = Element::getElementSimpleById(@$value["parentId"], @$value["parentType"]); 
        else 
          $parent=array(); 
      $allCircuit[$key]["parent"] = $parent; 
      if(@$value["type"]) 
        $allCircuit[$key]["typeSig"] = Circuit::COLLECTION.".".@$allCircuit[$key]["type"];//.".".$value["type"]; 
      else 
        $allCircuit[$key]["typeSig"] = Circuit::COLLECTION.".".@$allCircuit[$key]["type"]; 
       
 
      $allCircuit[$key]["typePoi"] = @$allCircuit[$key]["type"]; 
      $allCircuit[$key]["type"] = Circuit::COLLECTION; 
      } 
      return $allCircuit; 
    } 
  	//*********************************  Generic Search   ******************************************
	public static function searchAny($collection, $query, $indexStep, $indexMin){
    	$allPlace = PHDB::findAndSortAndLimitAndIndex($collection, $query, 
  												array("updated" => -1), $indexStep, $indexMin);
  		foreach ($allPlace as $key => $value) {
	  		if(@$value["parentId"] && @$value["parentType"])
	  			$parent = Element::getElementSimpleById(@$value["parentId"], @$value["parentType"]);
	  		else
	  			$parent=array();
			$allPlace[$key]["parent"] = $parent;
			if($collection==Ressource::COLLECTION)
				$allPlace[$key]["category"] = @$value["type"];
			if(@$value["type"])
				$allPlace[$key]["typeComplet"] = $collection.".".$value["type"];
			else
				$allPlace[$key]["typeSig"] = $collection;
			$allPlace[$key]["type"] = $collection;
			$allPlace[$key]["typeSig"] = $collection;
  		}
  		return $allPlace;
  	}

  	//*********************************  DDA   ******************************************
  	public static function searchDDA($query, $indexMax){
  		$allRes = array();
        $queryDiscuss = $query;
    	if( !isset( $queryDiscuss['$and'] ) ) 
    		$queryDiscuss['$and'] = array();
    	array_push( $queryDiscuss[ '$and' ], array( "type" => ActionRoom::TYPE_DISCUSS ) );
    	$allFound = PHDB::findAndSort(ActionRoom::COLLECTION, $queryDiscuss, array("updated" => -1), $indexMax);
  		foreach ($allFound as $key => $value) {
			$allFound[$key]["type"] = $value["type"];
			$allFound[$key]["typeSig"] = ActionRoom::COLLECTION;
  		}
  		$allRes = array_merge($allRes, $allFound);
  		
    	$allFound = PHDB::findAndSort( ActionRoom::COLLECTION_ACTIONS, $query, array("updated" => -1), $indexMax);
  		foreach ($allFound as $key => $value) {
			$allFound[$key]["type"] = @$value["type"];
			$allFound[$key]["typeSig"] = ActionRoom::COLLECTION_ACTIONS;
  		}
  		$allRes = array_merge($allRes, $allFound);

    	$allFound = PHDB::findAndSort( Survey::COLLECTION, $query, array("updated" => -1), $indexMax);
  		foreach ($allFound as $key => $value) {
			$allFound[$key]["type"] = $value["type"];
			$allFound[$key]["typeSig"] = Survey::CONTROLLER;
  		}
  		$allRes = array_merge($allRes, $allFound);
	  	return $allRes;
	}

	//*********************************  VOTES / propositions   ******************************************
    public static function searchVotes($query, $indexStep, $indexMin, $searchType){
    	$allFound = array();
    	if(!empty(Yii::app()->session["userId"])){
	    	// //rajoute les résultats pour mon conseil citoyen
	    	$me = Person::getSimpleUserById(Yii::app()->session["userId"]);
	    	$myCityKey = @$me["address"]["addressCountry"] ? $me["address"]["addressCountry"] : false;
	    	if($myCityKey!=false){
	    		$myCityKey .= @$me["address"]["codeInsee"] ? "_".$me["address"]["codeInsee"] : false;
	    		if($myCityKey!=false){
	    			$myCityKey .= @$me["address"]["postalCode"] ? "-".$me["address"]["postalCode"] : "";
	        		//$query['$or'][] = array("parentType"=>"cities", "parentId" => $myCityKey);
	        	}
	    	}
	    	
	    	$query = array();

	    	$allRooms = array(); //PHDB::find( ActionRoom::COLLECTION, $query);
	    	//var_dump($allRooms); exit;

	    	//crée une array avec uniquement les id des rooms
	    	$allRoomsId = array();
	    	foreach ($allRooms as $key => $room) {
	    		$allRoomsId[] = (string)$room["_id"];
	    	}

	    	if(self::typeWanted( ActionRoom::TYPE_VOTE, $searchType)){
				$collection = Survey::COLLECTION;
				$parentRow = "survey";
			}
	    	if(self::typeWanted( ActionRoom::TYPE_ACTIONS, $searchType)){ 
	    		$collection = Action::NODE_ACTIONS;
	    		$parentRow = "room";
	    	}

	    	$query = array();//$parentRow => array('$in' => $allRoomsId) );
	    	
	    	//if(count($tmpTags))
	    	//$query = array('$and' => array( $query , array("tags" => array('$in' => $tmpTags)))) ;
	    	
	    	//echo "search : ". $search." - ".(string)strpos($search, "#");
	    	if(@$search != "" && strpos(@$search, "#") === false){
	        	$searchRegExp = self::accentToRegex($search);
	        	$queryFullTxt = array( '$or' => array( array("name" => new MongoRegex("/.*{$searchRegExp}.*/i")),
	        						   				   array("message" => new MongoRegex("/.*{$searchRegExp}.*/i")))
	        						);
	        	if(isset($query['$and'])) $query['$and'][] = $queryFullTxt;
	        	else $query = array('$and' => array( $query , $queryFullTxt)) ;
	        }

			//var_dump($query); exit;

	        $allFound = PHDB::findAndSortAndLimitAndIndex($collection, $query, array("updated" => -1), $indexStep, $indexMin);

	    	foreach ($allRooms as $keyR => $room) {
	    		//pour chaque room des orga, on ajoute quelques info sur le parentObj
	    		if(@$myLinks)
	    		foreach ($myLinks["organizations"] as $keyL => $orga) {
	    			//error_log("orga " . (string)$orga['_id'] ."==". (string)$room['parentId']);
	    			if((string)$orga['_id'] == (string)$room['parentId'] && $room['parentType'] == "organizations"){
	    				$allRooms[$keyR]["parentObj"]["_id"] 	 = $orga["_id"];
	    				$allRooms[$keyR]["parentObj"]["name"] 	 = $orga["name"];
	    				$allRooms[$keyR]["parentObj"]["address"] = @$orga["address"];
	    				$allRooms[$keyR]["parentObj"]["typeSig"] = $orga["typeSig"];
	    				break;
	    			}
	    		}

	    		//pour chaque room des projets, on ajoute les infos du parentObj
	    		if(@$myLinks)
	    		foreach ($myLinks["projects"] as $keyL => $project) {
	    			//error_log("project " . (string)$project['_id'] ."==". (string)$room['parentId']);
	    			if((string)$project['_id'] == (string)$room['parentId'] && $room['parentType'] == "projects"){
	    				$allRooms[$keyR]["parentObj"]["_id"] 	 = $project["_id"];
	    				$allRooms[$keyR]["parentObj"]["name"] 	 = $project["name"];
	    				$allRooms[$keyR]["parentObj"]["address"] = @$project["address"];
	    				$allRooms[$keyR]["parentObj"]["typeSig"] = $project["typeSig"];
	    				break;
	    			}
	    		}

	    		//les conseils citoyens
	    		if($myCityKey!=false && $room["parentType"] == "cities"){
	    			$myCity = City::getByUnikey($myCityKey); 			
					$cityCheck["name"] = $myCity["name"];
					$cityCheck["address"] = array("postalCode" => $myCity["cp"], "countryCode" => $myCity["country"]);
					$cityCheck["codeInsee"] = $myCity["insee"];
					$cityCheck["geo"] = $myCity["geo"];
					$cityCheck["typeSig"] = "city";

					if(self::checkScopeParent($cityCheck) == true){
	    				$allRooms[$keyR]["parentObj"]["name"] = $cityCheck["name"];
	    				$allRooms[$keyR]["parentObj"]["address"] = @$cityCheck["address"];
	    				$allRooms[$keyR]["parentObj"]["address"]["addressLocality"] = @$cityCheck["name"];
	    				$allRooms[$keyR]["parentObj"]["address"]["addressCountry"] = @$cityCheck["address"]["countryCode"];
	    				$allRooms[$keyR]["parentObj"]["typeSig"] = $cityCheck["typeSig"];
	    				$allRooms[$keyR]["geo"] = @$cityCheck["geo"];
	    			}else{
	    				$allRooms[$keyR] = array();
	    			}
	    		}

	    		$allRooms[$keyR]["typeSig"] = @$allRooms[$keyR]["type"];
	    	}
	    	
	    	//pour chaque resultat, on ajoute les infos du parentRoom
	    	foreach ($allFound as $keyS => $survey) {
	    		foreach ($allRooms as $keyR => $room) {
	    			if((string)$survey[$parentRow] == (string)@$room['_id']){
	    				$allFound[$keyS]["parentRoom"] = $room;
	    				$allFound[$keyS]["geo"] = @$room["geo"];
	    				if($room["parentType"] == "cities")
	    					$allFound[$keyS]["address"] = @$room["parentObj"]["address"];
	    				break;
	    			}else if(!isset($room['_id'])){
	    				unset($allFound[$keyS]);
	    				//$allFound[$keyS] = array();
	    				//break;
	    			}
	    		}
	    		
	    		if(@$allFound[$keyS]["dateEnd"]) $allFound[$keyS]["dateEnd"] =  date("Y-m-d H:i:s", $allFound[$keyS]["dateEnd"]);
				if(@$allFound[$keyS]["endDate"]) $allFound[$keyS]["endDate"] =  date("Y-m-d H:i:s", $allFound[$keyS]["endDate"]);
				if(@$allFound[$keyS]["startDate"]) $allFound[$keyS]["startDate"] =  date("Y-m-d H:i:s", $allFound[$keyS]["startDate"]);
				if(@$allFound[$keyS]["created"]) $allFound[$keyS]["created"] =  date("Y-m-d H:i:s", $allFound[$keyS]["created"]);
				
	    	}
    	}   
    		
    	
    	
    	return $allFound;
    }


    //*********************************  CITIES   ******************************************
    public static function searchCities($search, $locality, $country){

  		//$query = array( "name" => new MongoRegex("/".self::wd_remove_accents($search)."/i"));//array('$text' => array('$search' => $search));//


  		$query = array( "name" => new MongoRegex("/".self::wd_remove_accents($search)."/i"));
  		
  		//*********************************  DEFINE LOCALITY QUERY   ******************************************
    	if($locality == null || $locality == "")
    		$locality = $search;
    	
    	$type = self::getTypeOfLocalisation($locality);

		if($type == "NAME"){ 
    		$query = array('$or' => array( array( "name" => new MongoRegex("/".self::wd_remove_accents($locality)."/i")),
    									   array( "alternateName" => new MongoRegex("/".self::wd_remove_accents($locality)."/i")),
    									   array( "postalCodes.name" => array('$in' => array(new MongoRegex("/".self::wd_remove_accents($locality)."/i"))))
    					));
    	}
    	if($type == "CODE_POSTAL_INSEE") {
    		$query = array("postalCodes.postalCode" => array('$in' => array($locality)));
    	}
    	if($type == "DEPARTEMENT") {
    		$query = array("dep" => $locality );
    	}
    	if($type == "INSEE") {
    		$query = array("insee" => $locality );
    	}
	    //}

	    if($country != ""){
	    	$query["country"] = $country;
	    }
	    //var_dump($query);
  		$allCities = PHDB::find(City::COLLECTION, $query);
  		$allCitiesRes = array();
  		$nbMaxCities = 20;
  		$nbCities = 0;
  		foreach($allCities as $data){
  			if(!empty($data["postalCodes"])){
  				$countPostalCodeByInsee = count($data["postalCodes"]);
		  		foreach ($data["postalCodes"] as $val){
			  		if($nbCities < $nbMaxCities){
			  		$newCity = array();
			  		//$regionName = 
			  		$newCity = array(
			  						"_id"=>$data["_id"],
			  						"id"=>(String) $data["_id"],
			  						"insee" => $data["insee"], 
			  						// "regionName" => isset($data["regionName"]) ? $data["regionName"] : "", 
			  						// "depName" => isset($data["depName"]) ? $data["depName"] : "",
			  						"level1" => isset($data["level1"]) ? $data["level1"] : "",
			  						"level1Name" => isset($data["level1Name"]) ? $data["level1Name"] : "",
			  						"country" => $data["country"],
			  						"geoShape" => isset($data["geoShape"]) ? $data["geoShape"] : "",
			  						"cp" => $val["postalCode"],
			  						"postalCodes" => $data["postalCodes"],
			  						"geo" => $val["geo"],
			  						"geoPosition" => $val["geoPosition"],
			  						"name" => ucwords(strtolower($val["name"])),
			  						"cityName" => $val["name"],
			  						"alternateName" => ucwords(strtolower($val["name"])),
			  						"type"=>"city",
			  						"typeSig" => "city");

			  		//var_dump(count($newCity["postalCodes"]));
			  		if(!empty($newCity["postalCodes"]) && count($newCity["postalCodes"]) > 1){
						foreach ($newCity["postalCodes"] as $key => $v) {
		                    $citiesNames[] = $v["name"];
		                }
			            $newCity["cities"] = $citiesNames;
			            
			  		}else if(is_string($newCity["cp"]) && strlen($newCity["cp"]) > 0){
						if($newCity["cp"]){
			                $where = array("postalCodes.postalCode" =>new MongoRegex("/^".$newCity["cp"]."/i"),
			            					"country" => $newCity["country"]);
			                $citiesResult = PHDB::find( City::COLLECTION , $where, array("_id") );
			                $citiesNames=array();
			                foreach ($citiesResult as $key => $v) {
			                    $citiesNames[] = City::getNameCity($key);
			                }
			                $newCity["cities"] = $citiesNames;
			            }
			  		}

			  		if(!empty($data["level4"])){
						 $newCity["level4"] = $data["level4"];
						 $newCity["level4Name"] = $data["level4Name"];
			  		}
					
					if(!empty($data["level3"])){
						 $newCity["level3"] = $data["level3"];
						 $newCity["level3Name"] = $data["level3Name"];
			  		}

			  		if(!empty($data["level2"])){
						 $newCity["level2"] = $data["level2"];
						 $newCity["level2Name"] = $data["level2Name"];
			  		}


			  		if($countPostalCodeByInsee > 1){
			  			$newCity["countCpByInsee"] = $countPostalCodeByInsee;
			  			$newCity["cityInsee"] = ucwords(strtolower($data["alternateName"]));
			  		}
			  		$allCitiesRes[(String) $data["_id"]]=$newCity;
			  		} $nbCities++;
		  		}
  			}else{
  				$data["type"]="city";
			  	$data["typeSig"] = "city";
	  			$allCitiesRes[(String) $data["_id"]]=$data;
  			}
  		}
  		//var_dump($allCitiesRes);
  		if(empty($allCitiesRes)){
  			$query = array( "cp" => $search);
	  		$allCities = PHDB::find(City::COLLECTION, $query, array("name", "cp", "insee", "geo", "geoShape"));
	  		$nbCities = 0;
  			foreach ($allCities as $key => $value) {
	  			if($nbCities < $nbMaxCities){
		  			$city = City::getSimpleCityById($key);
					$city["type"] = "city";
					$city["typeSig"] = "city";
					$allCitiesRes[(String) $city["_id"]] = $city;
				} $nbCities++;
	  		}  		
  		}
  		//var_dump($allCitiesRes);
  		return $allCitiesRes;
  	}

    static public function removeEmptyWords($search){
        $stopwords = array(" ", "", "-", "?", "!", ",", ".", "/", "le", "la", "les", "un", "une", "des", "mon", "ton", "son", "pour", 
                            "à", "a", "d", "d'", "de", "notre", "votre", "leur", "leurs", "mes", "tes", "ses", "du");

        $arraySearch = explode(" ", $search);
        $resArraySearch = array();
        foreach ($arraySearch as $key => $word) {
            if(!in_array($word, $stopwords)){
                $resArraySearch[] = $word;
            }
        }

        $resStr = "";
        foreach ($resArraySearch as $key => $word) {
            if($resStr != "") $resStr .= " ";
            $resStr .= $word;
        }
        return $resStr;
    }


    /***********************************  DEFINE LOCALITY QUERY   ***************************************/
    public static function searchLocalityNetworkOld($query, $post){
  		$localityReferences['NAME'] = "address.addressLocality";
  		$localityReferences['CODE_POSTAL_INSEE'] = "address.postalCode";
  		$localityReferences['DEPARTEMENT'] = "address.postalCode";
  		$localityReferences['REGION'] = ""; //Spécifique
  		$localityReferences['INSEE'] = "address.codeInsee";

  		foreach ($localityReferences as $key => $value) {
  			if(isset($post["searchLocality".$key]) && is_array($post["searchLocality".$key])){
  				foreach ($post["searchLocality".$key] as $locality) {

  					//OneRegion

  					if($key == "REGION") {
  						if($locality == "La Réunion")
  							$locality = "Réunion" ;
	        			$dep = PHDB::findOne( City::COLLECTION, array("level3Name" => $locality), array("level3"));
	        			if(!empty($dep))
	        				$queryLocality = array("address.level3" => $dep["level3"]);
	        		}else if($key == "DEPARTEMENT") {
	        			$dep = PHDB::findOne( City::COLLECTION, array("level4Name" => $locality), array("level4"));
	        			if(!empty($dep))
		        			$queryLocality = array("address.level4" => $dep["level4"]);
		        	}//OneLocality
		        	else{
	  					$queryLocality = array($value => new MongoRegex("/".$locality."/i"));
	  				}

  					//Consolidate Queries
  					if(!empty($queryLocality)) {
	  					if(isset($allQueryLocality)){
	  						$allQueryLocality = array('$or' => array( $allQueryLocality ,$queryLocality));
	  					}else{
	  						$allQueryLocality = $queryLocality;
	  					}
	  				}
	  				unset($queryLocality);

  				}
  			}
  		}
  		if(isset($allQueryLocality) && is_array($allQueryLocality))
  			$query = array('$and' => array($query, $allQueryLocality));
  		return $query; 
  	}
}