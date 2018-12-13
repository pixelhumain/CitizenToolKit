<?php
class GetAction extends CAction {
    
    public function run($id = null, $format = null, $limit=50, $index=0, $tags = null, $multiTags=null , $key = null, $insee = null, $idElement = null, $typeElement = null) {
		$controller=$this->getController();
		// Get format
		if( $format == Translate::FORMAT_RSS) {
			$bindMap = TranslateRss::$dataBinding_news;
		} elseif ($format == Translate::FORMAT_KML) {
			$bindMap = TranslateKml::$dataBinding_news;
		} elseif ($format == Translate::FORMAT_GEOJSON) {
			$bindMap = TranslateGeoJson::$dataBinding_news;
		} elseif ($format == Translate::FORMAT_JSONFEED) {
			$bindMap = TranslateJsonFeed::$dataBinding_news;
		}

		else
			$bindMap = TranslateCommunecter::$dataBinding_news;

	    $result = Api::getData($bindMap, $format, News::COLLECTION, $id,$limit, $index, $tags, $multiTags, $key, $insee, null, $idElement, $typeElement);

	    if ((isset($idElement)) && (isset($typeElement))) {
	    	
	    	$opendata = Preference::getPreferencesByTypeId($idElement, $typeElement);
	    	
		    if ($opendata["isOpenData"] == false) {
				$not_opendata = 'Cet element ne veut pas partager ces données au public';
				$strucRss = News::getStrucChannelRss($not_opendata);
				$array_null = array();
				$result = $array_null;
			} else {
				$element = Element::getSimpleByTypeAndId($typeElement , $idElement);
				$name_element = ($element["name"]);
				$element["name"] = 'Fil d\'actualité de ' . $element["name"];		
				$strucRss = News::getStrucChannelRss($element["name"]);
			}
	    } else if ((isset($tags))) {
				$tags = ' Fil d\'atualité pour le ou les Tags suivants : ' . $tags;
				$strucRss = News::getStrucChannelRss($tags);
		} else {
			$default = 'Fil d\'actualité de tous les éléments du site';
			$strucRss = News::getStrucChannelRss($default);
		}			
			
	    if( $format == Translate::FORMAT_RSS)
			Rest::xml($result, $strucRss, $format);
		elseif ($format == Translate::FORMAT_KML) {
			$strucKml = News::getStrucKml();		
			Rest::xml($result, $strucKml,$format);
		}
		else 
			Rest::json($result);

		Yii::app()->end();
    }
}