<?php

class Poi {
	const COLLECTION = "poi";
	const CONTROLLER = "poi";
	const MODULE = "poi";
	const ICON = "fa-map-marker";
	
	//TODO Translate
	public static $types = array (
		//"link" 			=> "Lien, Url",
		//"tool"			=> "Outil",
		//"machine"		=> "Machine",
		//"software"		=> "Software",
		//"rh"			=> "Ressource Humaine",
		//"materialRessource" => "Ressource Materielle",
	//	"financialRessource" => "Ressource Financiere",
	//	"ficheBlanche" => "Fiche Blanche",
	//	"geoJson" 		=> "Url au format geojson ou vers une umap",
		"compostPickup" => "récolte de composte",
		"video" 		=> "video",
		"sharedLibrary" => "bibliothèque partagée",
		"artPiece" 		=> "oeuvres",
		"recoveryCenter"=> "ressourcerie",
		"trash" 		=> "poubelle",
		"history" 		=> "histoire",
		"something2See" => "chose a voir",
		//"funPlace" 		=> "endroit Sympas (skatepark, vue...)",
		//"place" 		=> "place publique",
		"streetArts" 	=> "arts de rue",
		"openScene" 	=> "scène ouverte",
		"stand" 		=> "stand",
		"parking" 		=> "Parking",
		"other"			=> "Autre"
	);

	//From Post/Form name to database field name
	public static $dataBinding = array (
	    "section" => array("name" => "section"),
	    "type" => array("name" => "type"),
	    "subtype" => array("name" => "subtype"),
	    "name" => array("name" => "name", "rules" => array("required")),
	    "address" => array("name" => "address", "rules" => array("addressValid")),
	    "addresses" => array("name" => "addresses"),
	    "streetAddress" => array("name" => "address.streetAddress"),
	    "postalCode" => array("name" => "address.postalCode"),
	    "city" => array("name" => "address.codeInsee"),
	    "addressLocality" => array("name" => "address.addressLocality"),
	    "addressCountry" => array("name" => "address.addressCountry"),
	    "geo" => array("name" => "geo"),
	    "geoPosition" => array("name" => "geoPosition"),
	    "description" => array("name" => "description"),
	    "parentId" => array("name" => "parentId"),
	    "parentType" => array("name" => "parentType"),
	    "media" => array("name" => "media"),
	    "urls" => array("name" => "urls"),
	    "medias" => array("name" => "medias"),
	    "tags" => array("name" => "tags"),

	    "modified" => array("name" => "modified"),
	    "source" => array("name" => "source"),
	    "updated" => array("name" => "updated"),
	    "creator" => array("name" => "creator"),
	    "created" => array("name" => "created"),
	    );
//From Post/Form name to database field name
	public static $collectionsList = array (
	   "Où sont les femmes",
		"Passeur d'images",
		"MHQM",
		"MIAA",
		"Portrait citoyens",
		"Parcours d'engagement"
	);
	public static $genresList=array(
		"Documentaire",
		"Fiction",
		"Docu-fiction",
		"Films outils",
		"Films de commande"
	);
	public static function getConfig(){
		return array(
			"collection"    => self::COLLECTION,
            "controller"   => self::CONTROLLER,
            //"module"   => self::MODULE,
			//"init"   => Yii::app()->getModule( self::MODULE )->assetsUrl."/js/init.js" ,
			//"form"   => Yii::app()->getModule( self::MODULE )->assetsUrl."/js/dynForm.js" ,
            //"categories" => CO2::getModuleContextList(self::MODULE,"categories"),
            "lbhp"=>true
		);
	}
	/**
	 * get all poi details of an element
	 * @param type $id : is the mongoId (String) of the parent
	 * @param type $type : is the type of the parent
	 * @return list of pois
	 */
	public static function getPoiByIdAndTypeOfParent($id, $type, $orderBy){
		$pois = PHDB::findAndSort(self::COLLECTION,array("parentId"=>$id,"parentType"=>$type), $orderBy);
	   	return $pois;
	}
	/**
	 * get poi with limit $limMin and $limMax
	 * @return list of pois
	 */
	public static function getPoiByTagsAndLimit($limitMin=0, $indexStep=15, $searchByTags=""){
		$where = array("name"=>array('$exists'=>1));
		if(@$searchByTags && !empty($searchByTags)){
			$queryTag = array();
			foreach ($searchByTags as $key => $tag) {
				if($tag != "")
					$queryTag[] = new MongoRegex("/".$tag."/i");
			}
			if(!empty($queryTag))
				$where["tags"] = array('$in' => $queryTag); 			
		}
		
		$pois = PHDB::findAndSort( self::COLLECTION, $where, array("updated" => -1));
	   	return $pois;
	}

	/**
	 * get a Poi By Id
	 * @param String $id : is the mongoId of the poi
	 * @return poi
	 */
	public static function getById($id) { 
		
	  	$poi = PHDB::findOneById( self::COLLECTION ,$id );
	  	
	  	// Use case notragora
	  	if(@$poi["type"])
		  	$poi["typeSig"] = self::COLLECTION.".".$poi["type"];
	  	else
		  	$poi["typeSig"] = self::COLLECTION;
		if(@$poi["type"])
	  		$poi = array_merge($poi, Document::retrieveAllImagesUrl($id, self::COLLECTION, $poi["type"], $poi));
	  	$where=array("id"=>@$id, "type"=>self::COLLECTION, "doctype"=>"image");
	  	$poi["images"] = Document::getListDocumentsWhere($where, "image");//(@$id, self::COLLECTION);
	  	
	  	return $poi;
	}

	public static function delete($id, $userId) {
		if ( !@$userId) {
            return array( "result" => false, "msg" => "You must be loggued to delete something" );
        }
        
        $poi = self::getById($id);
        if (!self::canDeletePoi($userId, $id, $poi)) 
        	return array( "result" => false, "msg" => "You are not authorized to delete this poi.");
        
        //Delete the comments
        $resComments = Comment::deleteAllContextComments($id,self::COLLECTION, $userId);
		if (@$resComments["result"]) {
			PHDB::remove(self::COLLECTION, array("_id"=>new MongoId($id)));
			$resDocs = Document::removeDocumentByFolder(self::COLLECTION."/".$id);
		} else {
			return $resComments;
		}
		
		return array("result" => true, "msg" => "The element has been deleted succesfully", "resDocs" => $resDocs);
	}

	public static function canDeletePoi($userId, $id, $poi = null) {
		if ($poi == null) 
			$poi = self::getById($id);
		//To Delete POI, the user should be creator or can delete the parent of the POI
        if ( $userId == @$poi['creator'] || Authorisation::canDeleteElement(@$poi["parentId"], @$poi["parentType"], $userId)) {
            return true;
        } else {
        	return false;
        }
    }

    public static function getDataBinding() {
	  	return self::$dataBinding;
	}
}
?>