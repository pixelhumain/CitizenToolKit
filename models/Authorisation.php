<?php
class Authorisation {
	//**************************************************************
    // Super Admin Authorisation
    //**************************************************************
    public static function isUserSuperAdmin($userId) {
        $res = false;
        if (! empty($userId)) {
            $account = Person::getById($userId);
            $res = Role::isUserSuperAdmin(@$account["roles"]);
        }
        return $res;
    }

    //**************************************************************
    // Super Admin Authorisation
    //**************************************************************
    public static function isUser($userId,$roles) {
        $res = false;
        if (! empty($userId)) {
            $account = Person::getById($userId);
            $res = Role::isUser(@$account["roles"],$roles);
        }
        return $res;
    }

    //**************************************************************
    // Organization Authorisation
    //**************************************************************

    /**
     * Return true if the user is admin (not pending) of at least an organization 
     * @param String the id of the user
     * @return boolean true/false
     */
    public static function isUserOrganizationAdmin($userId) {
    	$res = false;
        
        //get the person links memberOf
        $personMemberOf = Person::getPersonMemberOfByPersonId($userId);

        foreach ($personMemberOf as $linkKey => $linkValue) {
            if (!empty($linkValue) && !empty($linkValue["isAdmin"])) {
                if ($linkValue["isAdmin"] && @$linkValue["isAdminPending"] != false) {
                    $res = true;
                    break;
                }
            }
        }

    	return $res;
    }
	//trie les éléments dans l'ordre alphabetique par name
	 public static 	function sortByName($array){
	 	function mySort($a, $b){
	  		if(isset($a['name']) && isset($b['name'])){
		    	return ( strtolower($b['name']) < strtolower($a['name']) );
			}else{
				return false;
			}
		}
		usort($array,"mySort");
		return $array;
	}
    /**
     * Return an array with the organizations the user is admin of
     * @param String the id of the user
     * @return array of Organization (organizationId => organizationValue)
     */
    public static function listUserOrganizationAdmin($userId) {
    	$res = array();
        $result = array();
        //organization i'am admin 
        $where = array( "links.members.".$userId.".isAdmin" => true,
                        "links.members.".$userId.".isAdminPending" => array('$exists' => false ),
                        "links.members.".$userId.".".Link::TO_BE_VALIDATED => array('$exists' => false )
                    );

        $organizations = PHDB::find(Organization::COLLECTION, $where);
        $res = $organizations;
        foreach ($organizations as $e) {
        	$res[(string)new MongoId($e['_id'])] = $e;
        	if (self::canEditMembersData($e['_id'])) {
        		if(isset($e["links"]["members"])){
        			foreach ($e["links"]["members"] as $key => $value) {
        				if(isset($value["type"]) && $value["type"] == Organization::COLLECTION){
        					$subOrganization = Organization::getById($key);
        					$res[$key] = $subOrganization;        					
        				}
        			}
        		}
        	}
        }
		/*function mySort($a, $b){
	  		if(isset($a['name']) && isset($b['name'])){
		    	return ( strtolower($b['name']) < strtolower($a['name']) );
			}else{
				return false;
			}
		}
        if(isset($res)) usort($res,"mySort");*/
        //$res=self::sortByName($res);
    	return $res;
    }

    /**
     * Return true if the user is admin of the organization
     * @param String the id of the user
     * @param String the id of the organization
     * @return array of Organization (simple)
     */
    public static function isOrganizationAdmin($userId, $organizationId) {
        $res = false;
        $myOrganizations = self::listUserOrganizationAdmin($userId);
        if(!empty($myOrganizations))
		  $res = array_key_exists((string)$organizationId, $myOrganizations);

        return $res;
    }


    public static function isFormAdmin($userId, $organizationId) {
        $res = false;
        $myOrganizations = self::listUserOrganizationAdmin($userId);
        if(!empty($myOrganizations))
          $res = array_key_exists((string)$organizationId, $myOrganizations);

        return $res;
    }

    /**
     * Return true if the user is member of the organization
     * @param String the id of the user
     * @param String the id of the organization
     * @return array of Organization (simple)
     */
    public static function isOrganizationMember($userId, $organizationId) {
        $res = false;
        
        //Get the members of the organization : if there is no member then it's a new organization
        //We are in a creation process
        $organizationMembers = Organization::getMembersByOrganizationId($organizationId);
        if( array_key_exists((string)$userId, $organizationMembers) && (
            empty($organizationMembers[(string)$userId]["toBeValidated"]) || 
            $organizationMembers[(string)$userId]["toBeValidated"] == false)) 
            $res = true;    
        return $res;
    }

    /**
     * Return true if the user is admin of the organization or if it's a new organization
     * @param String the id of the user
     * @param String the id of the organization
     * @return array of Organization (simple)
     */
    /*public static function isProjectAdmin($userId, $projectId) {
        $res = false;
        $project = Project::getById($projectId);
        if( @$project["links"]['contributors'][$userid]["isAdmin"] == true )
            $res = true;
        
        return $res;
    }*/


 	/**
 	 * Description : Check if user is connect 
     * - to the web interface : communecter.org
     * - or to the mobile interface : meteor.communecter.org
 	 * @param type $userId 
 	 * @return type
 	 */
    public static function isMeteorConnected( $token, $test=null ) {
        
        $result = false;
        if($test)
            echo $token;
        if( $user = PHDB::findOne( "users" , array( "services.resume.loginTokens.0.hashedToken" => $token ) ) )
        {
            if($test)
                var_dump($user);
            if( $account = PHDB::findOne(Person::COLLECTION, array("email"=>$user["profile"]["pixelhumain"]["email"])) )
            {
                if($test)
                    var_dump($account);
                Person::saveUserSessionData($account);
                if($test)
                    echo "<br/>".Yii::app()->session['userId'];
                $result = true;
            }
        }
        return $result;
    }

    public static function isValidUser( $user, $pwd ) {
        
        $result = false;
        Person::clearUserSessionData();
        $account = PHDB::findOne(Person::COLLECTION, array( '$or' => array( 
                                                        array("email" => new MongoRegex('/^'.preg_quote(trim($user)).'$/i')),
                                                        array("username" => $user) ) ));
        if( @$account )
        {
            if (Person::checkPassword($pwd, $account)) {
                Person::saveUserSessionData($account);
                Person::updateLoginHistory((String) $account["_id"]);
                $result = true;
            }
        }
        return $result;
    }

    /**
     * Description
     * @param type $userId 
     * @return type
     */
    public static function getAuthorisation($userId) {
        
        //TODO : think about how to manage authentification
        //Authentification => Menu Access

        $result = array();
       
        return $result;
    }

    /**
     * Return true if the organization can modify his members datas
     * Depends if the params isParentOrganizationAdmin is set to true and if the organization 
     * got a flag canEditMember set to true
     * @param String $organizationId An id of an organization
     * @return boolean True if the organization can edit his members data. False, else.
     */
    public static function canEditMembersData($organizationId) {
        $res = false;
        if (Yii::app()->params['isParentOrganizationAdmin']) {
            $organization = Organization::getById($organizationId);
            if (isset($organization["canEditMember"]) && $organization["canEditMember"])
                $res = true;
        }
        return $res;
    }

    //**************************************************************
    // Event Authorisation
    //**************************************************************

    /**
     * Return true if the user is Admin of the event
     * A user can be admin of an event if :
     * 1/ He is attendee + admin of the event
     * 2/ He is admin of an organization organizing an event
     * 3/ He is admin of an organization that can edit it members (canEditMembers flag) 
     *      and the organizations members is organizing the event
     * @param String $eventId The eventId to check if the userId is admin of
     * @param String $userId The userId to get the authorisation of
     * @return boolean True if the user isAdmin, False else
     */
    public static function isEventAdmin($eventId, $userId, $attendees = null){
        $res = false;
        if(!empty($attendees) && @$attendees[$userId]["isAdmin"] == true){
            $res=true;
        }else{
            $where = array("_id"=>new MongoId($eventId),
                            "links.attendees.".$userId.".isAdmin" => true,
                            "links.attendees.".$userId.".isAdminPending" => array('$exists' => false));
            $event = PHDB::findOne(Event::COLLECTION, $where);
            if(!empty($event))
                $res=true;
            /*$listEvent = self::listEventsIamAdminOf($userId);
            if(isset($listEvent[(string)$eventId])){
                $res=true;
            }*/   
        }
        
        return $res;
    }
    /*public static function isEventAdmin($eventId, $userId) {
    	$res = false;
    	$event=Event::getById($eventId);
        if(@$event["links"] && @$event["links"]["attendees"] && (@$event["preferences"]["isOpenEdtion"] && $event["preferences"]["isOpenEdtion"] !=true)){
	        if(@$event["links"]["attendees"][$userId] && @$event["links"]["attendees"][$userId]["isAdmin"])
       			$res=true;
       		else{
	       		$res="openEdition";
	       		foreach($event["links"]["attendees"] as $value){
		       		if(@$value["isAdmin"] && $value["isAdmin"]==true){
		       			$res=false;
		       			break;
		       		}	
	       		}
       		}
       	} else {
	       $res="openEdition";
       	}	
       	return $res;
    }*/
    /**
     * Return true if the user is member of the event
     * @param String the id of the user
     * @param String the id of the event
     * @return array of event (simple)
     */
    public static function isEventMember($userId, $eventId) {
        $res = false;
        
        //Get the members of the event : if there is no member then it's a new organization
        //We are in a creation process
        $eventMembers = Event::getAttendeesByEventId($eventId);
        if( array_key_exists((string)$userId, $eventMembers) && (
            empty($eventMembers[(string)$userId]["toBeValidated"]) || 
            $eventMembers[(string)$userId]["toBeValidated"] == false)) 
        $res = true;    
        return $res;
    }

    /**
     * List all the event the userId is adminOf
     * A user can be admin of an event if :
     * 1/ He is attendee + admin of the event
     * 2/ He is creator of the event so admin
     * 3/ He is admin of an organization organizing an event
     * 4/ He is admin of a project organizing an event
     * @param String $userId The userId to get the authorisation of
     * @return array List of EventId (String) the user is admin of
     */
    public static function listEventsIamAdminOf($userId) {
        $eventListFinal = array();

        //event i'am admin 
        $where = array("links.attendees.".$userId.".isAdmin" => true);
        $eventListFinal = PHDB::find(Event::COLLECTION, $where);


        //events of organization i'am admin 
       /* $listOrganizationAdmin = self::listUserOrganizationAdmin($userId);
        foreach ($listOrganizationAdmin as $organizationId => $organization) {
            $eventOrganizationAsOrganizer = Event::listEventByOrganizerId($organizationId, Organization::COLLECTION);
            foreach ($eventOrganizationAsOrganizer as $eventId => $eventValue) {
                $eventListFinal[$eventId] = $eventValue;
            }
        }
		//events of project i'am admin 
        $listProjectAdmin = self::listProjectsIamAdminOf($userId);
        foreach ($listProjectAdmin as $projectId => $project) {
            $eventProjectAsOrganizer = Event::listEventByOrganizerId($projectId, Project::COLLECTION);
            foreach ($eventProjectAsOrganizer as $eventId => $eventValue) {
                $eventListFinal[$eventId] = $eventValue;
            }
		}*/
        return $eventListFinal;
    }
    
    public static function listOfEventAdmins($eventId) {
        $res = array();
        $event = Event::getById($eventId);
        if ($attendees = @$event["links"]["attendees"]){
	        foreach ($attendees as $personId => $linkDetail){
		    	if(@$linkDetail["isAdmin"]==true){
			    	array_push($res, $personId);
		    	}   
	        } 
	    }	
        return $res;
    }
    //**************************************************************
    // Project Authorisation
    //**************************************************************

    /**
     * Return true if the user is Admin of the project
     * A user can be admin of an project if :
     * 1/ He is attendee + admin of the project
     * 2/ He is admin of an organization organizing a project (not include)
     * 3/ He is admin of an organization that can edit it members (canEditMembers flag) (not include)
     *      and the organizations members is organizing the project
     * @param String $projectId The projectId to check if the userId is admin of
     * @param String $userId The userId to get the authorisation of
     * @return boolean True if the user isAdmin, False else
     */
    /* $isProjectAdmin = false;
	  	$admins = array();
    	if(isset($project["_id"]) && isset(Yii::app()->session["userId"])) {
    		$isProjectAdmin =  self::isProjectAdmin((String) $project["_id"],Yii::app()->session["userId"]);
    		if (!$isProjectAdmin && !empty($organizations)){
	    		foreach ($organizations as $data){
		    		$admins = Organization::getMembersByOrganizationId( (string)$data['_id'], Person::COLLECTION , "isAdmin" );
		    		foreach ($admins as $key => $member){
			    		if ($key == Yii::app()->session["userId"]){
				    		$isProjectAdmin=1;
				    		break 2;
			    		}
		    		}
	    		}
    		}
		}*/

    public static function isProjectAdmin($projectId, $userId) {
    	$res = false;
    	$listProject = self::listProjectsIamAdminOf($userId);
		if( isset( $listProject[(string)$projectId] ) )
       		$res=true;
       	
       	return $res;
    }

    /**
     * Return true if the user is member of the project
     * @param String the id of the user
     * @param String the id of the project
     * @return array of Project (simple)
     */
    public static function isProjectMember($userId, $projectId) {
        $res = false;
        
        //Get the members of the project : if there is no member then it's a new organization
        //We are in a creation process
        $projectMembers = Project::getContributorsByProjectId($projectId);
        if( array_key_exists((string)$userId, $projectMembers) && (
            empty($projectMembers[(string)$userId]["toBeValidated"]) || 
            $projectMembers[(string)$userId]["toBeValidated"] == false)) 
        $res = true;    
        return $res;
    }
    
	public static function listProjectsIamAdminOf($userId) {
        $projectList = array();
		
        //project i'am admin 
        $where = array("links.contributors.".$userId.".isAdmin" => true,
         				"links.contributors.".$userId.".isAdminPending" => array('$exists' => false )
         		);
        $projectList = PHDB::find(Project::COLLECTION, $where);
        /*$listOrganizationAdmin = self::listUserOrganizationAdmin($userId);
        foreach ($listOrganizationAdmin as $organizationId => $organization) {
            $projectOrganization = Organization::listProjects($organizationId);
            foreach ($projectOrganization as $projectId => $projectValue) {
	            if (!empty($projectValue) && count($projectValue) > 1){
	            	if(array_key_exists($projectId, $projectList) != true){
                		$projectList[$projectId] = $projectValue;
					}
				}
            }
        }*/
       /* function mySort($a, $b){
	  		if(isset($a['name']) && isset($b['name'])){
		    	return ( strtolower($b['name']) < strtolower($a['name']) );
			}else{
				return false;
			}
		}

        if(isset($res)) usort($res,"mySort");*/
		//$projectList = self::sortByName($projectList);
        return $projectList;
    }

    //**************************************************************
    // Job Authorisation
    //**************************************************************

    /**
     * Return true if the user is Admin of the job
     * A user can be admin of an job if :
     * 1/ He is admin of the organization posting the job offer
     * 3/ He is admin of an organization that can edit it members (canEditMembers flag) 
     *      and the organizations members is offering the job
     * @param String $jobId The jobId to check if the userId is admin of
     * @param String $userId The userId to get the authorisation of
     * @return boolean True if the user isAdmin, False else
     */
    public static function isJobAdmin($jobId, $userId) {
        $job = Job::getById($jobId);
        if (!empty($job["hiringOrganization"])) {
            $organizationId = (String) $job["hiringOrganization"]["_id"];
        } else {
            throw new CommunecterException("The job ". $jobId." is not well format : contact your admin.");
        }
        
        $res = self::isOrganizationAdmin($userId, $organizationId);

        return $res;
    }

    /**
    * Get the authorization for edit an event
    * An user can edit an event if :
    * 1/ he is admin of this event
    * 2/ he is admin of an organisation, which is the creator of an event
    * 3/ he is admin of an organisation witch can edit an organisation creator 
    * @param String $userId The userId to get the authorisation of
    * @param String $eventId event to get authorisation of
    * @return a boolean True if the user can edit and false else
    */
    public static function canEditEvent($userId, $eventId){
    	$res = false;
    	$event = Event::getById($eventId);
    	if(!empty($event)){

    		// case 1
    		if(isset($event["links"]["attendees"])){
    			foreach ($event["links"]["attendees"] as $key => $value) {
    				if($key ==  $userId){
	    				if(isset($value["isAdmin"]) && $value["isAdmin"]==true && empty($value["isAdminPending"])){
	    					$res = true;
	    				}
	    			}
    			}
    		}
    		// case 2 and 3
    		/*if(isset($event["links"]["organizer"])){
    			foreach ($event["links"]["organizer"] as $key => $value) {
    				if( self::isOrganizationAdmin($userId, $key)){
    					$res = true;
    				}
    			}
    		}*/	
    	}
    	return $res;
    }

    //**************************************************************
    // Entry Authorisation
    //**************************************************************
    
   /**
    * Get the authorization to edit an entry. The entry is stored in the survey collection.
    * A user can edit a vote if :
    * 1/ he is super admin
    * 2/ he is the organizer of the vote
    * 3/ he is admin of an organisation witch is organizer 
    * @param String $userId The userId to get the authorisation of
    * @param String $eventId event to get authorisation of
    * @return a boolean True if the user can edit and false else
    */
    public static function canEditElementByParent($userId, $elemId,$parentType=null,$parentId=null,$type = null){
        $res = false;
        $elem = Element::getByTypeAndId($type, $elemId);

        if(!empty($elem) && !empty($userId)) {
            // case 1 : superAdmin
            if (self::isUserSuperAdmin($userId)) {
                return true;
            }

            // case 2 : organiser of Survey
            if ( @$elem["organizerType"] == Person::COLLECTION && @$elem["organizerId"] == $userId ) {
                return true;
            }

            // case 3 : admin of parent
            if ( self::canEditItem($userId, $parentId, $parentType) )  {
                return true;
           }
        } else {
            //RAJOUTER UN LOG
            error_log("Problem with survey authorization, surveyId:".@$elemId." & userId:".@$userId);
        }
        return $res;
    }

    public static function canEdit($userId, $id,$type){
        $res = false;
        $elem = $type::getById($id) ;

        if( @$elem && !empty($userId) ) {
            if( (@$elem["parentType"] == Person::COLLECTION && $userId == @$elem["parentId"] )
                || $userId == @$elem["creator"]
                || self::canEditItem($userId, @$elem["parentId"], @$elem["parentType"]) ) 
                return true;
        } 
        return $res;
    }

    public static function userOwner($userId, $type, $id){
        $res = false;
        $el = $type::getById($id) ;

        if( @$el && $userId && $userId == $el["creator"] ) 
            return true;
        
        return $res;
    }


    /**
    * Get the authorization for edit an item
    * @param type is the type of item, (organization or event or person or project)
    * @param itemId id of the item we want to edits
    * @return a boolean
    */
    public static function canEditItem($userId, $type, $itemId,$parentType=null,$parentId=null,$deleteProcess=false){
        $res=false;    
        $check = false;
        //DDA
        if( $type == Room::COLLECTION || $type == Room::CONTROLLER ||
            $type == Action::COLLECTION || $type == Action::CONTROLLER || 
            $type == Proposal::COLLECTION || $type == Proposal::CONTROLLER ) { 

            if( $parentType == null || $parentId == null ){
                $elem = Element::getByTypeAndId($type, $itemId);
                $parentId = $elem["parentId"];
                $parentType = $elem["parentType"];
            } 
            $isDDA = true;
            $type = $parentType;
            $itemId = $parentId;
            $check = true;
        }

        //Super Admin can do anything
        if(Role::isSuperAdmin( Role::getRolesUserId($userId) ) )
            return true;

        if ( $type == Event::COLLECTION || $type == Project::COLLECTION || $type == Organization::COLLECTION ) {
            //Check if delete pending => can not edit
            $isStatusDeletePending = Element::isElementStatusDeletePending($type, $itemId);
            if ($isStatusDeletePending && $deleteProcess === false ) {
                return false;
            }
            //Element admin ?
            else if (self::isElementAdmin($itemId, $type, $userId)) {
                return true;
            //Source admin ?
            } else if (self::isSourceAdmin($itemId, $type, $userId)) {
                return true;
            } else if(@$isDDA == true) {
                return self::canParticipate($userId, $type, $itemId);
            }
        } else if($type == Person::COLLECTION) {
            if($userId==$itemId)
                $res = true;
        } else if($type == City::COLLECTION ) {
            if($check)
                $res = self::isLocalCitizen( $userId, ($parentType == City::CONTROLLER) ? $parentId : $itemId ); 
            else 
                $res = true;
        } else if($type == Poi::COLLECTION) 
            $res = self::canEdit($userId, $itemId, "Poi");
        else if($type == Place::COLLECTION) 
            $res = self::canEdit($userId, $itemId,"Place");
        else if($type == Ressource::COLLECTION) 
            $res = self::canEdit($userId, $itemId,"Ressource");
        else if($type == Classified::COLLECTION) 
            $res = self::userOwner($userId, "Classified", $itemId);
        else if($type == Proposal::COLLECTION) 
           $res = self::canParticipate($userId, $type, $itemId);
        else if($type == Action::COLLECTION) 
           $res = self::canParticipate($userId, $type, $itemId);
        else if($type == Network::COLLECTION) 
            $res = self::canEdit($userId, $itemId, "Network");
    	return $res;
    }

    /**
    * check for any element if a user is either member, contributor, attendee
    * @param type is the type of item, (organization or event or person or project)
    * @param itemId id of the item we want to edits
    * @return a boolean
    */
    public static function canParticipate($userId, $type, $itemId, $openEdition=true){
        $res=false;
        if( $userId )
        {   
            if($openEdition)
                $res = Preference::isOpenEdition(Preference::getPreferencesByTypeId($itemId, $type));
            //var_dump($res);
            if($res != true){
                if( $type == Person::COLLECTION && $itemId == $userId)
                    $res = true;
                if( $type == Organization::COLLECTION )
                    $res = self::isOrganizationMember($userId, $itemId);
                if( $type == Project::COLLECTION )
                    $res = self::isProjectMember($userId, $itemId);
                if( $type == Event::COLLECTION )
                    $res = self::isEventMember($userId, $itemId);
                if($type == City::COLLECTION) 
                    $res = self::isLocalCitizen($userId, $itemId);
                if($type == News::COLLECTION) 
                    $res = true;
                if($type == Classified::COLLECTION) 
                    $res = true;
            }
            
        }
        return $res;
    }
    public static function canSeePrivateElement($links, $type, $id, $creator, $parentType=null, $parentId=null){
        if(!@Yii::app()->session["userId"])
            return false;
        else{
            // SuperAdmin is equal to superman, he can see everything
            if( self::isUserSuperAdmin(Yii::app()->session["userId"]) )
                return true;
            //creator access to his creativity
            if(Yii::app()->session["userId"]==$creator)
                return true;
            // attendees and contributors directly access and see the element
            if(!empty($links) && 
                @$links[Link::$linksTypes[$type][Person::COLLECTION]] && 
                @$links[Link::$linksTypes[$type][Person::COLLECTION]][Yii::app()->session["userId"]])
                return true;
            if(!empty($parentType) && !empty($parentId))
                return self::canParticipate(Yii::app()->session["userId"], $parentType, $parentId, false);

            return false;
        }

    }
    /**
    * check if a user is a local citizen
    * @param cityId is a unique  city Id
    * @return a boolean
    */
    public static function isLocalCitizen($userId, $cityId) {
        $cityMap = City::getUnikeyMap($cityId);
        //echo Yii::app()->session["user"]["codeInsee"] ."==". $cityMap["insee"];
        return (@Yii::app()->session["user"]["codeInsee"] && Yii::app()->session["user"]["codeInsee"] == $cityMap["insee"] ) ? true : false;
    }

    /**
     * List the user that are admin of the organization
     * @param string $organizationId The organization Id to look for
     * @param boolean $pending : true include the pending admins. By default no.
     * @return type array of person Id
     */
    /*public static function listOrganizationAdmins($organizationId, $pending=false) {
        $res = array();
        $organization = Organization::getById($organizationId);
        
        if ($members = @$organization["links"]["members"]) {
            foreach ($members as $personId => $linkDetail) {
                if (@$linkDetail["isAdmin"] == true) {
	                $userActivated = Role::isUserActivated($personId);
	                if($userActivated){
	                    if ($pending) {
	                        array_push($res, $personId);
	                    } else if (@$linkDetail["isAdminPending"] == null || @$linkDetail["isAdminPending"] == false) {
	                        array_push($res, $personId); 
	                    }
					}
                }
            }
        }

        return $res;
    }*/
    /**
     * List the user that are admin of the organization
     * @param string $organizationId The organization Id to look for
     * @param boolean $pending : true include the pending admins. By default no.
     * @return type array of person Id
     */
    public static function listAdmins($parentId, $parentType, $pending=false) {
        $res = array(); 

        if ($parentType == Organization::COLLECTION){     
	        $parent = Organization::getById($parentId);
	        $link="members";
		}
		else if ($parentType == Project::COLLECTION){     
	        $parent = Project::getById($parentId);
	        $link="contributors";
		} else if ($parentType == Event::COLLECTION){     
	        $parent = Event::getById($parentId);
	        $link="attendees";
		}else if ($parentType == Form::COLLECTION){  
            $parent = Form::getLinksById($parentId);
            $link="survey";
        }
        
        if ($users = @$parent["links"][$link]) {
            foreach ($users as $personId => $linkDetail) {
                if (@$linkDetail["isAdmin"] == true) {
	                $userActivated = Role::isUserActivated($personId);
	                if($userActivated){
	                    if ($pending) {
	                        array_push($res, $personId);
	                    } else if (@$linkDetail["isAdminPending"] == null || @$linkDetail["isAdminPending"] == false) {
	                        array_push($res, $personId); 
	                    }
	                }
                }
            }
        }

        return $res;
    }


    /**
     * Return true if the user is source admin of the entity(organization, event, project)
     * @param String the id of the entity
     * @param String the type of the entity
     * @param String the id of the user
     * @return bool 
     */
    public static function isSourceAdmin($idEntity, $typeEntity ,$idUser){
        $res = false ;
        $entity = PHDB::findOne($typeEntity,array("_id"=>new MongoId($idEntity)));
        if(!empty($project["source"]["sourceKey"])){
            $user = PHDB::findOne(Person::COLLECTION,array("_id"=>new MongoId($idUser),
                                                        "sourceAdmin" => $entity["source"]["sourceKey"]));
        }
        if(!empty($user))
            $res = true ;
        return $res;
    }

    /**
     * Return true if the user is  admin of the entity (organization, event, project)
     * @param String the id of the entity
     * @param String the type of the entity
     * @param String the id of the user
     * @return bool 
     */
    public static function isElementAdmin($elementId, $elementType ,$userId){
        $res = false ;
        if( self::isUserSuperAdmin($userId) ) {
            $res = true;
        } else if( $elementType == Event::COLLECTION ) {
            $res = self::canEditEvent($userId,$elementId);
        } else if( $elementType == Project::COLLECTION ) {
            $res = self::isProjectAdmin($elementId, $userId);
        } else if( $elementType == Organization::COLLECTION ) {
            $res = self::isOrganizationAdmin($userId, $elementId);
        } else if( $elementType == Form::COLLECTION ) {
            //$res = self::isFormAdmin($userId, $elementId);
            $res = true;
        } else 
            error_log("isElementAdmin : Can not manage that type ! : ".$elementType);
        return $res;
    }
    
    /**
     * Return true if the entity is in openEdition
     * @param String the id of the entity
     * @param String the type of the entity
     * @return bool 
     */
    public static function isOpenEdition($idEntity, $typeEntity, $preferences=null){
        $res = false ;
        if(empty($preferences)){
            $entity = PHDB::findOne($typeEntity,array("_id"=>new MongoId($idEntity)),array('preferences'));
            $preferences = @$entity["preferences"];
        }
        if(!empty($preferences)){
           $res = Preference::isOpenEdition($preferences);
        }
        

        return $res;
    }


    /**
     * Return true if the entity is in openEdition
     * @param String the id of the entity
     * @param String the type of the entity
     * @return bool 
     */
    public static function isOpenData($idEntity, $typeEntity, $preferences=null){
        $res = false ;
        if(empty($preferences)){
            $entity = PHDB::findOne($typeEntity,array("_id"=>new MongoId($idEntity)),array('preferences'));
            $preferences = @$entity["preferences"];
        }
        if(!empty($preferences)){
           $res = Preference::isOpenData($preferences);

        }
        

        return $res;
    }


    public static function canEditItemOrOpenEdition($idEntity, $typeEntity, $userId, $parentType=null,$parentId=null){
        $res = false ;
        
        $res = self::isOpenEdition($idEntity, $typeEntity);
        if($res != true)
            $res = self::canEditItem($userId, $typeEntity, $idEntity, $parentType, $parentId);

        return $res;
    }

    /**
     * Return true if the user can delete the element
     * @param type $elementType 
     * @param type $elementId 
     * @param type $userId 
     * @return boolean
     */
    public static function canDeleteElement($elementId, $elementType, $userId) {
        //If open Edition : the element can be deleted
        if ($elementType == Person::COLLECTION && $elementId == $userId){
            $res = true;
        }else{
           $res = self::isOpenEdition($elementId, $elementType);

            if($res != true) {
                
                //check if the user is super admin
                $res = self::isUser($userId, array(Role::SUPERADMIN, Role::COEDITOR ));

                if ($res != true) {
                    // check if the user can edit the element (admin of the element)
                    $res = self::canEditItem($userId, $elementType, $elementId,null,null,true);

                    
                }
            } 
        }

        return $res;
    }
} 
?>