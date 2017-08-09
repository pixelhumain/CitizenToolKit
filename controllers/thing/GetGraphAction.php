<?php
/**
* GetGraphAction 
*  
* @author: Jean Daniel CAZAL <danzalkay551@gmail.com>
* Date: 26/01/2017
* 
*/

class GetGraphAction extends CAction {
    
    public function run($country="RE",$postalCode="0",$nbDays=1,$devices=array()) {

		$controller=$this->getController();

        $params=array(); 
        if(isset($nbDays)){$params['nbDays']=$nbDays;}
        if(isset($country)){$params['country']=$country; }
        if(isset($postalCode)){$params['postalCode']=$postalCode; }
        //if((isset($postalCode) && empty($postalCode)) || !isset($postalCode)) {$params['postalCode']="97490";}
        //else if(isset($postalCode) && is_int($postalCode)){$params['postalCode']=strval($postalCode); }

       // echo $params['postalCode'];

        if(Yii::app()->request->isAjaxRequest)
            echo $controller->renderPartial("graph",$params,true);
        else 
            $controller->render("graph",$params);

    }
}

?>
		