<?php 
class Project {

	const COLLECTION = "projects";
	/**
	 * get an project By Id
	 * @param type $id : is the mongoId of the project
	 * @return type
	 */
	public static function getById($id) {
	  	return PHDB::findOne( self::COLLECTION,array("_id"=>new MongoId($id)));
	}
	
	/**
	 * Get an project from an id and return filter data in order to return only public data
	 * @param type $id 
	 * @return project structure
	 */
	public static function getPublicData($id) {
		//Public datas 
		$publicData = array (
		);

		//TODO SBAR = filter data to retrieve only publi data	
		$project = Project::getById($id);
		if (empty($project)) {
			//throw new CommunecterException("The project id is unknown ! Check your URL");
		}

		return $project;
	}

	/**
	 * Apply project checks and business rules before inserting
	 * @param array $project : array with the data of the project to check
	 * @return array Project well format : ready to be inserted
	 */
	public static function getAndCheckProject($project, $userId) {

		if (empty($project['name'])) {
			throw new CTKException(Yii::t("project","You have to fill a name for your project"));
		}
		
		// Is There a association with the same name ?
	    $projectSameName = PHDB::findOne(self::COLLECTION ,array( "name" => $_POST['name']));
	    if($projectSameName) { 
	      throw new CTKException(Yii::t("project","A project with the same name already exist in the plateform"));
	    }

	    if(empty($project['startDate']) || empty($project['endDate'])) {
			throw new CTKException("The start and end date of an event are required.");
		}

		//The end datetime must be after start datetime
		$startDate = strtotime($project['startDate']);
		$endDate = strtotime($project['endDate']);
		if ($startDate > $endDate) {
			throw new CTKException("The start date must be before the end date.");
		}

		$newProject = array(
			"name" => $project['name'],
			'startDate' => new MongoDate($startDate),
			'endDate' => new MongoDate($endDate),
			'creator' => $userId,
			'created' => time()
	    );
				  
		if(!empty($project['postalCode'])) {
			if (!empty($project['city'])) {
				$insee = $project['city'];
				$address = SIG::getAdressSchemaLikeByCodeInsee($insee);
				$newProject["address"] = $address;
				$newProject["geo"] = SIG::getGeoPositionByInseeCode($insee);
			}
		} else {
			throw new CTKException(Yii::t("project","Please fill the postal code of the project to communect it"));
		}
		
		//No mandotory fields 
		if (!empty($project['description']))
			$newProject["description"] = $project['description'];
		
		if (!empty($project['url']))
			$newProject["url"] = $project['url'];

		if (!empty($project['licence']))
			$newProject["licence"] = $project['licence'];

		//Tags
		if (isset($project['tags'])) {
			if ( gettype($project['tags']) == "array" ) {
				$tags = $project['tags'];
			} else if ( gettype($project['tags']) == "string" ) {
				$tags = explode(",", $project['tags']);
			}
			$newProject["tags"] = $tags;
		}

		return $newProject;
	}

	/**
	 * Insert a new project, checking if the project is well formated
	 * @param array $params Array with all fields for a project
	 * @param string $userId UserId doing the insertion
	 * @return array as result type
	 */
	public static function insert($params, $userId){
	    $type = Person::COLLECTION;

	    $newProject = self::getAndCheckProject($params, $userId);
	    // TODO SBAR - If a Link::connect is used why add a link hard coded
	    $newProject["links"] = array( "contributors" => 
	    								array($userId =>array("type" => $type,"isAdmin" => true)));

	    PHDB::insert(self::COLLECTION,$newProject);

	    Link::connect($userId, $type, $newProject["_id"], self::COLLECTION, $userId, "projects", true );
	    
	    return array("result"=>true, "msg"=>"Votre projet est communecté.", "id"=>$newProject["_id"]);	
	}

	public static function removeProject($projectId, $userId) {
		
	    $type = Person::COLLECTION;

	    if (! Authorisation::canEditItem($userId, self::COLLECTION, $projectId)) {
			throw new CTKException("You can't remove this project : you are not admin of it");
		}

	    //0. Remove the links
        Link::disconnect($userId, $type, $projectId, PHType::TYPE_PROJECTS, $userId, "projects" );
        //1. Remove project's sheet corresponding to $projectId _id
        PHDB::remove(self::COLLECTION,array("_id" => new MongoId($projectId)));

        return array("result"=>true, "msg"=>"The project has been removed with success", "projectid"=>$projectId);
    }

    public static function saveChart($properties){
	    //print_r($properties);
	    //$member["_id"], $memberType, Yii::app()->session["userId"],
	    $propertiesList=array(
							"gouvernance" => $properties["gouvernance"],
							"local" => $properties["local"],	
							"partenaire" => $properties["partenaire"],
							"partage" => $properties["partage"],
							"solidaire" => $properties["solidaire"],
							"avancement" => $properties["avancement"],
		);
	    PHDB::update(self::COLLECTION,
			array("_id" => new MongoId($properties["projectID"])),
            array('$set' => array("properties"=> $propertiesList))
        );
        return true;
    }

    /**
	 * Update a project field value
	 * @param String $projectId The person Id to update
	 * @param String $projectFieldName The name of the field to update
	 * @param String $projectFieldValue 
	 * @param String $isAdmin or $isModerate (including after)
	 * @return boolean True if the update has been done correctly. Can throw CTKException on error.
	 */
	public static function updateProjectField($projectId, $projectFieldName, $projectFieldValue, $userId) {  
		$project = array($projectFieldName => $projectFieldValue);
		
		//update the project
		PHDB::update( self::COLLECTION, array("_id" => new MongoId($projectId)), 
		                          array('$set' => $project));
	                  
	    return array("result"=>true, "msg"=>"Votre projet a été modifié avec succes", "id"=>$projectId);
	}

}
?>