<?php 

class CleanTagsAction extends CAction
{
	public function run() {
		$controller=$this->getController();
		$params = array();
	    if(Yii::app()->request->isAjaxRequest)
	        echo $controller->renderPartial("cleantags",$params,true);
	    else 
	        $controller->render("cleantags",$params);
	}
}
?>
