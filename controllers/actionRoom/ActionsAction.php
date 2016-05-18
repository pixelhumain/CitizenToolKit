<?php
class ActionsAction extends CAction
{
    public function run($id)
    {
      $controller=$this->getController();
      $where = array( "type"=>ActionRoom::TYPE_ACTION, "room"=>$id );

      //check if is moderated in which the proper filter will be added to the where clause
      /*
      $moduleId = "communecter";//$this->moduleId
      $app = PHDB::findOne (PHType::TYPE_APPLICATIONS, array("key" => $moduleId  ) );
      $isModerator = Survey::isModerator(Yii::app()->session["userId"], $moduleId);
      if(!$isModerator && isset($app["moderation"]))
        $where['applications.'.$moduleId.'.'.Survey::STATUS_CLEARED] = array('$exists'=>false);
      */
      $list = PHDB::find(ActionRoom::COLLECTION_ACTIONS, $where );
      $room = PHDB::findOne (ActionRoom::COLLECTION, array("_id"=>new MongoId ( $id ) ) );

      $uniqueVoters = PHDB::count( Person::COLLECTION, array("applications.actions"=>array('$exists'=>true)) );

      $parentType = ($room["parentType"] == "organizations") ? "organization" : "";
      if( $parentType == "" )
        $parentType = ($room["parentType"] == "projects") ? "project" : "";
      if( $parentType == "" )
        $parentType = ($room["parentType"] == "person") ? "person" : "";

      $roomLoadByHash = ( isset( $room["parentType"] ) && isset( $room["parentId"] ) ) ? "#".$parentType.".detail.id.".$room["parentId"] : "#rooms"; 

     
      $parent = array("name"=>"_");
      //error_log("parentType : ".$room["parentType"]);
      if( $room["parentType"] == Organization::COLLECTION ) {
        $parent = Organization::getById($room["parentId"]);
      }
      if( $room["parentType"] == Person::CONTROLLER ) {
        $parent = Person::getById($room["parentId"]);
      }
      if( $room["parentType"] == Project::COLLECTION ) {
        $parent = Project::getById($room["parentId"]);
      }

      $tpl = ( isset($_GET['tpl']) ) ? $_GET['tpl'] : "actionList";

      $controller->renderPartial( $tpl, array( "list" => $list,
                                             "room"=>$room,
                                             "isModerator"=>false,//$isModerator,
                                             "uniqueVoters"=>$uniqueVoters,
                                             "parent"=>$parent,
                                             "surveyLoadByHash" => $roomLoadByHash,
                                              )  );
    }
}

