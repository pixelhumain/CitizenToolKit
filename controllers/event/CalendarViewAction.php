<?php
	class CalendarViewAction extends CAction{

		public function run($id=null, $type=null, $pod=null){
		  	$controller=$this->getController();

		  	$params = array(
		  		"events" => array()
		  	);
		  	$events = array();
		  	if( @$id )
		  	{
		  		if( @$type )
		  		{
		  			if(strcmp($type, "person")==0)
		  				$params['events'] = Event::getListCurrentEventsByPeopleId($id);
		  			else if (strcmp($type, "organization") == 0)
		  				$params['events'] = Event::getListCurrentEventsByOrganizationId($id);
		  		}else{
		  			//means we are showing details of an events
		  			$params['events'] = Event::getListEventsById($id);
		  			$event = Event::getById($id);
		  			$params['event'] = $event;
		  			if( @$event['startDate'] ){
		  				//focus on the start date of the event 
		  				$params['defaultDate'] = date("Y-m-d", strtotime($event["startDate"]) );
		  				//if last onl y one day then apply day view 
		  				$params['defaultView'] = "agendaDay";
		  				if( @$event['endDate'] )
		  				{
			  				$datetime1 = new DateTime($event['startDate']);
							$datetime2 = new DateTime($event['endDate']);
							$diff = $datetime1->diff($datetime2)->days;
							if( $diff > 1 ){
								if($diff < 7) 
									$params['defaultView'] = "agendaWeek";
								else 
									$params['defaultView'] = "month";
							}
		  				}
		  			}
		  		}
		  	}
		  	$tpl = ( $pod ) ? "../pod/calendarPod" : "calendarView";
		  	if(Yii::app()->request->isAjaxRequest)
	            echo $controller->renderPartial($tpl, $params);
	        else 
		  		$controller->render( $tpl , array("events" => $events));
		}
	}
?>