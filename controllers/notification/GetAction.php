<?php
/**
 * a notification has been read by a user
 * remove it's entry in the notify node on an activity Stream for the current user
 * @return [json] 
 */
class GetAction extends CAction
{
     public function run($type,$id) { 

        $res = array(); $datas = array();
        if( Yii::app()->session["userId"] ){
          if($type != Person::COLLECTION){
            $params = array(
              '$and'=> 
                array(
                  array("notify.id.".Yii::app()->session["userId"] => array('$exists' => true),
                  "verb" => array('$ne' => ActStr::VERB_ASK)),
                  array('$or'=> array(
                    array("target.type"=>$type, "target.id" => $id),
                    array("target.parent.type"=>$type, "target.parent.id" => $id)
                    )
                  ) 
                ) 
              );
          }else{
            $params = array("notify.id.".Yii::app()->session["userId"] => array('$exists' => true));
          }
          if(@$_POST["refreshTimestamp"]){
            $params = array_merge($params,  array('updated' => array( '$gt' => new MongoDate($_POST["refreshTimestamp"]) ) ) );
            $res=ActivityStream::getNotificationsByTimeLimit($params);
          }
          else
            $res = ActivityStream::getNotificationsByStep($params, @$_POST['indexMin']);
          if(!empty($res)){
            $timezone="";
              foreach($res as $key => $data){
                if(@$data["notify"]["labelAuthorObject"] || @$data["notify"]["labelArray"]){
                  if(@$data["notify"]["labelAuthorObject"] && $data["notify"]["labelAuthorObject"]=="mentions")
                    $res[$key]["notify"]["displayName"]=Notification::translateMentions($data);
                  else
                    $res[$key]["notify"]["displayName"]=Notification::translateLabel($data);
                }
                $res[$key]["timeAgo"]=Translate::pastTime(date(@$data["updated"]->sec), "timestamp", $timezone);
                $res[$key]["timestamp"]=$data["updated"]->sec;
              } 
          }

          //$data["notif"] = $res;
          $datas["notif"] = $res;
          if(@$_POST["refreshTimestamp"])
            $datas["countNotif"] = ActivityStream::countUnseenNotifications(Yii::app()->session["userId"], $type, $id);
          //$datas["coop"] = Cooperation::getCountNotif();

        } else{
          $data = array('result' => false , 'msg'=>'something somewhere went terribly wrong');   
        }

        Rest::json($datas,false);  
        Yii::app()->end();
    }
}