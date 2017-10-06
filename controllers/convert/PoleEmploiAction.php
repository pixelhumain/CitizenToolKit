<?php
class PoleEmploiAction extends CAction {

    public function run($url, $rome_activity = null) {

    	$res = Convert::convertPoleEmploiToPh($url, $rome_activity);

  		if (isset($res)) {
			Rest::json($res);
		}

		Yii::app()->end();
	}
}

?>