<?php
/*
- actions are saved on any needed element in any collection

 */
class Action
{
    const NODE_ACTIONS          = "actions";
    const COLLECTION            = "actions";
    const CONTROLLER            = "action";
    
    const ACTION_ROOMS          = "actionRooms";
    const ACTION_ROOMS_TYPE_SURVEY = "survey";

    const ACTION_MODERATE       = "moderate";
    const ACTION_VOTE_UP        = "voteUp";
     const ACTION_VOTE        = "vote";
    const ACTION_VOTE_ABSTAIN   = "voteAbstain";
    const ACTION_VOTE_UNCLEAR   = "voteUnclear";
    const ACTION_VOTE_MOREINFO  = "voteMoreInfo";
    const ACTION_VOTE_DOWN      = "voteDown";
    const ACTION_FUND      = "fund";
   
    //const ACTION_VOTE_BLOCK   = "voteBlock";
    const ACTION_PURCHASE       = "purchase";
    /*const ACTION_INFORM       = "inform";
    const ACTION_ASK_EXPERTISE  = "expertiseRequest";*/
    const ACTION_COMMENT        = "comment";
    const ACTION_REPORT_ABUSE   = "reportAbuse";
    const ACTION_FOLLOW         = "follow";


    public static $dataBinding = array (
        
        "name"                  => array("name" => "name",                  "rules" => array("required")),
        "description"           => array("name" => "description",           "rules" => array("required")),
        "tags"                  => array("name" => "tags"),
        "urls"                  => array("name" => "urls"),
        "medias"                => array("name" => "medias"),
        
        "startDate"             => array("name" => "startDate"),
        "endDate"               => array("name" => "endDate"),
        
        "actors"               => array("name" => "actors"),
        
        // Open / Closed
        "status"                => array("name" => "status",                "rules" => array("required")), 
        
        "idUserAuthor"          => array("name" => "idUserAuthor",          "rules" => array("required")),
        "idParentRoom"          => array("name" => "idParentRoom",          "rules" => array("required")),
        "parentId"              => array("name" => "parentId",              "rules" => array("required")),
        "parentType"            => array("name" => "parentType",            "rules" => array("required")),

        "parentIdSurvey"              => array("name" => "parentIdSurvey",              "rules" => array("required")),
        "parentTypeSurvey"            => array("name" => "parentTypeSurvey",            "rules" => array("required")),
        "role"                 => array("name" => "role"),
        
        
        "idParentResolution"    => array("name" => "idParentResolution"),
        
        "email"                 => array("name" => "status"),
        
        "modified" => array("name" => "modified"),
        "updated" => array("name" => "updated"),
        "creator" => array("name" => "creator"),
        "created" => array("name" => "created"),

        //"medias" => array("name" => "medias"),
    );

    public static function getDataBinding() {
        return self::$dataBinding;
    }
    
    /**
     * get a action room By Id
     * @param String $id : is the mongoId of the action room
     * @return array Document of the action room
     */
    public static function getById($id) {
        return PHDB::findOne( self::COLLECTION,array("_id"=>new MongoId($id)));
    }
    public static function getSimpleSpecById($id, $where=null, $fields=null){
        if(empty($fields))
            $fields = array("_id", "name");
        $where["_id"] = new MongoId($id) ;
        $action = PHDB::findOne(self::COLLECTION, $where ,$fields);
        return @$action;
    }
    
    /**
     * - can only add an action once vote , purchase, .. 
     * - check user and element existance 
     * - QUESTION : should actions be application inside
     * @param String $userId : the id of the user doing the action
     * @param String $id : the id of the element it applied on
     * @param String $collection : Location of the element
     * @param String $action : Type of the action
     * @param String $reason : Detail or comment
     * @param boolean $unset : if the user already did the action, the action will be unset
     * @param boolean $multiple : true : the user can do multiple action, else can not.
     * @return array result (result, msg)
     */
        public static function addAction( $userId=null , $id=null, $collection=null, $action=null, $unset=false, $multiple=false, $details=null, $path=null){
       
        $user = Person::getById($userId);
        $element = ($id) ? PHDB::findOne ($collection, array("_id" => new MongoId($id) )) : null;
        $res = array('result' => false , 'msg'=>'something somewhere went terribly wrong');

        $possibleActions = array(
            self::ACTION_ROOMS,
            self::ACTION_ROOMS_TYPE_SURVEY,
            self::ACTION_MODERATE,
            self::ACTION_VOTE_UP,
            self::ACTION_VOTE,  
            self::ACTION_VOTE_ABSTAIN,
            self::ACTION_VOTE_UNCLEAR,
            self::ACTION_VOTE_MOREINFO,
            self::ACTION_VOTE_DOWN,
            self::ACTION_FUND,
            self::ACTION_PURCHASE,
            self::ACTION_COMMENT,
            self::ACTION_REPORT_ABUSE,
            self::ACTION_FOLLOW );
        if(!in_array($action, $possibleActions))
            throw new CTKException("Well done ! Stop playing and join us to help the construction of this common!");

        if($user && $element){
            //check user hasn't allready done the action or if it's allowed
            if( $unset 
                || !isset( $element[ $action ] ) 
                || ( !$multiple && isset( $element[ $action ] ) && !in_array( (string)$user["_id"] , $element[ $action ] )
                || $multiple ) ){
                

                //Add or remove
                $dbMethod = '$set';
                if($unset){
                    $dbMethod = '$unset';
                    if(($action=="voteUp" || $action=="voteDown") && (!isset( $element[$action][$userId])))
    	                throw new CTKException("Well done ! Stop playing and join us to help the construction of this common!");
                }else{
	            	if(($action=="voteUp" || $action=="voteDown" || $action=="reportAbuse") && (isset($element[$action][$userId])))
    	                throw new CTKException("Well done ! Stop playing and join us to help the construction of this common!");
                    if($action=="vote" && @$element[$action][$userId] && @$element[$action][$userId]["status"]==@$details["status"])
                        throw new CTKException("Well done ! Stop playing and join us to help the building of the Common!");
                
                }

                // Additional info
                // can contain a 
                // comment, date
                if (!empty($details) && is_array($details))
                    $details = array_merge($details, array('date' => new MongoDate(time()))) ; 
                else 
                    $details = array('date' => new MongoDate(time()));
                //$mapUser[ self::NODE_ACTIONS.".".$collection.".".$action.".".(string)$element["_id"] ] = $details ;
                //$mapUser[self::NODE_ACTIONS.".".$collection.".".(string)$element["_id"].".".$action ] = $action ;
                //update the user table => adds or removes an action
                //PHDB::update ( Person::COLLECTION , array( "_id" => $user["_id"]), 
                //                                  array( $dbMethod => $mapUser));

                //Decrement when removing an action instance
                if($unset){
                    $dbMethod = '$unset';
                    $inc = -1;
                }//Push unique user Ids into action node list + increment
                elseif($multiple == true)
                {
                    $dbMethod = '$addToSet';
                    $inc = 1;
                }//Save unique user Id and details into action + increment
                else{
                    $dbMethod = '$set';
                    $inc = 1;
                }
                
                if($unset){
                    PHDB::update ( $collection, array( "_id" => new MongoId($element["_id"]) ), 
                                                array( $dbMethod => array(  $action.".".Yii::app()->session["userId"] => 1),
                                                       '$inc'=>array( $action."Count" => $inc),
                                                       '$set'=>array( "updated" => time(),
                                                                      "modified" => new MongoDate(time()))
                                                       ));
                    // DELETE IN NOTIFICATION REMOVE LIKE
                }
                else{
                    if(@$path)
                        $mapObject[ $action.".".$path.".".(string)$user["_id"] ] = $details ;
                    else
                        $mapObject[ $action.".".(string)$user["_id"] ] = $details ;

                    $params = array();
                    $createNotification=true;
                    //if : empeche la mise à jour de la date des news à chaque commentaire
                    if(!(in_array($collection, [News::COLLECTION,Service::COLLECTION,Product::COLLECTION]) && $action == Action::ACTION_COMMENT)){    
                        if( $dbMethod == '$set'){
                            $mapObject["updated"] = time();
                            $mapObject["modified"] = new MongoDate(time());
                        }
                        else $params['$set'] = array( "updated" => new MongoDate(time()), "modified" => new MongoDate(time()) );
                    }
                    $params[$dbMethod] = $mapObject;
                    if(@$path)
                        $params['$inc'] = array( $action.".".$path."Count" => $inc);
                    else
                        $params['$inc'] = array( $action."Count" => $inc);
                    if($action=="vote"){
                        $params['$inc'] = array( $action."Count.".$details["status"] => $inc);
                        if(@$element[$action] && @$element[$action][$userId]){
                            $createNotification=false;
                            if($action."Count.".$element[$action][$userId]["status"]>1)
                                $params['$inc']=array_merge($params['$inc'], array( $action."Count.".$element[$action][$userId]["status"] => -1));
                            else
                                $params['$unset']= array( $action."Count.".$element[$action][$userId]["status"] => 1);
                        }
                    }
                    PHDB::update ($collection, 
                                    array("_id" => new MongoId($element["_id"])), 
                                    $params);
                    //NOTIFICATION LIKE AND DISLIKE
                    if(in_array($action, ["vote"])){
                       $verb = ActStr::VERB_REACT;
                    }
                  
                    if(@$verb && $collection != Survey::COLLECTION){
                        $objectNotif=null;
                        if($collection==Comment::COLLECTION){
                            $target=array("type"=>$element["contextType"], "id"=>$element["contextId"]);
                            $objectNotif=array("type"=>$collection,"id"=>(string)$element["_id"]);
                        } else {
                            $target=array("type"=>$collection, "id"=>(string)$element["_id"]);
                            if(@$element["targetIsAuthor"] || @$target["object"])
                                $target["targetIsAuthor"]=true;
                        }

                        //Rest::json($target); exit;
                        if($target["type"]!=Form::ANSWER_COLLECTION && $createNotification)
                            Notification::constructNotification($verb, array("id" => Yii::app()->session["userId"],"name"=> Yii::app()->session["user"]["name"]), $target, $objectNotif, $collection);
                    }

                    if($action == "reportAbuse" && $collection == News::COLLECTION){
                        $params = CO2::getThemeParams();
                        $abuseMax = $params["nbReportCoModeration"];
                        $thisNews = News::getById($element["_id"]);
                        if($thisNews["reportAbuseCount"] >= $abuseMax){
                            Proposal::createModeration(News::COLLECTION, (string)$element["_id"]);
                        }
                    } 
                    
                }

                //self::addActionHistory( $userId , $id, $collection, $action);
                self::updateParent( $id, $collection);

                //We update the points of the user
                if(isset($user['gamification']['actions'][$action])){
                    Gamification::incrementUser($userId, $action);
                }
                else{
                    Gamification::updateUser($userId);
                }

                //Moderate automatic 
                if($collection == Comment::COLLECTION && $action == "reportAbuse"){
                    $element = ($id) ? PHDB::findOne ($collection, array("_id" => new MongoId($id) )) : null;
                    if(isset($element[$action."Count"]) && $element[$action."Count"] >= 3){
                        PHDB::update($collection, array("_id" => new MongoId($element["_id"])), 
                                                                            array('$set' => array( "isAnAbuse" => true, "status"=>"declaredAbused"))
                        );
                    }
                }
                $msg="OK !"; //WDTF ?
                if($action==self::ACTION_REPORT_ABUSE)
                    $msg=Yii::t("common","Thank you ! We are dealing it as quickly as possible. If there is more than 5 report, the news will be hidden");
                if($action==self::ACTION_VOTE)
                    $msg=Yii::t("common","Reaction succesfully saved");
                $res = array( "result"          => true,  
                              "userActionSaved" => true,
                              "user"            => PHDB::findOne ( Person::COLLECTION , array("_id" => new MongoId( $userId ) ),array("actions")),
                              "element"         => PHDB::findOne ($collection,array("_id" => new MongoId($id) ),array( $action)),
                              "inc"         => $inc,
                              "msg"             => $msg
                               );
            } else {
                $res = array( "result" => true,  "userAllreadyDidAction" => true, "msg" => Yii::t("common","You have already made this action" ));
            }
        }
        return $res;
    }
    public static function getList($type , $id, $actionType, $indexStep=0){
        $object=PHDB::findOne($type,
                     array("_id" => new MongoId( $id )),
                     array($actionType."Count", $actionType)
                );
        if(!empty($object) && !empty($object[$actionType])){
            foreach ($object[$actionType] as $key => $value) {
                $type = (@$value["type"]) ? $value["type"] : Person::COLLECTION;
                $target=Element::getElementSimpleById($key, $type,null, array("slug", "name", "profilThumbImageUrl"));
                $object[$actionType][$key]=array_merge($object[$actionType][$key], $target);
            }
        }
        return $object;
    }
    /* TODO BOUBOULE - Not necessary anymore ... ?
    The Action History colelction helps build timeline and historical visualisations 
    on a given item
    in time we could also use it as a base for undoing tasks
     */
    public static function addActionHistory($userId=null , $id=null, $collection=null, $action=null){
        $currentAction = array( "who"=> $userId,
                                "self" => $action,
                                "collection" => $collection,
                                "objectId" => $id,
                                "created"=>time()
                                );
        PHDB::insert( ActivityStream::COLLECTION, $currentAction );
    }
    
    /*
    update the updated date on a parent entity
     */
    public static function updateParent($id=null, $collection=null)
    {
        $updatableParentTypes = array(
            ActionRoom::TYPE_ACTIONS    => array("parentCollection" => ActionRoom::COLLECTION,
                                                 "parentField"=>"room"),
            Survey::COLLECTION          => array("parentCollection" => ActionRoom::COLLECTION,
                                                 "parentField"=>"survey"),
        );
        if( $obj = @$updatableParentTypes[$collection] )
        {
            $element = ($id) ? PHDB::findOne ($collection, array("_id" => new MongoId($id) )) : null;
            if( isset($element) && $parentId = @$element[ $obj["parentField"] ] ) 
            {
                PHDB::update ( $obj["parentCollection"], array("_id" => new MongoId( $parentId )), 
                                           array( '$set'=>array( "updated" => time())
                                                  ));
            }
        }
    }
    /**
   * check if loggued in user is in the "follow" field array for an entry
   * @return Boolean
   */
    public static function isUserFollowing( $value, $actionType )
    {
        //return ( isset($value[ $actionType ]) && is_array($value[ $actionType ]) && in_array(Yii::app()->session["userId"], $value[ $actionType ]) );
        $userId = Yii::app()->session["userId"];
        return ( isset($value[ $actionType ]) && 
                 is_array($value[ $actionType ]) && 
                (isset($value[ $actionType ][$userId]) || in_array(Yii::app()->session["userId"], $value[ $actionType ])) 
               );
    }

    /**
   * return an html according to enttry voting state
   * the total count of votes
   * filtering class
   * boolean hasVoted
   * @return array
   */
    public static function  voteLinksAndInfos( $logguedAndValid, $value )
    {
        $res = array( "links"=>"",
                      "totalVote"=>0,
                      "avoter" => "mesvotes",
                      "hasVoted" => true);
        //has loged user voted on this entry 
        //vote UPS
        $voteUpActive = ( $logguedAndValid && Action::isUserFollowing($value,Action::ACTION_VOTE_UP) ) ? "active":"";
        $voteUpCount = (isset($value[Action::ACTION_VOTE_UP."Count"])) ? $value[Action::ACTION_VOTE_UP."Count"] : 0 ;
        $hrefUp = ($logguedAndValid && empty($voteUpActive)) ? "javascript:addaction('".$value["_id"]."','".Action::ACTION_VOTE_UP."')" : "";
        $classUp = $voteUpActive." ".Action::ACTION_VOTE_UP." ".$value["_id"].Action::ACTION_VOTE_UP;
        $iconUp = ' fa-thumbs-up ';

        //vote ABSTAIN 
        $voteAbstainActive = ($logguedAndValid && Action::isUserFollowing($value,Action::ACTION_VOTE_ABSTAIN) ) ? "active":"";
        $voteAbstainCount = (isset($value[Action::ACTION_VOTE_ABSTAIN."Count"])) ? $value[Action::ACTION_VOTE_ABSTAIN."Count"] : 0 ;
        $hrefAbstain = ($logguedAndValid && empty($voteAbstainActive)) ? "javascript:addaction('".(string)$value["_id"]."','".Action::ACTION_VOTE_ABSTAIN."')" : "";
        $classAbstain = $voteAbstainActive." ".Action::ACTION_VOTE_ABSTAIN." ".$value["_id"].Action::ACTION_VOTE_ABSTAIN;
        $iconAbstain = ' fa-circle';

        //vote UNCLEAR
        $voteUnclearActive = ( $logguedAndValid && Action::isUserFollowing($value,Action::ACTION_VOTE_UNCLEAR) ) ? "active":"";
        $voteUnclearCount = (isset($value[Action::ACTION_VOTE_UNCLEAR."Count"])) ? $value[Action::ACTION_VOTE_UNCLEAR."Count"] : 0 ;
        $hrefUnclear = ($logguedAndValid && empty($voteUnclearCount)) ? "javascript:addaction('".$value["_id"]."','".Action::ACTION_VOTE_UNCLEAR."')" : "";
        $classUnclear = $voteUnclearActive." ".Action::ACTION_VOTE_UNCLEAR." ".$value["_id"].Action::ACTION_VOTE_UNCLEAR;
        $iconUnclear = " fa-pencil";

        //vote MORE INFO
        $voteMoreInfoActive = ( $logguedAndValid && Action::isUserFollowing($value,Action::ACTION_VOTE_MOREINFO) ) ? "active":"";
        $voteMoreInfoCount = (isset($value[Action::ACTION_VOTE_MOREINFO."Count"])) ? $value[Action::ACTION_VOTE_MOREINFO."Count"] : 0 ;
        $hrefMoreInfo = ($logguedAndValid && empty($voteMoreInfoCount)) ? "javascript:addaction('".$value["_id"]."','".Action::ACTION_VOTE_MOREINFO."')" : "";
        $classMoreInfo = $voteMoreInfoActive." ".Action::ACTION_VOTE_MOREINFO." ".$value["_id"].Action::ACTION_VOTE_MOREINFO;
        $iconMoreInfo = " fa-question-circle";

        //vote DOWN 
        $voteDownActive = ($logguedAndValid && Action::isUserFollowing($value,Action::ACTION_VOTE_DOWN) ) ? "active":"";
        $voteDownCount = (isset($value[Action::ACTION_VOTE_DOWN."Count"])) ? $value[Action::ACTION_VOTE_DOWN."Count"] : 0 ;
        $hrefDown = ($logguedAndValid && empty($voteDownActive)) ? "javascript:addaction('".(string)$value["_id"]."','".Action::ACTION_VOTE_DOWN."')" : "";
        $classDown = $voteDownActive." ".Action::ACTION_VOTE_DOWN." ".$value["_id"].Action::ACTION_VOTE_DOWN;
        $iconDown = " fa-thumbs-down";

        //votes cannot be changed, link become spans
        if( !empty($voteUpActive) || !empty($voteAbstainActive) || !empty($voteDownActive) || !empty($voteUnclearActive) || !empty($voteMoreInfoActive))
        {
            $linkVoteUp = ($logguedAndValid && !empty($voteUpActive) ) ? 
                            "<span class='".$classUp." ' ><i class='fa fa-caret-bottom'></i> ".
                                Yii::t("survey","Voted").
                                " <span class='btnvote color-btnvote-green'><i class='fa $iconUp' ></i> Pour</span></span>" : "";
            $linkVoteAbstain = ($logguedAndValid && !empty($voteAbstainActive)) ? 
                            "<span class='".$classAbstain." '><i class='fa fa-caret-bottom'></i> ".
                                Yii::t("survey","Voted").
                                " <span class='btnvote color-btnvote-white'><i class='fa $iconAbstain'></i> Blanc</span></span>" : "";
            $linkVoteUnclear = ($logguedAndValid && !empty($voteUnclearActive)) ? 
                            "<span class='".$classUnclear." '><i class='fa fa-caret-bottom'></i> ".
                                Yii::t("survey","Voted").
                                " <span class='btnvote color-btnvote-blue'><i class='fa  $iconUnclear'></i> Incompris</span></span>" : "";
            $linkVoteMoreInfo = ($logguedAndValid && !empty($voteMoreInfoActive)) ? 
                            "<span class='".$classMoreInfo." '><i class='fa fa-caret-bottom'></i> ".
                                Yii::t("survey","Voted").
                                " <span class='btnvote color-btnvote-purple'><i class='fa  $iconMoreInfo'></i> Incomplet</span></span>" : "";
            $linkVoteDown = ($logguedAndValid && !empty($voteDownActive)) ? 
                            "<span class='".$classDown." '><i class='fa fa-caret-bottom'></i> ".
                                Yii::t("survey","Voted").
                                " <span class='btnvote color-btnvote-red'><i class='fa $iconDown'></i> Contre</span></span>" : "";
        }
        else
        {
            $res["avoter"] = "avoter";
            $res["hasVoted"] = false;
            
            $linkVoteUp = ($logguedAndValid  ) ? "<a class='btn ".$classUp." voteIcon' data-vote='".Action::ACTION_VOTE_UP."' href=\" ".$hrefUp." \" title='Voter Pour'><i class='fa $iconUp' ></i></a>" : "";
            $linkVoteAbstain = ($logguedAndValid ) ? "<a class='btn ".$classAbstain." voteIcon'  data-vote='".Action::ACTION_VOTE_ABSTAIN."' href=\"".$hrefAbstain."\" title='Voter Blanc'><i class='fa $iconAbstain'></i></a>" : "";
            $linkVoteUnclear = ($logguedAndValid ) ? "<a class='btn ".$classUnclear." voteIcon' data-vote='".Action::ACTION_VOTE_UNCLEAR."' href=\"".$hrefUnclear."\" title='Voter Pas Clair, Pas fini, Amender'><i class='fa $iconUnclear'></i></a>" : "";
            $linkVoteMoreInfo = ($logguedAndValid ) ? "<a class='btn ".$classMoreInfo." voteIcon' data-vote='".Action::ACTION_VOTE_MOREINFO."' href=\"".$hrefMoreInfo."\" title=\"Voter Pour Plus d'informations\"><i class='fa $iconMoreInfo'></i></a>" : "";
            $linkVoteDown = ($logguedAndValid) ? "<a class='btn ".$classDown." voteIcon' data-vote='".Action::ACTION_VOTE_DOWN."' href=\"".$hrefDown."\" title='Voter Contre'><i class='fa $iconDown'></i></a>" : "";
        }

        //default Values are hasn't voted
        $res["totalVote"] = $voteUpCount+$voteAbstainCount+$voteDownCount+$voteUnclearCount+$voteMoreInfoCount;
        $res["ordre"] = $voteUpCount+$voteDownCount;
        $res["links"] = ( $value["type"] == Survey::TYPE_ENTRY ) ? "<span class='text-bold active btnvote color-btnvote-red'><i class='fa fa-clock-o'></i> ".Yii::t("survey","You did not vote", null, Yii::app()->controller->module->id)."</span>" : "";

        //$res["links"] = ($res["totalVote"]) ? "<span class='text-red text-bold'>RESULT</span>" : $res["links"];
        if( ($value["type"]==Survey::TYPE_ENTRY 
                && ( !isset($value["dateEnd"]) || $value["dateEnd"] > time() ) 
            ) || ($res["hasVoted"])
          )
            $res["links"] = "<div class='leftlinks'>".$linkVoteUp." ".$linkVoteUnclear." ".$linkVoteAbstain." ".$linkVoteMoreInfo." ".$linkVoteDown."</div>";
        else
            $res["avoter"] = "closed";
        
        return $res;
    }

}