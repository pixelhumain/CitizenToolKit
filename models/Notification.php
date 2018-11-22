<?php 

class Notification{
	/* *
	*	Authors 
	*		@Bouboule [clement.damiens@gmail.com]
	*		@Bardot 
	**/
	//limit  the size of the notification map
	//when an organization/project has a huge number of members
	const PEOPLE_NOTIFY_LIMIT = 50;

	/**
	* $notificationTree is an multi-array defining each notification with different level
	* Levl:
	** First level is the verb
	** Second level is the type
		* $type::COLLECTION is some case is the second level (add, comment, etc)
		* Link::Type asAdmin || asMember in other case is the second on (join, ask, confirm, validate)
	** Third level is more about the target $targetIsAuthor for news
	* A part is composed by:
	* params boolean $repeat indicating if notification case can be repeat
	* params string $label defining the label at the creation of the notification
	* params string $labelRepeat defining the label when notification is updated
	* params string $url link of notification
	* params string $icon icon of notification
	* params array $labelArray indicating location of label to precising 
	* params boolean $notifyUser in case of notification should send to a user in addition to the target
	*/
	public static $notificationTree = array(
		// Action realized by a user
		ActStr::VERB_FOLLOW => array(
			"repeat" => true,
			//"context" => array("user","members"),
			"settings"=>"low",
			//WHAT == you || elementName
			"type"=> array(
				"user"=> array(
					"label" => "{who} is following you",
					"labelRepeat"=>"{who} are following you",
					"labelMail" => "{who} is following you",
					"labelRepeatMail"=>"{who} are following you",
				)
			),
			"label" => "{who} is following {where}",
			"labelRepeat"=>"{who} are following {where}",
			"labelMail" => "{who} is following {where}",
			"labelRepeatMail"=> "{who} are following {where}",
			"labelArray" => array("who","where"),
			"icon" => "fa-link",
			"url" => "page/type/{collection}/id/{id}/view/directory/dir/followers"
		),
		ActStr::VERB_ASK => array(
			"repeat" => true,
			"type" => array(
				"asMember" => array(
					"to"=> "members",
					"label"=>"{who} wants to join {where}",
					"labelRepeat"=>"{who} want to join {where}",
					"labelMail" => "{who} wants to join {where}",
					"labelRepeatMail"=>"{who} want to join {where}",
				),
				"asAdmin" => array(
					"to" => "admin",
					"label"=>"{who} wants to administrate {where}",
					"labelRepeat"=>"{who} want to administrate {where}",
					"labelMail" =>"{who} wants to administrate {where}",
					"labelRepeatMail"=>"{who} want to administrate {where}",
				)
			),
			"tpl" => "askToBecome",
			"labelArray" => array("who","where"),
			"context" => "admin",
			"settings"=>"low",
			"icon" => "fa-cog",
			"url" => "page/type/{collection}/id/{id}/view/notifications"
		),
		ActStr::VERB_DELETE => array(
			"type" => array(
				ActStr::VERB_ASK => array(
					"to"=> "admin",
					"label"=>"{who} asks the suppression of {where}",
					"labelMail"=>"{who} asks the suppression of {where}",
					"url" => "page/type/{collection}/id/{id}"
				),
				ActStr::VERB_REFUSE => array(
					"to" => "admin",
					"label"=>"{who} stopped the pending suppression of {where}",
					"labelMail"=>"{who} stopped the pending suppression of {where}",
					"url" => "page/type/{collection}/id/{id}"
				),
				ActStr::VERB_DELETE => array(
					"to" => "members",
					"label"=>"{who} deleted {where}",
					"labelMail"=>"{who} deleted {where}",
					"url" => "live"
				)
			),
			"tpl" => "deleted",
			"labelArray" => array("who","where"),
			"settings"=>"default",
			"icon" => "fa-trash",
		),
		//// USED ONLY FOR EVENT (because there is no confirmation of admin)
		// FOR ORGANIZATION AND PROJECT IF ONLY MEMBER
		ActStr::VERB_JOIN => array(
			"repeat" => true,
			//"context" => "members",
			"settings"=>"high",
			// "mail" => array(
			// 	"tpl"=>"join"
			// ),
			"type" => array(
				"asMember"=> array(
					Event::COLLECTION => array(
						"label"=>"{who} participates to {where}",
						"labelRepeat"=>"{who} participate to {where}",
						"labelMail"=>"{who} participates to {where}",
						"labelRepeatMail"=>"{who} participate to {where}"
					),
					Organization::COLLECTION => array(
						"label"=>"{who} joins {where}",
						"labelRepeat"=>"{who} join {where}",
						"labelMail"=>"{who} joins {where}",
						"labelRepeatMail"=>"{who} join {where}",
					),
					Project::COLLECTION => array(
						"label"=>"{who} contributes to {where}",
						"labelRepeat"=>"{who} contribute to {where}",
						"labelMail"=>"{who} contributes to {where}",
						"labelRepeatMail"=>"{who} contribute to {where}"
					)
				),
				"asAdmin" => array(
					"label"=>"{who} becomes administrator of {where}",
					"labelRepeat"=>"{who} become administrator of {where}"
				)
			),
			"labelArray" => array("who","where"),
			"icon" => "fa-group",
			"url" => "page/type/{collection}/id/{id}/view/directory/dir/{connectAs}"
		),
		ActStr::VERB_COMMENT => array(
			"repeat" => true,
			"type" => array(
				News::COLLECTION => array(
					"label" => "{who} commented on your news {what}",
					"labelRepeat" => "{who} added comments on your news {what}",
					"sameAuthor"=>array(
						"labelRepeat" => "{who} added few comments on your news {what}",
						"labelRepeatMail" => "{who} added few comments on your news {what}"
					),
					"labelMail" => "{who} commented on your news {what}",
					"labelRepeatMail" => "{who} added comments on your news {what}",
					"targetIsAuthor"=> array(
						"label"=> "{who} commented a news {what} posted on {where}",
						"labelRepeat"=> "{who} added comments on a news {what} posted on {where}",
						"labelMail"=> "{who} commented a news {what} posted on {where}",
						"labelRepeatMail" => "{who} added comments on a news {what} posted on {where}",
						"sameAuthor"=>array(
							"labelRepeat" => "{who} added few comments on a news {what} posted on {where}",
							"labelRepeatMail" => "{who} added few comments on a news {what} posted on {where}"
						)
					),
					"notifyUser" => true,
					"parentTarget"=>true,
					"repeat" => true,
					"url" => "page/type/news/id/{id}"
				),
				Classified::COLLECTION => array(
					"label" => "{who} commented on classified {what} in {where}",
					"labelRepeat" => "{who} added comments on classified {what} in {where}",
					"labelMail" => "{who} commented on classified {what} in {where}",
					"labelRepeatMail" => "{who} added comments on classified {what} in {where}",
					"sameAuthor"=>array(
						"labelRepeat" => "{who} added few comments on classified {what} in {where}",
						"labelRepeatMail" => "{who} added few comments on classified {what} in {where}"
					),
					"url" => "page/type/{collection}/id/{id}",
				),
				Proposal::COLLECTION => array(
					"label" => "{who} commented on proposal {what} in {where}",
					"labelRepeat" => "{who} added comments on proposal {what} in {where}",
					"labelMail" => "{who} commented on proposal {what} in {where}",
					"labelRepeatMail" => "{who} have added few comments on proposal {what} in {where}",
					"sameAuthor"=>array(
						"labelRepeat" => "{who} added few comments on proposal {what} in {where}",
						"labelRepeatMail" => "{who} added few comments on proposal {what} in {where}"
					),
					"url" => "page/type/{collection}/id/{id}/view/coop/room/{roomId}/proposal/{objectId}",
				),
				Action::COLLECTION => array(
					"label" => "{who} commented on action {what} in {where}",
					"labelRepeat" => "{who} added comments on action {what} in {where}",
					"labelMail" => "{who} commented on action {what} in {where}",
					"labelRepeatMail" => "{who} have added few comments on action {what} in {where}",
					"sameAuthor"=>array(
						"labelRepeat" => "{who} added few comments on action {what} in {where}",
						"labelRepeatMail" => "{who} added few comments on action {what} in {where}"
					),
					"url" => "page/type/{collection}/id/{id}/view/coop/room/{roomId}/action/{objectId}"
				),
				Resolution::COLLECTION => array(
					"label" => "{who} commented on resolution {what} in {where}",
					"labelRepeat" => "{who} added comments on resolution {what} in {where}",
					"sameAuthor"=>array(
						"labelRepeat" => "{who} added few comments on resolution {what} in {where}",
						"labelRepeatMail" => "{who} added few comments on resolution {what} in {where}"
					),
					"url" => "page/type/{collection}/id/{id}/view/coop/room/{roomId}/resolution/{objectId}"
				),
				ActionRoom::COLLECTION => array(
					"label" => "{who} commented on discussion {what} in {where}",
					"labelRepeat" => "{who} added comments on discussion {what} in {where}",
					"labelMail" => "{who} commented on discussion {what} in {where}",
					"labelRepeatMail" => "{who} added comments on discussion {what} in {where}",
					"url" => "comment/index/type/actionRooms/id/{id}"
				),
				/*"needs" => array(
					"label" => "{who} added a comment on your need",
					"labelRepeat" => "{who} added comment on your need",
					"need/datail/id/{id}"
				),*/
				Comment::COLLECTION => array(
					"label" => "{who} answered to your comment posted on {where}",
					"labelRepeat" => "{who} added comments on your comments posted on {where}",
					"labelMail" => "{who} answered to your comment posted on {where}",
					"labelRepeatMail" => "{who} added comments on your comments posted on {where}",
					"sameAuthor" => array(
						"labelRepeat" => "{who} added few comments on your comments posted on {where}",
						"labelRepeatMail" => "{who} added few comments on your comments posted on {where}"
					),
					"url" => "targetTypeUrl",
					"notifyUser" => true,
					"repeat"=>true
				)
			),
			"labelArray" => array("who","where","what"),
			"settings"=> "default",
			"mail" => array(
				"tpl" => "comment" //If orga or project to members
			),
			"icon" => "fa-comment"
			//"url" => "{whatController}/detail/id/{whatId}"
		),
		/*ActStr::VERB_LIKE => array(
			"repeat" => true,
			"type" => array(
				News::COLLECTION => array(
					"targetIsAuthor" => array(
						"label"=>"{who} likes a news {what} from {where}",
						"labelRepeat"=>"{who} like a news {what} from {where}"
					) ,
					"label"=>"{who} likes your news {what}",
					"labelRepeat"=>"{who} like your news {what}",
					"notifyUser" => true,
					"parentTarget"=>true,
					"url" => "page/type/news/id/{id}"
				),
				/*Classified::COLLECTION => array(
					"label" => "{who} commented on classified {what} in {where}",
					"labelRepeat" => "{who} added comments on classified {what} in {where}",
					"url" => "page/type/{collection}/id/{id}",
				),
				Proposal::COLLECTION => array(
					"label" => "{who} commented on proposal {what} in {where}",
					"labelRepeat" => "{who} added comments on proposal {what} in {where}",
					"url" => "page/type/{collection}/id/{id}/view/coop/room/{roomId}/proposal/{objectId}",
				),
				Action::COLLECTION => array(
					"label" => "{who} commented on action {what} in {where}",
					"labelRepeat" => "{who} added comments on action {what} in {where}",
					"url" => "page/type/{collection}/id/{id}/view/coop/room/{roomId}/action/{objectId}"
				),
				Resolution::COLLECTION => array(
					"label" => "{who} commented on resolution {what} in {where}",
					"labelRepeat" => "{who} added comments on resolution {what} in {where}",
					"url" => "page/type/{collection}/id/{id}/view/coop/room/{roomId}/resolution/{objectId}"
				//),
				Comment::COLLECTION => array(
					"label"=>"{who} likes your comment on {where}",
					"labelRepeat"=>"{who} like your comment on {where}",
					"url" => "targetTypeUrl",
					"notifyUser" => true
				)
			),
			"labelArray" => array("who", "where"),
			"settings"=> "default",
			"mail" => array(
				"type"=>"instantly",
				"to" => "author" //If orga or project to members
			),
			"icon" => "fa-thumbs-up"
		),
		ActStr::VERB_UNLIKE => array(
			"repeat" => true,
			"type" => array(
				News::COLLECTION => array(
					"targetIsAuthor" => array(
						"label"=>"{who} disapproves a news {what} from {where}",
						"labelRepeat"=>"{who} disapprove a news {what} from {where}"
					),
					"label"=>"{who} disapproves your news {what}",
					"labelRepeat"=>"{who} disapproves your news {what}",
					"notifyUser" => true,
					"parentTarget"=> true,
					"url" => "page/type/news/id/{id}"
				),
				/*Proposal::COLLECTION => array(
					"label" => "{who} commented on proposal {what} in {where}",
					"labelRepeat" => "{who} added comments on proposal {what} in {where}",
					"url" => "page/type/{collection}/id/{id}/view/coop/room/{roomId}/proposal/{objectId}",
				),
				Classified::COLLECTION => array(
					"label" => "{who} commented on classified {what} in {where}",
					"labelRepeat" => "{who} added comments on classified {what} in {where}",
					"url" => "page/type/{collection}/id/{id}",
				),
				Action::COLLECTION => array(
					"label" => "{who} commented on action {what} in {where}",
					"labelRepeat" => "{who} added comments on action {what} in {where}",
					"url" => "page/type/{collection}/id/{id}/view/coop/room/{roomId}/action/{objectId}"
				),
				Resolution::COLLECTION => array(
					"label" => "{who} commented on resolution {what} in {where}",
					"labelRepeat" => "{who} added comments on resolution {what} in {where}",
					"url" => "page/type/{collection}/id/{id}/view/coop/room/{roomId}/resolution/{objectId}"
				//),
				Comment::COLLECTION => array(
					"label"=>"{who} disapproves your comment on {where}",
					"labelRepeat"=>"{who} disapproves your comment on {where}",
					"url" => "targetTypeUrl",
					"notifyUser" => true
				)
			),
			"labelArray" => array("who", "where"),
			"settings"=> "default",
			"mail" => array(
				"type"=>"instantly",
				"to" => "author" //If orga or project to members
			),
			"icon" => "fa-thumbs-down"
		),*/
		ActStr::VERB_REACT => array(
			"repeat" => true,
			"type" => array(
				News::COLLECTION => array(
					"targetIsAuthor" => array(
						"label"=>"{who} reacts on a news from {where}",
						"labelRepeat"=>"{who} react on a news from {where}",
						"labelMail"=>"{who} reacts on a news from {where}",
						"labelRepeatMail"=>"{who} react on a news from {where}"
					),
					"label"=>"{who} reacts on your news {what}",
					"labelRepeat"=>"{who} react on your news {what}",
					"labelMail"=>"{who} reacts on your news {what}",
					"labelRepeatMail"=>"{who} react on your news {what}",
					"notifyUser" => true,
					"parentTarget"=> true,
					"url" => "page/type/news/id/{id}"
				),
				/*Proposal::COLLECTION => array(
					"label" => "{who} commented on proposal {what} in {where}",
					"labelRepeat" => "{who} added comments on proposal {what} in {where}",
					"url" => "page/type/{collection}/id/{id}/view/coop/room/{roomId}/proposal/{objectId}",
				),
				Classified::COLLECTION => array(
					"label" => "{who} commented on classified {what} in {where}",
					"labelRepeat" => "{who} added comments on classified {what} in {where}",
					"url" => "page/type/{collection}/id/{id}",
				),
				Action::COLLECTION => array(
					"label" => "{who} commented on action {what} in {where}",
					"labelRepeat" => "{who} added comments on action {what} in {where}",
					"url" => "page/type/{collection}/id/{id}/view/coop/room/{roomId}/action/{objectId}"
				),
				Resolution::COLLECTION => array(
					"label" => "{who} commented on resolution {what} in {where}",
					"labelRepeat" => "{who} added comments on resolution {what} in {where}",
					"url" => "page/type/{collection}/id/{id}/view/coop/room/{roomId}/resolution/{objectId}"
				),*/
				Comment::COLLECTION => array(
					"label"=>"{who} reacts on your comment on {where}",
					"labelRepeat"=>"{who} react on your comment on {where}",
					"labelMail"=>"{who} reacts on your comment on {where}",
					"labelRepeatMail"=>"{who} react on your comment on {where}",
					"url" => "targetTypeUrl",
					"notifyUser" => true
				)
			),
			"labelArray" => array("who", "where"),
			"settings"=> "default",
			"mail" => array(
				"type"=>"instantly",
				"to" => "author" //If orga or project to members
			),
			"icon" => "fa-heartbeat"
		),
		ActStr::VERB_POST => array(
			"repeat" => true,
			"type" => array(
				"targetIsAuthor" => array(
					"label"=>"{where} publishes a new post",
					"labelRepeat"=>"{where} publishes new posts",
					"labelMail"=>"{who} publishes a new post",
					"labelRepeatMail"=>"{where} publishes new posts",
				),
				"userWall" => array(
					"label"=>"{who} writes a post on your wall",
					"labelRepeat"=>"{who} write posts on your wall",
					"labelMail"=>"{who} writes a post on your wall",
					"labelRepeatMail"=>"{who} write posts on your wall",
					"sameAuthor" => array(
						"labelRepeat" => "{who} writes posts on your wall",
						"labelRepeatMail" => "{who} writes posts on your wall"
					)
				),
				"label"=>"{who} writes a post on the wall of {where}",
				"labelRepeat"=>"{who} write posts on the wall of {where}",
				"labelMail"=>"{who} writes a post on the wall of {where}",
				"labelRepeatMail"=>"{who} write posts on the wall of {where}",
				"sameAuthor" => array(
					"labelRepeat" => "{who} writes posts on the wall of {where}",
					"labelRepeatMail" => "{who} writes posts on the wall of {where}",
				)
				
			),
			"settings"=> "default",
			"url" => "page/type/{collection}/id/{id}",
			"labelArray" => array("who", "where"),
			"icon" => "fa-rss"
		),
		ActStr::VERB_ADD => array(
			"type" => array(
				/*"need"=> array(
					"url" => "{ctrlr}/detail/id/{id}"
				),*/
				Poi::COLLECTION => array(
					"url" => "page/type/{objectType}/id/{objectId}",
					"urlRepeat"=>"page/type/{collection}/id/{id}/view/directory/dir/{objectType}",
					"label" => "{who} added a new point of interest on {where}",
					"labelMail" => "{who} added a new point of interest : {what} on {where}",
					"labelRepeat" => "{who} have added points of interest on {where}",
					"labelRepeatMail" => "{who} have added ponts of interst on {where}",
					"sameAuthor" => array(
						"labelRepeat" => "{who} added points of interest on {where}"
					)
				),
				Project::COLLECTION => array(
					"url" => "page/type/{objectType}/id/{objectId}",
					"label" => "{who} added a new project on {where}",
					"labelMail" => "{who} added a new project : {what} on {where}",
					"urlRepeat"=>"page/type/{collection}/id/{id}/view/directory/dir/{objectType}",
					"labelRepeat" => "{who} have added new projects on {where}",
					"labelRepeatMail" => "{who} have added new projects on {where}",
					"repeat"=>true,
					"sameAuthor" => array(
						"labelRepeat" => "{who} added new projects on {where}"
					)
				),
				Event::COLLECTION=> array(
					"url" => "page/type/{objectType}/id/{objectId}",
					"label" => "{who} added a new event on {where}",
					"labelMail" => "{who} added a new event : {what} on {where}",
					"urlRepeat"=>"page/type/{collection}/id/{id}/view/directory/dir/{objectType}",
					"labelRepeat" => "{who} have added new events on {where}",
					"labelRepeatMail" => "{who} have added new events on {where}",
					"repeat"=>true,
					"sameAuthor" => array(
						"labelRepeat" => "{who} added new events on {where}"
					)
				),
				Classified::COLLECTION=> array(
					"url" => "page/type/{objectType}/id/{objectId}",
					"label" => "{who} added a new classified on {where}",
					"labelMail" => "{who} added a new classified : {what} on {where}",
					"urlRepeat"=>"page/type/{collection}/id/{id}/view/directory/dir/{objectType}",
					"labelRepeat" => "{who} have added new classifieds on {where}",
					"labelRepeatMail" => "{who} have added new classifieds on {where}",
					"repeat"=>true,
					"sameAuthor" => array(
						"labelRepeat" => "{who} added new classifieds on {where}"
					)	
				),
				/*ActionRoom::COLLECTION_ACTIONS=> array(
					"url"=>"rooms/actions/id/{objectId}",
					"label"=> "{who} added a new actions list on {where}"
				),
				ActionRoom::TYPE_DISCUSS => array(
					"url"=>"comment/index/type/actionRooms/id/{objectId}",
					"label" => "{who} added a new discussion room on {where}"
				),*/
				/*ActionRoom::TYPE_VOTE => array(
					"url"=>"survey/entries/id/{objectId}",
					"label" => "{who} added a new voting room on {where}"
				),*/
				Room::COLLECTION => array(
					"url"=>"page/type/{collection}/id/{id}/view/coop/room/{objectId}",
					"label" => "{who} added a new room in the co-space on {where}",
					"labelRepeat" => "{who} added new rooms in the co-space on {where}",
					"labelMail" => "{who} added a new room : {what} on {where}",
					"labelRepeatMail" => "{who} have added new rooms in the co-space on {where}",
					"sameAuthor" => array(
						"labelRepeat" => "{who} added new rooms in the co-space on {where}"
					),
					"repeat"=>true
				),
				Proposal::COLLECTION => array(
					"url" => "page/type/{collection}/id/{id}/view/coop/room/{roomId}/proposal/{objectId}",
					"urlRepeat"=>"page/type/{collection}/id/{id}/view/coop",
					"label"=> "{who} added a new proposal {what} in {where}",
					"labelMail" => "{who} added a new proposal : {what} in {where}",
					"labelRepeat" => "{who} have added few proposals in {where}",
					"labelRepeatMail" => "{who} added few proposals in {where}",
					"sameAuthor" => array(
						"labelRepeat" => "{who} added few proposals in {where}"
					),
					"repeat"=>true
				),
				Action::COLLECTION => array(
					"url" => "page/type/{collection}/id/{id}/view/coop/room/{roomId}/action/{objectId}",
					"urlRepeat"=>"page/type/{collection}/id/{id}/view/coop",
					"label" => "{who} added a new action {what} in {where}",
					"labelMail" => "{who} added a new action : {what} in {where}",
					"labelRepeat" => "{who} added few actions in {where}",
					"labelRepeatMail" => "{who} added few actions in {where}",
					"sameAuthor" => array(
						"labelRepeat" => "{who} added few actions in {where}"
					),
					"repeat"=>true
				),
				Resolution::COLLECTION => array(
					"url" => "page/type/{collection}/id/{id}/view/coop/room/{roomId}/resolution/{objectId}",
					"label" => "A new resolution {what} is added in {where}",
					"urlRepeat"=>"page/type/{collection}/id/{id}/view/coop",
					"labelRepeat" => "Few resolutions {what} are in {where}",
					"labelMail" => "The proposal {what} is resolved in {where}",
					"labelRepeatMail" => "Few resolutions {what} are in {where}",
					"repeat"=>true
				),
				"profilImage" => array(
					"url" => "targetTypeUrl",
					"label" => "{who} added a new profil image on {where}"
				),
				"albumImage" => array(
					"url" => "page/type/{collection}/id/{id}/view/gallery",
					"label" => "{who} added new images to the album of {where}",
					"repeat" => true,
					"noUpdate" => true
				),
				"asMember"=> array(
					"url" => "page/type/{collection}/id/{id}/view/directory/dir/{connectAs}",
					"label" => "{author} added {who} in the community of {where}",
					"labelRepeat" => "{author} have added {who} in the community of {where}",
					"labelMail" => "{author} added {who} in the community of {where}",
					"labelRepeatMail" => "{author} have added {who} in the community of {where}",
					"sameAuthor" => array(
						"labelRepeatMail" => "{author} added {who} in the community of {where}",
						"labelRepeat" => "{author} added {who} in the community of {where}"
					),
					"repeat" => true
				),
				"chat" => array(
					"url" => "page/type/{collection}/id/{id}",
					"label" => "{who} added a chat on {where}"
				),
			),
			/*"context" => array(
				"members" => array(
					"mail" => array(
						"type"=>"instantly",
						"to" => "members"
					)
				),
				"city" => true
			),*/
			//"label"=>"{who} added {type} {what} in {where}",
			"settings"=> "default",
			"labelArray" => array("who","where","what", "author"),
			"icon" => "fa-plus"
		),
		ActStr::VERB_VOTE => array(
			"repeat" => true,
			"label" => "{who} voted on {what} in {where}",
			"labelRepeat"=>"{who} have voted on {what} in {where}",
			"labelMail" => "{who} voted on {what} in {where}",
			"labelRepeatMail"=>"{who} have voted on {what} in {where}",
			"labelArray" => array("who", "what", "where"),
			"sameAuthor" => array(
				"labelRepeat" => "{who} voted few times on {what} in {where}"
			),
			"icon" => ActStr::ICON_VOTE,
			"url" =>  "page/type/{collection}/id/{id}/view/coop/room/{roomId}/proposal/{objectId}",
			"settings"=> "default",
		),
		ActStr::VERB_AMEND => array(
			"repeat" => true,
			"label" => "{who} amended the proposal {what} in {where}",
			"labelRepeat"=>"{who} have amended the proposal {what} in {where}",
			"labelMail" => "{who} amended the proposal {what} in {where}",
			"labelRepeatMail"=>"{who} have amended the proposal {what} in {where}",
			"labelArray" => array("who","what","where"),
			"sameAuthor" => array(
				"labelRepeat" => "{who} amended few times the proposal {what} in {where}"
			),
			
			"icon" => ActStr::ICON_VOTE,
			"url" =>  "page/type/{collection}/id/{id}/view/coop/room/{roomId}/proposal/{objectId}",
			"settings"=> "default",
		),

		/*
		"ActStr::VERB_UPDATE" => array(
			"repeat" => true,
			"context" => "community",
			"mail" => array(
				"type"=>"daily",
				"to" => "members"
			),
			"label"=>"{who} modified {what} of {where}",
			"labelRepeat"=>"{who} confirmed the invitation to join {where}",
			"labelArray" => array("who","what","where"),
			"icon" => "fa-cog",
			"url" => "{whatController}/detail/id/{whatId}"
		),*/
		ActStr::VERB_CONFIRM => array(
			"repeat" => true,
			"type"=>array(
				"asMember"=>array(
					"label" => "{who} confirmed the invitation to join {where}",
					"labelMail" => "{who} confirmed the invitation to join {where}",
					"labelRepeat" => "{who} have confirmed the invitation to join {where}",
					"labelRepeatMail" => "{who} have confirmed the invitation to join {where}",
				),
				"asAdmin"=>array(
					"label" => "{who} confirmed the invitation to administrate {where}",
					"labelRepeat" => "{who} have confirmed the invitation to administrate {where}",
					"labelMail" => "{who} confirmed the invitation to administrate {where}",
					"labelRepeatMail" => "{who} confirmed the invitation to administrate {where}",
				)
			),
			"labelArray" => array("who","where"),
			"icon" => "fa-check",
			"url" => "page/type/{collection}/id/{id}/view/directory/dir/{connectAs}",
			"settings"=> "high",
		),
		//FROM USER LINK TO AN ELEMENT ACTING ON IT
		ActStr::VERB_INVITE => array(
			"repeat" => true,
			"notifyUser" => true,
			"type" => array(
				"asMember" => array(
					"to"=> "members",
					"label"=>"{author} invited {who} to join {where}",
					"labelRepeat"=>"{author} invited {who} to join {where}",
					"labelMail"=>"{author} invited {who} to join {where}",
					"labelRepeatMail"=>"{author} invited {who} to join {where}"
				),
				"asAdmin" => array(
					"to"=> "members",
					"label"=>"{author} invited {who} to administrate {where}",
					"labelRepeat"=>"{author} invited {who} to administrate {where}",
					"labelMail"=>"{author} invited {who} to administrate {where}",
					"labelRepeatMail"=>"{author} invited {who} to administrate {where}"
				),
				"user" => array(
					"tpl" => "inviteYouTo",
					"settings"=> "low",
					"asMember" => array(
						"label"=>"{author} invited you to join {where}"
					),
					"asAdmin" => array(
						"label"=>"{author} invited you to administrate {where}"
					)
				)
			),
			"labelArray" => array("author","who","where"),
			"context" => "admin",
			"settings"=> "high",
			// "mail" => array(
			// 	"type"=>"instantly",
			// 	"to" => "user"
			// ),
			"icon" => "fa-send",
			"url" => "page/type/{collection}/id/{id}"
		),
		// AJouter la confirmation vers l'utilisateur
		//Creer le mail pour l'utilisateur accepté !!
		ActStr::VERB_ACCEPT => array(
			"repeat" => true,
			//"context" => array("user","members"),
			"notifyUser" => true,
			"mail" => array(
				"type"=>"instantly",
				"to" => "invitor"
			),
			"type" => array(
				"asMember" => array(
					"to"=> "members",
					"label"=>"{author} confirmed {who} to join {where}",
					"labelRepeat"=>"{author} confirmed {who} to join {where}",
					"labelMail"=>"{who} confirmed {what} to join {where}",
					"labelRepeatMail"=>"{who} confirmed {what} to join {where}",
				),
				"asAdmin" => array(
					"to"=> "members",
					"label"=>"{author} confirmed {who} to administrate {where}",
					"labelRepeat"=>"{author} confirmed {who} to administrate {where}",
					"labelMail"=>"{who} confirmed {what} to administrate {where}",
					"labelRepeatMail"=>"{who} confirmed {what} to administrate {where}"
				),
				"user" => array(
					"tpl" => "confirmYouTo",
					"asMember" => array(
						"label"=>"{author} confirmed your request to join {where}",
					),
					"asAdmin" => array(
						"label"=>"{author} confirmed your request to administrate {where}"
					)
				)
			),
			"labelArray" => array("author","who","where"),
			"icon" => "fa-check",
			"settings"=> "high",
			"url" => "page/type/{collection}/id/{id}/view/directory/dir/{connectAs}"
		)/*,
		"SIGNIN" => array(
			"repeat" => true,
			"context" => "user",
			"mail" => array(
				"type"=>"instantly",
				"to" => "invitor"
			)
		)*/
	);
	/****************
	* MOVE TO PREFERENCES
	****************/
	public static function checkUserNotificationPreference($verb, $settings, $id){
		$person = Element::getElementById( $id, Person::COLLECTION, null, array("name", "email","preferences","language"));
	 	if(@$person["preferences"]["notifications"]){
	 		$add["notifications"]=true;
	 		foreach($person["preferences"]["notifications"] as $key => $value){
	 			if(strpos($verb,$key)!==false)
	 				$add["notifications"]=$value;
	 		}
	 	}else
	 		$add["notifications"]=true;
	 	if(@$person["preferences"]["mails"]){
    		if($settings=="high" && $person["preferences"]["mails"]=="high")
    			$add["email"]=true;
    		else if($settings=="default" && in_array($person["preferences"]["mails"],["default","high"]))
    			$add["email"]=true;
    		else if($settings=="low" && $person["preferences"]["mails"] != "desactivated")
    			$add["email"]=true;
    		else
    			$add["email"]=false;
    	}
    	else if(in_array($settings, ["low", "default"]))
    		$add["email"]=true;
    	else
    		$add["email"]=false;
    	if($add["email"]){
    		$add["email"]=$person["email"];
    		$add["language"]=( @$person["language"] ? $person["language"] : "fr" ) ;
    	}

    	$add["id"] = $id;
    	if(@$person["name"])
    		$add["name"] = $person["name"];
    	
		return $add;
	}
	/** TODO BOUBOULE
	* Get admins and member of target to notify
	* params string $id && $type defined the target
	* params string $impact is which part of community is notified
	* params string $authorId is used to avoid to notify author of the action
	* params string $alreadyAuthorNotify could be used if a notification for a specific user is already create
	* return array of id with two boolean for each id, isUnseen && isUnread
	**/
	public static function communityToNotify($construct, $alreadyAuhtorNotify=null, $notificationType="notifications"){
		//inform the entities members of the new member
		//build list of people to notify
		//print_r($construct);
		$type=$construct["target"]["type"];
		$id=$construct["target"]["id"];
		$impactType=Person::COLLECTION;
		$impactRole=null;
		if(@$construct["context"]/* || Event::COLLECTION*/){
			//$impactType=Person::COLLECTION;
			$impactRole="isAdmin";
		}
		$settings=array("type"=>$notificationType, "value"=>$construct["settings"]);
        $peopleNotifs = array();
        $peopleMails = array();
	    $membersToNotifs = array();
	    //$membersToNotifs = array();
	    if(in_array($type, array( Proposal::COLLECTION))){
	    	$prop=Proposal::getById($id);
	    	$type=$prop["parentType"];
	    	$id=$prop["parentId"];
	    }
	    $membersToNotifs = Element::getCommunityByTypeAndId($type, $id ,$impactType, $impactRole, null, array("type"=>"notifications", "value"=>$construct["settings"]));
	    $peopleMails = Element::getCommunityByTypeAndId($type, $id ,$impactType, $impactRole, null, array("type"=>"mails", "value"=>$construct["settings"]));
	    //Rest::json($peopleMails); exit
	    // ADD INVITOR IF NOT IN ADMIN LIST
	    if($type == Event::COLLECTION && $construct["verb"]==ActStr::VERB_CONFIRM && @$construct["target"]["invitorId"] && !@$members[$construct["target"]["invitorId"]]){
	    	$notifUser=self::checkUserNotificationPreference($construct["verb"], $construct["settings"],$construct["target"]["invitorId"]);
			if(@$notifUser["notifications"] && $notifUser["notifications"])
		 		$membersToNotifs[$construct["target"]["invitorId"]]=array();
		 	if(@$notifUser["email"] && !empty($notifUser["email"]))
		 		array_push($peopleMails,$notifUser["email"]);
	    	
	    }
	    if($construct["verb"]==Actstr::VERB_DELETE && $construct["levelType"]==ActStr::VERB_REFUSE){
	    	$userAskingToDelete=Element::getElementSimpleById($id, $type, null,array("userAskingToDelete"));
	    	$userAskingToDelete=$userAskingToDelete["userAskingToDelete"];
	    	if(!@$members[$userAskingToDelete])
	    		$members[$userAskingToDelete]=array();
	    }
		if($type == Person::COLLECTION && ($construct["verb"]==Actstr::VERB_FOLLOW || $construct["verb"]==Actstr::VERB_POST) ){
			$notifUser=self::checkUserNotificationPreference($construct["verb"], $construct["settings"],$id);
			if(@$notifUser["notifications"] && $notifUser["notifications"])
		 		$peopleNotifs[$id] = array("isUnread" => true, "isUnseen" => true);
		 	if(@$notifUser["email"] && !empty($notifUser["email"]))
		 		$peopleMails[$id]=array("email"=>$notifUser["email"], "language"=>$notifUser["language"]);
		}
		else if($type == News::COLLECTION){
			if(Yii::app()->session["userId"] != $alreadyAuhtorNotify){
				$news=News::getById($id);
				$authorNews=News::getAuthor($id);
				if(($alreadyAuhtorNotify != $authorNews["author"] && $news["target"]["type"]==Person::COLLECTION) || $news["target"]["type"] !=Person::COLLECTION /*&& Yii::app()->session["userId"]!=$authorNews["author"])*/){
					if($news["target"]["type"] !=Person::COLLECTION){
						if($news["target"]["id"]){
							$impactRole=null;
						}
						$membersToNotifs = Element::getCommunityByTypeAndId($news["target"]["type"], $news["target"]["id"],$impactType, $impactRole, null, $settings);
						$peopleMails = Element::getCommunityByTypeAndId($news["target"]["type"], $news["target"]["id"], $impactType, $impactRole, null, array("type"=>"mails", "value"=>$construct["settings"]));
					}
					else{
						$notifUser=self::checkUserNotificationPreference($construct["verb"], $construct["settings"], $authorNews["author"]);
						if(@$notifUser["notifications"] && $notifUser["notifications"])
		 					$peopleNotifs[$authorNews["author"]] = array("isUnread" => true, "isUnseen" => true);
		 				if(@$notifUser["email"] && !empty($notifUser["email"]))
		 					$peopleMails[$authorNews["author"]]=array("email"=>$notifUser["email"], "language"=>$notifUser["language"]);
					}
				}
			} 
		} 
		foreach ($membersToNotifs as $key => $value) 
	    {
	    	if( $key != Yii::app()->session['userId'] && !in_array($key, $peopleNotifs) && count($peopleNotifs) < self::PEOPLE_NOTIFY_LIMIT && $key != $alreadyAuhtorNotify && (!@$value["type"] || $value["type"]==Person::COLLECTION)){
	    		$peopleNotifs[$key] = array("isUnread" => true, "isUnseen" => true); 
	    	}
	    }

	    $construct["community"]=array("notifications"=>$peopleNotifs,"mails"=>$peopleMails);
	    return $construct;
	}

	 
	
	/* TODO BOUBOULE
	* Create notification in db ActivityStream
	* return true
	*/
	public static function createNotification($construct, $type=null){
		$asParam = array(
	    	"type" => "notifications", 
            "verb" => $construct["verb"],
            "author"=> array($construct["author"]["id"]=>$construct["author"]),
 			"target"=> $construct["target"]
        );
        if($construct["object"]){
        	if($construct["labelUpNotifyTarget"]=="object")
		       	$asParam["object"] = array($construct["object"]["id"]=>$construct["object"]);
		    else
        		$asParam["object"]=$construct["object"];
        }
 	    $stream = ActStr::buildEntry($asParam);
		$notif = array( 
	    	"persons" => $construct["community"]["notifications"],
            "label"   => self::getLabelNotification($construct,$type),
            "labelArray"=> self::getArrayLabelNotification($construct,$type),
            "labelAuthorObject"=>$construct["labelUpNotifyTarget"],
            "icon"    => $construct["icon"],
            "url"     => self::getUrlNotification($construct)
        );
        if($construct["levelType"])
        	$notif["objectType"]=$construct["levelType"];
        if($type=="user" && $construct["verb"]==Actstr::VERB_INVITE)
        	$notif["objectType"]="userInvitation";
        else if($construct["verb"]==Actstr::VERB_POST && !@$construct["target"]["targetIsAuthor"] && !@$construct["target"]["userWall"])
		    $notif["objectType"]=News::COLLECTION; 
	    $stream["notify"] = ActivityStream::addNotification( $notif );
    	ActivityStream::addEntry($stream);
	}

	/** TODO BOUBOULE  
	* construct notification is the constructor of a notification
	* Firstly this method will create a constructor common for all methods called
	* Secondly it checks if a specific user should be notify in addition to the target community
	* Thirdly it gets community to notyfy
	* Fourthly it checks if notification in this usecase already exists
	* Fively it creates notification
	* params string $verb indicates the verb of notification and the part of notificationTree to get
	* params array $author is in most of case people who executing the action or the person who is concerning by the action
	* params array $target is target of notification 
	* params array $object  is object of notification (could be null)
	* params string levelType indicates if there is subLevel
	* params string||array $context should be use to specify community to notify (only admin, only person, etc)
	*/
	public static function constructNotification($verb, $author, $target, $object = null, $levelType = null, $context = null, $value=null){

		$notificationPart = self::$notificationTree[$verb];
		$notificationPart["verb"] = $verb;
		$notificationPart["target"]=$target;
		$notificationPart["object"]=$object;
		$notificationPart["levelType"]=$levelType;
		$notificationPart["value"]=$value;
		// Object could be the object in following method if action is by an other acting on an other person (ex: author add so as member {"member"=> $author})
		$authorId=(@$author["_id"]) ? (String)$author["_id"] : $author["id"]; 
		$eltauthor = Element::getElementById( $authorId, Person::COLLECTION, null, array("profilThumbImageUrl") );

		$notificationPart["author"]=array(	"id"=>$authorId,
											"name"=>$author["name"],
											"type"=>Person::COLLECTION,
											"profilThumbImageUrl"=>@$eltauthor["profilThumbImageUrl"]);

		//echo '<br><br>' ;var_dump($notificationPart["author"]);
		
		if(	!empty($notificationPart["target"]) && 
			( 	empty($notificationPart["target"]["name"]) || 
				empty($notificationPart["target"]["profilThumbImageUrl"]) ) ) {
			$elt = Element::getElementById( $notificationPart["target"]["id"], $notificationPart["target"]["type"], null, array("name", "title", "slug", "profilThumbImageUrl") );
			$notificationPart["target"]=array_merge($notificationPart["target"],$elt);			
		}
		
		if(!empty($notificationPart["object"]) && empty($notificationPart["object"]["name"])){
			$elt = Element::getElementById( $notificationPart["object"]["id"], $notificationPart["object"]["type"], null, array("name", "title", "text", "slug", "profilMediumImageUrl", "shortDescription") );
			$notificationPart["object"]=array_merge($notificationPart["object"],$elt);
			$object = $notificationPart["object"];
		}

		//Move labelUpToNotify in getLabel
		$notificationPart["labelUpNotifyTarget"] = "author";
		$notificationPart["notifyCommunity"]=true;
		//Specific usecase for comment on proposal
		if(in_array($verb,[Actstr::VERB_COMMENT,Actstr::VERB_REACT]) && 
			in_array($target["type"], [Proposal::COLLECTION, Action::COLLECTION, Resolution::COLLECTION])){
			if($target["type"]==Proposal::COLLECTION)
				$propAct=Proposal::getById($target["id"]);
			else if($target["type"]==Action::COLLECTION)
				$propAct=Action::getById($target["id"]);
			else if($target["type"]==Resolution::COLLECTION)
				$propAct=Resolution::getById($target["id"]);
			$notificationPart["object"]=$notificationPart["target"];
			$notificationPart["target"]=array("type"=>$propAct["parentType"],"id"=>$propAct["parentId"]);
		}

		// Create notification specially for user added to the next notify for community of target
		$notificationPart=self::notifyOnSpecificUser($notificationPart, $object);
		
		// COnstruct notification for target with community
		// Notifiy the community of an element
		$notificationPart = self::communityToNotify($notificationPart, @$notificationPart["alreadyAuhtorNotify"]);
		$update = false;
		if(!empty($notificationPart["community"]) && $notificationPart["notifyCommunity"]){
		    if(	in_array("author",$notificationPart["labelArray"]) && 
		    	( $notificationPart["verb"] != Actstr::VERB_ADD || 
					( @$notificationPart["levelType"] && $notificationPart["levelType"]=="asMember") ) ) {
		       	$notificationPart["object"] = array("id"=>$authorId, "type"=> Person::COLLECTION, "name"=>$author["name"], "profilThumbImageUrl"=>@$eltauthor["profilThumbImageUrl"]);
		        $notificationPart["author"] = array("id"=>Yii::app()->session["userId"], "type"=> Person::COLLECTION, "name"=> Yii::app()->session["user"]["name"], "profilThumbImageUrl"=>@Yii::app()->session["user"]["profilThumbImageUrl"]);
		        $notificationPart["labelUpNotifyTarget"]="object";
		    }
		    // !!!!!!!!!!!!!!! COMMENT HERE VIEW BEHAVIOR IF OBJECT IS CHANGED BEFORE !!!!!!!!!!!!! //
		    //else if($object){
		        //$notificationPart["object"]= array( "id" => $object["id"], "type" => $object["type"], "name" => @$notificationPart["object"]);
		    //}

		    if($notificationPart["verb"]==Actstr::VERB_COMMENT && $notificationPart["levelType"]==Comment::COLLECTION)
		    	$notificationPart["levelType"]=$notificationPart["target"]["type"];

		    //Check if the notification indicates a repeat system 
			if( ( @$notificationPart["repeat"] && $notificationPart["repeat"] ) || 
				( 	@$notificationPart["type"] && 
					@$notificationPart["type"][$levelType] && 
					@$notificationPart["type"][$levelType]["repeat"] ) )
				$update=self::checkIfAlreadyNotifForAnotherLink($notificationPart);
				/********* MAILING PROCEDURE *********/
				/** Update mail notification
				* Modifier le cron si le cron n'est pas déjà envoyé (sinon cf. création mail notification:
					** Ajouté l'object concerné
				* Le cron sera récupéré sur les cinq/dix minutes depuis sa création 
				* Regarder si la communauté notifiée par mail n'a pas vu la notification associée (isUnseen exists)
				* Envoie de l'email
				**/
				/********** END MAIL PROCEDURE ******/
			
			
			if($update==false && !empty($notificationPart["community"]["notifications"])){
				self::createNotification($notificationPart);
				/********* MAILING PROCEDURE *********/
				/** Création mail notification
				* Créer un cron avec:
					** type "notificaitons"
					** Id de la notification
					** object a notifié
					** tpl égale à $notificationPart["mail"]
				* Récupérer les id de la communauté notifiée qui n'est pas connectée (sinon on considère qu'elle a vu la notification)
				* Le cron sera récupéré sur les cinq/dix minutes depuis sa création 
				* Regarder si la communauté notifiée par mail n'a pas vu la notification associée (isUnseen exists)
				* Envoie de l'email
				**/
				/********** END MAILING PROCEDURE *********/
			}
			//Rest::json($notificationPart); exit ;
			$tpl = ( ( @$notificationPart["tpl"] ) ? $notificationPart["tpl"] : null );
			//echo '<br><br>' ;var_dump($notificationPart["author"]); 
			Mail::createNotification($notificationPart, $tpl);

		}
	}

	public static function notifyOnSpecificUser($construct, $object){
			
		if(@$construct["notifyUser"] || (@$construct["type"] && @$construct["type"][$construct["levelType"]] && @$construct["type"][$construct["levelType"]]["notifyUser"])){
			$update=false;
			$isToNotify=true;
			// If answered on comment is the same than on the news or other don't notify twice the author of parent and comment
			if(in_array($construct["verb"],[Actstr::VERB_COMMENT, Actstr::VERB_REACT])){
				if(@$construct["object"] && !empty($construct["object"]))
					$construct["notifyCommunity"]=false;
				$comment=Comment::getById($object["id"]);
				$userNotify=$comment["author"]["id"];
				$commentAuthor=$comment["author"]["id"];
				// Case when user answer to his comment
				// 1 -- If comment's author is the user connected
				if($construct["target"]["type"]==News::COLLECTION && $commentAuthor==Yii::app()->session["userId"])
					$isToNotify=false;

				// 2 -- Go forward in the analysis of news protocol notifications
				if($construct["target"]["type"]==News::COLLECTION){
					$news=News::getById($construct["target"]["id"]);
					$authorNews=News::getAuthor($construct["target"]["id"]);
					// Give the parent target for url construction and label after
					$construct["target"]["parent"]=array("id"=>$news["target"]["id"],"type"=> $news["target"]["type"]);
					$construct["notifyCommunity"]=false;

					if(!@$construct["object"] || empty($construct["object"]))
						$userNotify=$authorNews["author"];
					// 2.1 -- If user notify a news where news' author is organization, project, event
					// 			-- Go forward to notify community except user who do the action
					if((@$news["targetIsAuthor"] || ($news["type"]!="news" && $news["target"]["type"] != Person::COLLECTION)) && empty($construct["object"])){
						$isToNotify=false;
						$construct["notifyCommunity"]=true;
					}
					// 2.2 -- If comment's author is the news' author or there is no comment's author   and news' author is current user
					//		-- Go forward 
					if(/*($commentAuthor!="" && $commentAuthor==$authorNews["author"]) 
						|| (*/$commentAuthor=="" && Yii::app()->session["userId"]==$authorNews["author"]/*)*/){
						$isToNotify=false;
					}
				}
			}
			else
				$userNotify=$construct["author"]["id"]; // Case specific to invitation or accept proccess in a community

			if($isToNotify){
				if(gettype($userNotify)!="string")
					$userNotify=(string)$userNotify["id"];

				$construct["alreadyAuhtorNotify"]=$userNotify;
				$settings = ( ( @$construct["type"] && @$construct["type"]["user"] && @$construct["type"]["user"]["settings"]) ? $construct["type"]["user"]["settings"] : $construct["settings"] );
				$notifUser=self::checkUserNotificationPreference($construct["verb"], $settings, $userNotify);
				if(@$notifUser["notifications"] && $notifUser["notifications"]){
					$construct["community"]["notifications"]=array($userNotify=>array("isUnread" => true, "isUnseen" => true));
					if((@$construct["type"][$levelType] && @$construct["type"][$levelType]["repeat"])
						|| in_array($construct["verb"], [Actstr::VERB_COMMENT,Actstr::VERB_REACT]))
						$update=self::checkIfAlreadyNotifForAnotherLink($construct);
					if($update==false){
				 	    if(@$construct["type"]["user"])
							$construct["labelUpNotifyTarget"]="object";
						// -------- END MOVE ON GETLABEL --------///
						// !!!!!!!!!!!!!!!CAREFULLY !!!!!!!!!!!!
						$construct["author"]=array("id"=>Yii::app()->session["userId"], "type"=> Person::COLLECTION, "name"=> Yii::app()->session["user"]["name"], "profilThumbImageUrl"=> Yii::app()->session["user"]["profilThumbImageUrl"]);
						// !!!!!!!!!!!!!!!CAREFULLY END!!!!!!!!!!!!
						self::createNotification($construct,"user");
				    }
				}

				//Rest::json($construct); exit ;

				if(!empty($notifUser["email"])){
					$tpl = ( ( @$construct["type"] && @$construct["type"]["user"] && @$construct["type"]["user"]["tpl"]) ? $construct["type"]["user"]["tpl"] : null );

			    	//$construct["community"]["mails"] = array($userNotify=>array("email" => $notifUser["email"], "language" => $notifUser["language"] ));

			    	$construct["community"]["mails"] = array($userNotify=>$notifUser );
			    	
			    	Mail::createNotification($construct, $tpl);
				}
			} 
		}
		return $construct;
	}
	/**********************************
	* This function will check if a notification respecting same params is already existed
	* Return false if not existed
	* Else it will update the label of the notification and parameters wanted
	***********************************/ 
	// BOUBOULE : J'ai enlever $isUserNotif=false car il n'etait pas utiliser dans le code
	public static function checkIfAlreadyNotifForAnotherLink($construct)	{
		
		$notification = self::getNotificationByConstruct($construct) ;

		if(!empty($notification)){
			if( @$construct["type"] && 
				@$construct["type"][$construct["levelType"]] && 
				@$construct["type"][$construct["levelType"]]["noUpdate"])
				return true;
			else{
				$countRepeat=1;
				//BOUBOULE - UNDERSTAND WHY HERE
				/*if($notification["verb"] == Actstr::VERB_ADD){
					foreach($notification["author"] as $key => $i){
						if($key == Yii::app()->session["userId"]){
							$sameAuthor=true;
						}else
							$sameAuthor=false;
					}
				}

				foreach($notification[$construct["labelUpNotifyTarget"]] as $key => $i){
					if(($notification["verb"] != Actstr::VERB_POST && $notification["verb"] != Actstr::VERB_COMMENT) || ($key != Yii::app()->session["userId"]))
						$countRepeat++;
				}*/
				//// INSTEDA OF THIS
				foreach($notification[$construct["labelUpNotifyTarget"]] as $key => $i){
					if($key != Yii::app()->session["userId"] /* || ($notification["verb"] != Actstr::VERB_POST && $notification["verb"] != Actstr::VERB_COMMENT) */)
						$countRepeat++;
				}
				/// OOOOOOOOK
				if($countRepeat==1)
					$sameAuthor=true;
				if(@$construct["type"][$construct["levelType"]]["urlRepeat"])
					$newUrl=self::getUrlNotification($construct, $construct["type"][$construct["levelType"]]["urlRepeat"]);
				// Get new Label
				$newLabel=self::getLabelNotification($construct, null, $countRepeat, $notification, "Repeat", @$sameAuthor);
				$arrayLabel=self::getArrayLabelNotification($construct, null, $countRepeat, $notification, "Repeat", @$sameAuthor);
				// Add new author to notification
				if($construct["labelUpNotifyTarget"] == "object")
					//foreach($construct["object"] as $key => $data){
					$notification["object"][$construct["object"]["id"]]=$construct["object"];
					//}
				else
					$notification["author"][Yii::app()->session['userId']]=array("name" => Yii::app()->session['user']['name'], "profilThumbImageUrl" => Yii::app()->session['user']['profilThumbImageUrl']);
				
				$set=array(
					$construct["labelUpNotifyTarget"]=>$notification[$construct["labelUpNotifyTarget"]],
					"notify.labelArray"=>$arrayLabel,
					"notify.id" => $construct["community"]["notifications"],
					"notify.displayName"=> $newLabel,
					"notify.repeat"=> true,
					"notify.label"=>$notification["notify"]["displayName"],
					"notify.labelAuthorObject"=>$construct["labelUpNotifyTarget"],
					"updated" => new MongoDate(time())
				);
				if(@$newUrl) $set["notify.url"]=$newUrl;
				PHDB::update(ActivityStream::COLLECTION,
					array("_id" => $notification["_id"]),
					array('$set' => $set)
				);
				return true;
			}
		}
		else
			return false;
	}
	/*************
	* Check if a notification is already existing depending to the constructor of notification
	*	Params always checked:
	*		-- Same verb of notification
	*		-- Same target
	*		-- Check if notification is more recent than 7 days
	*	Params with usecase
	*		-- labelUpNotifyTarget is object (comment, react and DDA stuff
	*			--- check if it is the author of the new action
	*			--- accept demand of someone => check if object exist
	*		-- The same LevelType 
	*		-- The same object for an comment or a reaction (like/unlike)  
	*/
	public static function getNotificationByConstruct($construct)	{
		$where=array("verb"=>$construct["verb"], "target.id"=>$construct["target"]["id"], "target.type"=>$construct["target"]["type"],"updated"=>array('$gte'=>new MongoDate(strtotime('-7 days', time()))));
		if($construct["labelUpNotifyTarget"]=="object")
			$where["author.".Yii::app()->session["userId"]] = array('$exists' => true);
		if($construct["labelUpNotifyTarget"]=="object" && $construct["verb"]==ActStr::VERB_ACCEPT)
			$where["object"] = array('$exists' => true);
		if($construct["levelType"])
			$where["notify.objectType"] = $construct["levelType"];
        else if($construct["verb"]==Actstr::VERB_POST && !@$construct["target"]["targetIsAuthor"] && !@$construct["target"]["userWall"])
		    $where["notify.objectType"]=News::COLLECTION;
		if($construct["object"] && !empty($construct["object"]) && in_array($construct["verb"], [Actstr::VERB_COMMENT, Actstr::VERB_REACT])){
			$where["object.id"] = $construct["object"]["id"];
			$where["object.type"] = $construct["object"]["type"];
		}
		$notification = PHDB::findOne(ActivityStream::COLLECTION, $where);

		return $notification ;
	}
	/****************************************************
	* function aims to return the url of the notification
	* Check dynamic variable into notification
	* Applied right value foreach specific url
	******************************************/ 
	public static function getUrlNotification($construct, $urlRepeat=null){
		if(@$urlRepeat && !empty($urlRepeat)){
			$url=$urlRepeat;
		}else{
			if(@$construct["url"])
				$url=$construct["url"];
			else 
				$url=$construct["type"][$construct["levelType"]]["url"];
		}

		if($url=="targetTypeUrl"){
			if(in_array($construct["verb"],[Actstr::VERB_COMMENT, Actstr::VERB_REACT])
				&& @$construct["object"] 
				&& in_array($construct["object"]["type"],[Proposal::COLLECTION, Action::COLLECTION,Resolution::COLLECTION]))
				$url=$construct["type"][$construct["object"]["type"]]["url"];
			else
				$url=$construct["type"][$construct["target"]["type"]]["url"];
		}
		$url = str_replace("{ctrlr}", Element::getControlerByCollection($construct["target"]["type"]), $url);
		$url = str_replace("{collection}", $construct["target"]["type"], $url);
		$url = str_replace("{id}", $construct["target"]["id"], $url);
		if(stripos($url, "{connectAs}") > 0)
			$url = str_replace("{connectAs}", Element::$connectTypes[$construct["target"]["type"]], $url);
		if(stripos($url, "{objectType}") > 0)
			$url = str_replace("{objectType}", $construct["object"]["type"], $url);
		if(stripos($url, "{objectId}") > 0)
			$url = str_replace("{objectId}", $construct["object"]["id"], $url);
		if(stripos($url, "{roomId}") > 0){
			//$objTarget=
			//if($construct["object"]["type"]==Comment::COLLECTION)
			if($construct["object"]["type"]==Action::COLLECTION)
				$actionSpec=Action::getSimpleSpecById($construct["object"]["id"],null,array("idParentRoom"));
			else if($construct["object"]["type"]==Proposal::COLLECTION)
				$actionSpec=Proposal::getSimpleSpecById($construct["object"]["id"],null,array("idParentRoom"));
			else if($construct["object"]["type"]==Resolution::COLLECTION)
				$actionSpec=Resolution::getSimpleSpecById($construct["object"]["id"],null,array("idParentRoom"));
			$url = str_replace("{roomId}", @$actionSpec["idParentRoom"], $url);
		}
		return $url;
	}
	/** TODO BOUBOULE
	* !!!!!???? Should be written on communityToNotify ???!!!!!! 
	* getTargetInbformation is used by getLabelNotification
	* return {where} and {what} values
	**/
	public static function getTargetInformation($id, $type, $object=null,$labelArray=false) {	
	 	$target=array();
	 	if(@$object && @$object["type"] && in_array($object["type"], array( Proposal::COLLECTION, Room::COLLECTION, Action::COLLECTION, Resolution::COLLECTION) ) )
		{
			$roomId = $object["id"];
			if( $object["type"] == Proposal::COLLECTION )
				$target["entry"] = Proposal::getById( $object["id"] );
			else if( $object["type"] == Action::COLLECTION )
				$target["entry"] = Action::getById( $object["id"] );
			else if( $object["type"] == Resolution::COLLECTION )
				$target["entry"] = Resolution::getById( $object["id"] );
			if(@$target["entry"])
				$roomId=@$target["entry"]["idParentRoom"];
			$target["room"] = Room::getById( $roomId );

			if(@$target["room"])
			$target["parent"] = Element::getElementSimpleById($target["room"]["parentId"], $target["room"]["parentType"]); 
		}else if($type=="news"){
			$news=News::getById($id);
			$authorNews=News::getAuthor($id);
			$parent=Element::getElementSimpleById($news["target"]["id"], $news["target"]["type"]);
		} else if(in_array($type, [Organization::COLLECTION, Project::COLLECTION, Event::COLLECTION, Classified::COLLECTION])){
			$parent=Element::getElementSimpleById($id, $type);
		}
		$res=array();
		$res["{what}"] = ["a ".Element::getControlerByCollection($type)];
		if(@$target["name"])
			$res["{where}"]=[$target["name"]];
		else if(@$parent["name"]){
			if($object && @$object["type"] && $object["type"]==Comment::COLLECTION && $type==News::COLLECTION){
				$comment=Comment::getById($object["id"]);
				$res["{where}"]= ($comment["author"]["id"]==$authorNews["author"] && !@$news["targetIsAuthor"]) ? ["your news"] : ["the wall of", $parent["name"]];
			}
			else
				$res["{where}"]=[$parent["name"]];
				
			if($type=="news"){
				if(@$news["title"])
					$res["{what}"]=["&quot;".$news["title"]."&quot;"];
				else if($news["type"]=="activityStream"){ 
					if($news["verb"]!="share"){
						if(@$news["object"]["name"])
							$res["{what}"]=["of creation","&quot;".strtr($news["object"]["name"],0,20)."...&quot;"];
						else if(@$news["object"]["displayName"])
								$res["{what}"]=["of creation","&quot;".strtr($news["object"]["displayName"],0,20)."...&quot;"];							
					}else
						$res["{what}"]=["shared"];
				}
				else{
					$res["{what}"]="";
					if(!empty($news["text"]))	
						$res["{what}"]=["&quot;".substr(@$news["text"], 0, 20)."...&quot;"];
					else if(@$news["media"]){
						if($news["media"]["type"]=="url_content")
							$res["{what}"]=["with the link"];
						else if($news["media"]["type"]=="gallery_files")
							$res["{what}"]=["with the documents shared"];
						else if($news["media"]["type"]=="gallery_images")
							$res["{what}"]=["with the album's images"];
					}	
				}
			}
			else if($object && @$object["type"]){
				$object=Element::getElementSimpleById($object["id"], $object["type"]);
				$res["{what}"]=[@$object["name"]];
			}

		}
		else if (@$target["entry"]){
			$res["{what}"]=(@$target["entry"]["name"]) ? [$target["entry"]["name"]] : [@$target["entry"]["title"]];
			if(@$target["parent"])
				$res["{where}"] = [$target["parent"]["name"]];
		} 
		else if(@$target["room"]){
			$res["{what}"]=[$target["room"]["name"]];
			if(@$target["parent"])
				$res["{where}"] = [$target["parent"]["name"]];
		}
		return $res;
	}
	/** TODO BOUBOULE
	* getLabelNotification will create the specific label for notification to create or update
	* params array $construct is the constructor for a notification
	* params string $type gives if notification is for specific user or for the community
	* params integer $count in case of repeat indicates the number of repetition for a specific notif
	* params array $notification is the existed notification when come from checkIfAlreadyExist method 
	* params string $repeat indicates if label used is normal one or the repeat label
	* return label ready to push in dB
	**/
	public static function getLabelNotification($construct, $type=null, $count=1, $notification=null, $repeat="", $sameAuthor=null){
		//$specifyLabel = array();
		//GetLAbel
		//$type=""; else "Repeat"
		if($type && $construct["levelType"]!=Comment::COLLECTION){
			if($construct["levelType"] == News::COLLECTION)
				$label = $construct["type"][$construct["levelType"]]["label".$repeat];
			else
				$label = $construct["type"]["user"][$construct["levelType"]]["label".$repeat];
		}
		else if($construct["levelType"]){
 	    	if(@$target["targetIsAuthor"]){
 	    		if($sameAuthor)
 	    			$label = $construct["type"][$construct["levelType"]]["targetIsAuthor"]["sameAuthor"]["label".$repeat];
 	    		else
					$label = $construct["type"][$construct["levelType"]]["targetIsAuthor"]["label".$repeat];
 	    	}
			else if(!@$construct["type"][$construct["levelType"]]["label"]){
				if($sameAuthor)
					$label = $construct["type"][$construct["levelType"]][$construct["target"]["type"]]["sameAuthor"]["label".$repeat];
				else
					$label = $construct["type"][$construct["levelType"]][$construct["target"]["type"]]["label".$repeat];
			}
			else{
				if($sameAuthor && @$construct["type"][$construct["levelType"]]["sameAuthor"])
					$label = $construct["type"][$construct["levelType"]]["sameAuthor"]["label".$repeat];
				else
					$label = $construct["type"][$construct["levelType"]]["label".$repeat];
				//Specific case for comment, like, unlike on news
				if($construct["levelType"]==News::COLLECTION){
					$news=News::getById($construct["target"]["id"]);
					if($news["target"]["type"] != Person::COLLECTION){
						if($sameAuthor)
							$label = $construct["type"][$construct["levelType"]]["targetIsAuthor"]["sameAuthor"]["label".$repeat];
						else
							$label = $construct["type"][$construct["levelType"]]["targetIsAuthor"]["label".$repeat];
					}
				}
			}
 	    	//$notifyObject=$typeAction;
 	    }
 	    // CASE FOR NEWS
		else if (!@$construct["label".$repeat]){
			if(@$construct["target"]["targetIsAuthor"])
				$label = $construct["type"]["targetIsAuthor"]["label".$repeat];
			else if(@$construct["target"]["userWall"]){
				if($sameAuthor)
					$label = $construct["type"]["userWall"]["sameAuthor"]["label".$repeat];
				else
					$label = $construct["type"]["userWall"]["label".$repeat];
			}
			else{
				if($sameAuthor)
					$label = $construct["type"]["sameAuthor"]["label".$repeat];
				else
					$label = $construct["type"]["label".$repeat];
			}
		}
		else
			$label = $construct["label".$repeat];
		
		return $label;
	}
	/******************************************
	* Construction of the array which gives the dynamic variables to replace in the label
	* Correlate to the fuction @translateLabel
	***************************************/
	public static function getArrayLabelNotification($construct, $type=null, $count=1, $notification=null, $repeat="", $sameAuthor=null){
		$specifyLabel=array();
		if($construct["labelUpNotifyTarget"]=="object"){
			$memberName="";
			if($construct["object"]){
				if(@$construct["object"]["name"])
					$memberName=$construct["object"]["name"];
				else{
					foreach($construct["object"] as $user){
						$memberName=$user["name"];
					}
				}
			}
			$specifyLabel["{author}"] = [Yii::app()->session['user']['name']];
		}else {
			$memberName=Yii::app()->session['user']['name'];
		}

		$specifyLabel["{who}"] = [$memberName];

		if($count>1){
			foreach($notification[$construct["labelUpNotifyTarget"]] as $key=> $data){
				if($key!=Yii::app()->session["userId"]){
					$lastAuthorName=$data["name"];
					break; 
				}
			}
			array_push($specifyLabel["{who}"],$lastAuthorName);
			if($count >2){
				$nbOthers = $count - 2;
				array_push($specifyLabel["{who}"],$nbOthers);
			}
		}

		if(in_array("where",$construct["labelArray"])){
			if(@$construct["target"]["name"])
				$specifyLabel["{where}"] = [$construct["target"]["name"]];
			else{
				$resArray=self::getTargetInformation($construct["target"]["id"],$construct["target"]["type"], $construct["object"],true);
				$specifyLabel["{where}"] = @$resArray["{where}"];
				if(@$resArray["{what}"])
					$specifyLabel["{what}"]=$resArray["{what}"];
			}
		}
		if(in_array("what",$construct["labelArray"])){
			$nameWhat=(@$construct["object"]["title"]) ? @$construct["object"]["title"] : @$construct["object"]["name"];
			$specifyLabel["{what}"] = [$nameWhat];
		}
		
		return $specifyLabel;
	}
	/****************************************
	* !! CAREFULLY - THIS FUNCTION IS TO REPEAT IN EACH TECHNOLOGY USE OR INSTANCE (EX : METEOR APPLICATION COMOBI [cf: Thomas Craipeau]) !! 
	* construct notification label with the right language
	* interprete the label array given and the label entry with dynamics variables
	* permits to give translated notification for view depending to the current user's language's configuration  
	*****************************************/
	public static function translateLabel($notif){
		$resArray=array();
		if(@$notif["notify"]["labelArray"]){
			if(@$notif["notify"]["labelArray"]["{author}"] && !empty($notif["notify"]["labelArray"]["{author}"])){
				$author="";
				$i=0;
				$countEntry=count($notif["notify"]["labelArray"]["{author}"]);
				foreach($notif["notify"]["labelArray"]["{author}"] as $data){
					if($i == 1 && $countEntry==2)
						$author.=" ".Yii::t("common","and")." ";
					else if($i > 0)
						$author.=", ";
					if($i==2 && is_numeric($data)){
						$s="";
						if($data > 1)
							$s="s";
						$author.=" ".Yii::t("common","and")." ".$data." ".Yii::t("common", "person".$s);
					}else
						$author.=$data;
					$i++;
				}
				$resArray["{author}"]=$author;
			}
			if(@$notif["notify"]["labelArray"]["{who}"] && !empty($notif["notify"]["labelArray"]["{who}"])){
				$who="";
				$i=0;
				$countEntry=count($notif["notify"]["labelArray"]["{who}"]);
				foreach($notif["notify"]["labelArray"]["{who}"] as $data){
					if($i == 1 && $countEntry==2)
						$who.=" ".Yii::t("common","and")." ";
					else if($i > 0)
						$who.=", ";
					if($i==2 && is_numeric($data)){
						$s="";
						if($data > 1)
							$s="s";
						$typeMore="person";
						if($notif["verb"]==ActStr::VERB_ADD && @$notif["notify"]["objectType"] && $notif["notify"]["objectType"]=="asMember")
							$typeMore="organization";
						$who.=" ".Yii::t("common","and")." ".$data." ".Yii::t("common", $typeMore.$s);
					}else
						$who.=$data;
					$i++;
				}
				$resArray["{who}"]=$who;
			}
			if(@$notif["notify"]["labelArray"]["{what}"] && !empty($notif["notify"]["labelArray"]["{what}"])){
				$what="";
				$i=0;
				foreach($notif["notify"]["labelArray"]["{what}"] as $data){
					if($i > 0)
						$what.=" ";
					$what=Yii::t("notification",$data);
					$i++;
				}
				$resArray["{what}"]=$what;
			}
			if(@$notif["notify"]["labelArray"]["{where}"] && !empty($notif["notify"]["labelArray"]["{where}"])){
				$where="";
				$i=0;
				foreach($notif["notify"]["labelArray"]["{where}"] as $data){
					if($i > 0)
						$where.=" ";
					$where=Yii::t("notification",$data);
					$i++;
				}
				$resArray["{where}"]=$where;
			}
		}
		return Yii::t("notification",$notif["notify"]["displayName"], $resArray);
	}
	private static function array_column($array,$column_name)
    {
        return array_map(function($element) use($column_name){return $element[$column_name];}, $array);

    }
    public static function translateMentions($notif){
    	$where=Yii::t("notification","in a news");
		if(@$notif["object"] && !empty($notif["object"]))
			$where=Yii::t("notification","in a comment");
		foreach($notif["author"] as $data)
			$authorName=$data["name"];
		if($notif["notify"]["type"]==Person::COLLECTION){
			if(empty($notif["notify"]["mentions"]))
				$mentionsLabel="";
			else
				$mentionsLabel=Yii::t("notification", "with {who}", array("{who}"=>$notif["notify"]["mentions"][0]));
		}else{
			if(count($notif["notify"]["mentions"])==1)
				$mentionsLabel=$notif["notify"]["mentions"][0];
			else
				$mentionsLabel=$notif["notify"]["mentions"][0]." ".Yii::t("common", "and")." ".$notif["notify"]["mentions"][1];
		}
		return Yii::t("notification",$notif["notify"]["displayName"],array("{who}"=>$authorName,"{mentions}"=>$mentionsLabel,"{where}"=>$where));	
    }
    // TODO BOUBOULE => Mention in news // comment (à développer)
    // A RENOMER mentionNotification
	public static function notifyMentionOn ($author, $target, $mentions, $object=null) 
	{
		$arrayLabel=array(
			"you"=>"{who} mentionned you {mentions} {where}",
			"other"=>"{who} mentionned {mentions} {where}",
		);
		$verb=ActStr::VERB_MENTION;	
		$icon=ActStr::ICON_RSS;
		$notification=array();
		$url = 'page/type/'.$target["type"].'/id/'.$target["id"];
		$people=array();
		$news=News::getByid($target["id"]);
		$labelArray=array("{where}"=>["in a news"],"{who}"=>[$author["name"]]);
		if(@$object && !empty($object))
			$labelArray["{where}"]=["in a comment"];
		$scope=$news["scope"];
		if($scope=="private"){
			if($news["target"]["type"]=Person::COLLECTION)
				return true;
			else if( $news["target"]["type"] == Project::COLLECTION )
	    		$members = Project::getContributorsByProjectId($news["target"]["id"]);
	   		else if( $news["target"]["type"] == Organization::COLLECTION)
	    		$members = Organization::getMembersByOrganizationId( $news["target"]["id"]) ;
	   		else if( $news["target"]["type"] == Event::COLLECTION )
	    		$members = Event::getAttendeesByEventId( $news["target"]["id"] , "admin", "isAdmin" ) ;
		}
		foreach ($mentions as $data){
			if($data["type"]==Person::COLLECTION){
				if($scope!="private" || @$members[$data["id"]]){
					// si l'id du mention est déjà présent dans une notif alors une orga, ou projet à déjà été notifié 
					$alreadyNotify=false;
					if(!empty($notification)){//} && array_search($data["id"], self::array_column($notification, 'persons'))){

				    	foreach($notification as $i => $list){
					    	foreach($list["persons"] as $id => $v){
						    	if($id==$data["id"]){
						    		$alreadyNotify=true;
						    		$mentionsArray=[$list["nameElement"]];
						    		$labelArray["{mentions}"]=["with",$list["nameElement"]];
							    	//$mentionsLabel=Yii::t("notification", "with {who}", array("{who}",$list["nameElement"]));
									if(count($notification[$i]["persons"])>1)
										unset($notification[$i]["persons"][$data["id"]]);
									else
										unset($notification[$i]);
						    	}
					    	}
				    	}
			    	}
			    	if(!$alreadyNotify){
			    		$labelArray["{mentions}"]="";
		    			//$mentionsLabel="";
		    			$mentionsArray=[];
				    }
				   	$pushNotif=array(
					    "type"=> Person::COLLECTION,
					    "persons"=>array($data["id"]=>array("isUnseen"=>true,"isUnread"=>true)),
					    "label"=> $arrayLabel["you"],
					    "labelArray"=>$labelArray,
					    //Yii::t("notification",$arrayLabel["you"],array("{who}"=>$author["name"],"{mentions}"=>$mentionsLabel,"{where}"=>$where)),
					    "mentions"=>$mentionsArray,
					    "url"=> $url,
					    "icon" => $icon
					);
					array_push($notification, $pushNotif);
				}
			}
			else{
				if($scope!="private"){
					if($data["type"]==Organization::COLLECTION)
						$community = Organization::getMembersByOrganizationId( $data["id"], Person::COLLECTION , "all" );
					else
						$community = Project::getContributorsByProjectId( $data["id"], Person::COLLECTION );
					$people=array();
				    foreach ($community as $key => $value) 
				    {
				    	if( $key != Yii::app()->session['userId'] /* /*&& count($people) < self::PEOPLE_NOTIFY_LIMIT*/ ){
					    	$people[$key]=array("isUnseen"=>true,"isUnread"=>true);
					    	if(!empty($notification)){
						    	foreach($notification as $i => $list){
							    	foreach($list["persons"] as $id => $v){
								    	if($id==$key){
									    	if($list["type"]!=Person::COLLECTION){
									    		$mentionsArray=[$data["name"]];
									    		$labelArray["{mentions}"]=$mentionsArray;
									    		if(@$list["nameElement"]){
									    			array_push($mentionsArray, $list["nameElement"]);
									    			$labelArray["{mentions}"]= array($data["name"],"and",$list["nameElement"]);
									    			
									    		}
										    	//$mentionsLabel=$data["name"]." ".Yii::t("common", "and")." ".@$list["nameElement"];
										    	$typeMention=$list["type"];
										    	$labelNotif=$arrayLabel["other"];
										    	unset($notification[$i]["persons"][$key]);
										    	//Yii::t("notification",$arrayLabel["other"],array("{who}"=>$author["name"],"{mentions}"=>$mentionsLabel,"{where}"=>$where));
										    	
									    	}
											else{
												$mentionsArray=[$data["name"]];
												$labelArray["{mentions}"]=["with",$data["name"]];
										    	$mentionsLabel=Yii::t("notification", "with {who}", array("{who}"=>$data["name"]));
										    	$typeMention=Person::COLLECTION;
										    	$labelNotif=$arrayLabel["you"];
										    	//Yii::t("notification",$arrayLabel["you"],array("{who}"=>$author["name"],"{mentions}"=>$mentionsLabel,"{where}"=>$where));
									    	}
									    	$pushNotif=array(
												"type"=> $typeMention,
												"nameElement"=>$data["name"],
												"labelArray"=>$labelArray,
												"nbMention"=>2,
												"persons"=>array($key=>array("isUnseen"=>true,"isUnread"=>true)),
												"mentions"=>$mentionsArray,
												"label"=>$labelNotif, 
												"icon" => $icon,
												"url"=> $url
											);
											if(count($notification[$i]["persons"])>1)
												unset($notification[$i]["persons"][$data["id"]]);
											else
												unset($notification[$i]);
											unset($people[$key]);
											array_push($notification, $pushNotif);
								    	}
							    	}
						    	}
						    }
			    		}	
			    	}
			    	if(count($people)>0){
			    		$labelArray["{mentions}"]=[$data["name"]];
					    $pushNotif=array(
						    "type"=> $data["type"],
						    "nameElement"=>$data["name"],
						    "persons"=>$people,
						    "label"=> $arrayLabel["other"],
						    "labelArray"=>$labelArray,
						    "mentions"=>[$data["name"]],
						    //Yii::t("notification",$arrayLabel["other"],array("{who}"=>$author["name"],"{mentions}"=>$data["name"],"{where}"=>$where)),
						    "url"=> $url,
						    "icon" => $icon 
						);
						array_push($notification, $pushNotif);
					}
				}
			}
		}
		foreach($notification as $notif){
			$asParam = array(
		    	"type" => "notifications", 
	            "verb" => $verb,
	            "author"=>$author,
	            "target"=>$target
	        );
	        if(!empty($object))
	        	$asParam["object"]=$object;
	        $notif["labelAuthorObject"]="mentions";
		    $stream = ActStr::buildEntry($asParam);
		    $stream["notify"] = ActivityStream::addNotification( $notif );
		    ActivityStream::addEntry($stream);
		}
		
	}
	/**
	* Get array of news order by date of creation
	* @param array $array is the array of news to return well order
	* @param array $cols is the array indicated on which column of $array it is sorted
	**/
	public static function sortNotifs($array, $cols){
		$colarr = array();
	    foreach ($cols as $col => $order) {
	        $colarr[$col] = array();
	        foreach ($array as $k => $row) { $colarr[$col]['_'.$k] = strtolower(@$row[$col]); }
	    }
	    $eval = 'array_multisort(';
	    foreach ($cols as $col => $order) {
	        $eval .= '$colarr[\''.$col.'\'],'.$order.',';
	    }
	    $eval = substr($eval,0,-1).');';
	    eval($eval);
	    $ret = array();
	    foreach ($colarr as $col => $arr) {
	        foreach ($arr as $k => $v) {
	            $k = substr($k,1);
	            if (!isset($ret[$k])) $ret[$k] = $array[$k];
	            $ret[$k][$col] = @$array[$k][$col];
	        }
	    }
	    return $ret;
	}


	/*
	when someone joins or leaves or disables a project / organization / event
	notify all contributors

	the action/verb can be done by the person or by an admin (remove from project)
	$verb can be join, leave
	$icon : anicon to show
	$member : a map of the object member , 
		should contain : id ,type, name of the member (person or Orga)
	$target : context of the action (project, orga,event)
	$invitation : adapt notification's text if it's an invitation from someone
	*/
	public static function actionOnPerson ( $verb, $icon, $member, $target, $invitation=false) 
	{
		$targetId = ( isset( $target["id"] ) ) ? $target["id"] : (string)$target["_id"] ;
		if( $member )
			$memberId = ( isset( $member["id"] ) ) ? $member["id"] : (string)$member["_id"] ;
	    $asParam = array(
	    	"type" => ActStr::TEST, 
            "verb" => $verb,
            "author"=>array(
            	"type" => Person::COLLECTION,
            	"id"   => ( isset(Yii::app()->session["userId"]) ) ? Yii::app()->session["userId"] : null
            ),
            "object"=>array(
            	"type" => Person::COLLECTION,
            	"id"   => ( isset(Yii::app()->session["userId"]) ) ? Yii::app()->session["userId"] : null
            ),
            "target"=>array(
	            "type" => $target["type"],
	            "id"   => $targetId
            )
        );
        //build list of people to notify
        $people = array();
        //when admin makes the change
        //notify the people concerned by the entity
        if( isset($memberId) && $memberId != Yii::app()->session["userId"] ){
        	if(@$member['type'] && $member['type'] == Organization::COLLECTION )
        	{
        		$asParam["object"] = array(
		            "type" => Organization::COLLECTION,
		            "id"   => $memberId
	            );

	            //inform the organisations admins
		    	$admins = Organization::getMembersByOrganizationId( $memberId, Person::COLLECTION , "isAdmin" );
			    foreach ($admins as $key => $value) 
			    {
			    	if( $key != Yii::app()->session['userId'] && !in_array($key, $people) && count($people) < self::PEOPLE_NOTIFY_LIMIT )
			    		array_push( $people, $key);
			    }
        	} 
        	else 
        	{ 
	        	$asParam["object"] = array(
		            "type" => Person::COLLECTION,
		            "id"   => $memberId
	            );
	        	array_push( $people, $memberId );
	        }
        }

	    $stream = ActStr::buildEntry($asParam);
	    //inform the entities members of the new member
	    $members = array();
	    if( $target["type"] == Project::COLLECTION ) {
	    	$members = Project::getContributorsByProjectId( $targetId ,"all", null ) ;
			$typeOfConnect="contributor";
	    }
	    else if( $target["type"] == Organization::COLLECTION) {
	    	$members = Organization::getMembersByOrganizationId( $targetId ,"all", null ) ;
	    	$typeOfConnect="member";
	    }
	    else if( $target["type"] == Event::COLLECTION ) {
	    	/**
		    * Notify only the admin of the event
	    	*	- if new attendee or new admin
	    	* Notify all
	    	*	- if a post in event wall
	    	*/
	    	if($verb == ActStr::VERB_POST)
	    		$members = Event::getAttendeesByEventId( $targetId , "all", null ) ;
	    	else
	    		$members = Event::getAttendeesByEventId( $targetId , "admin", "isAdmin" ) ;
	    	$typeOfConnect="attendee";
	    }
		else if($target["type"] == Person::COLLECTION)
			$people = array($targetId);
		else if($target["type"] == News::COLLECTION){
			$author=News::getAuthor($target["id"]);
			$people = array($author["author"]);
		} 
		else if( in_array($target["type"], array( Survey::COLLECTION, ActionRoom::COLLECTION, ActionRoom::COLLECTION_ACTIONS) ) )
		{
			$entryId = $target["id"];
			if( $target["type"] == Survey::COLLECTION ){
				$target["entry"] = Survey::getById( $target["id"] );
				//var_dump($target); echo (string)$target["entry"]["_id"]; return;
				$entryId = (string)$target["entry"]["survey"];
			} else if( $target["type"] == ActionRoom::COLLECTION_ACTIONS ){
				$target["entry"] = ActionRoom::getActionById( $target["id"] );
				//echo "tageettttt ". var_dump($target["entry"]); //return;
				$entryId = $target["entry"]["room"];
				//echo "entryId : ".$entryId;return;
			}

			$room = ActionRoom::getById( $entryId );
			$target["room"] = $room;
			//echo "target : ".$entryId; var_dump($target); return;
			if( @$room["parentType"] ){
				if( $room["parentType"] == Project::COLLECTION ) {
					$target["parent"] = Project::getById( $room["parentId"]);
			    	$members = Project::getContributorsByProjectId( $room["parentId"] ,"all", null ) ;
					$typeOfConnect="contributor";
			    }
			    else if( $room["parentType"] == Organization::COLLECTION) {
			    	$target["parent"] = Organization::getById( $room["parentId"]);
			    	$members = Organization::getMembersByOrganizationId( $room["parentId"] ,"all", null ) ;
			    	$typeOfConnect="member";
			    }
			    else if( $room["parentType"] == Event::COLLECTION ) {
			    	//TODO notify only the admin of the event
			    	$target["parent"] = Event::getById( $room["parentId"]);
			    	if($verb == ActStr::VERB_POST)
		    			$members = Event::getAttendeesByEventId( $room["parentId"] , "all", null ) ;
					else
		    			$members = Event::getAttendeesByEventId( $room["parentId"] , "admin", "isAdmin" ) ;

			    	//$members = Event::getAttendeesByEventId( $room["parentId"],"admin", "isAdmin" ) ;
			    	$typeOfConnect="attendee";
			    } else if( $room["parentType"] == City::COLLECTION ) {
			    	//TODO notify only the admin of the event
			    	$target["parent"] = City::getByUnikey( $room["parentId"]);
			    }
			}
		}
	    foreach ($members as $key => $value) 
	    {
	    	if( $key != Yii::app()->session['userId'] && !in_array($key, $people) && count($people) < self::PEOPLE_NOTIFY_LIMIT )
	    		array_push( $people, $key);
	    }

	    $ctrl = Element::getControlerByCollection($target["type"]);
	    $url = $ctrl.'/detail/id/'.$targetId;

	    if( $verb == ActStr::VERB_CLOSE ){
		    $label = $target["name"]." ".Yii::t("common","has been disabled by")." ".Yii::app()->session['user']['name'];
	    }
	    else if( $verb == ActStr::VERB_POST ){
		    if($target["type"] == Person::COLLECTION)
			    $label = Yii::app()->session['user']['name']." ".Yii::t("common","wrote a message on your wall");
			else	
				$label = $target["name"]." : ".Yii::t("common","new post by")." ".Yii::app()->session['user']['name'];
	    	$url = 'news/index/type/'.$target["type"].'/id/'.$targetId;
	    }
		else if( $verb == ActStr::VERB_FOLLOW ){
			if($target["type"]==Person::COLLECTION)
				$specificLab = Yii::t("common","is following you");
			else
				$specificLab = Yii::t("common","is following")." ".$target["name"];
		    $label = Yii::app()->session['user']['name']." ".$specificLab;
	    	$url = Person::CONTROLLER.'/detail/id/'.Yii::app()->session['userId'];
	    }
	    else if($verb == ActStr::VERB_WAIT){
		    $label = Yii::app()->session['user']['name']." ".Yii::t("common","wants to join")." ".$target["name"];
		    $url = $ctrl.'/directory/id/'.$target["id"].'?tpl=directory2';
	    }
	    else if($verb == ActStr::VERB_AUTHORIZE){
		    $label = Yii::app()->session['user']['name']." ".Yii::t("common","wants to administrate")." ".$target["name"];
		    $url = $ctrl.'/directory/id/'.$target["id"].'?tpl=directory2';
	    }
	    else if($verb == ActStr::VERB_JOIN){
		    $label = Yii::app()->session['user']['name']." ".Yii::t("common","participates to the event")." ".$target["name"];
		    $url = 'event/detail/id/'.$target["id"];
	    }
	    else if($verb == ActStr::VERB_COMMENT ){
		    $label = Yii::t("common","{who} commented your post", array("{who}"=>Yii::app()->session['user']['name']));
		    $url = $ctrl.'/detail/id/'.$target["id"];
		    if( in_array( $target["type"], array( Survey::COLLECTION, ActionRoom::COLLECTION_ACTIONS) ) ){
		    	$label = Yii::t("common","{who} commented on {what}", array("{who}"=>Yii::app()->session['user']['name'],
		    																"{what}"=>$target["entry"]["name"]));
		    	$base = 'survey/entry';
		    	if($target["type"] == ActionRoom::COLLECTION_ACTIONS)
		    		$base = 'rooms/action';
		    	$url = $base.'/id/'.$target["id"];
		    }
	    } 
	    else if($verb == ActStr::VERB_ADDROOM && @$target["parent"]){
		    $label = Yii::t("rooms","{who} added a new Voting Room on {where}",array("{who}"=>Yii::app()->session['user']['name'],
		    																					"{where}"=>$target["parent"]["name"]),Yii::app()->controller->module->id);
		    $url = 'survey/entries/id/'.$target["id"];
		    if( $target['room']["type"] == ActionRoom::TYPE_DISCUSS ){
		    	$label = Yii::t("rooms","{who} added a new Discussion Room on {where}",array("{who}"=>Yii::app()->session['user']['name'],
		    																						"{where}"=>$target["parent"]["name"]),Yii::app()->controller->module->id);
		    	$url = 'comment/index/type/actionRooms/id/'.$target["id"];

		    }else if( $target['room']["type"] == ActionRoom::TYPE_ACTIONS ){
		    	$label = Yii::t("rooms","{who} added a new Actions List on {where}",array("{who}"=>Yii::app()->session['user']['name'],
		    																					"{where}"=>$target["parent"]["name"]),Yii::app()->controller->module->id);
		    	$url = 'rooms/actions/id/'.$target["id"];
		    }
	    }
	    else if($verb == ActStr::VERB_ADD_PROPOSAL){
		    $label = Yii::t("rooms","{who} added a new Proposal {what} in {where}", array("{who}" => Yii::app()->session['user']['name'],
		    																	"{what}"=>$target['entry']["name"],
		    																	"{where}"=>$target['parent']["name"]),Yii::app()->controller->module->id);
		    $url = 'survey/entry/id/'.$target["id"];
	    }
	    else if($verb == ActStr::VERB_ADD_RESOLUTION){
		    $label = Yii::t("rooms","{who} added a new Resolution {what} in {where}", array("{who}" => Yii::app()->session['user']['name'],
		    																	"{what}"=>$target['entry']["name"],
		    																	"{where}"=>$target['parent']["name"]),Yii::app()->controller->module->id);
		    $url = 'survey/entry/id/'.$target["id"];
	    }
	    else if($verb == ActStr::VERB_ADD_ACTION){
	    	$label = Yii::t("rooms","{who} added a new Action {what} in {where}", array("{who}" => Yii::app()->session['user']['name'],
		    																"{what}"=>$target["entry"]["name"],
		    																"{where}"=>$target['parent']["name"]),Yii::app()->controller->module->id);
		    $url = 'rooms/action/id/'.$target["id"];
	    } else if( $verb == ActStr::VERB_VOTE ){
		    $label = Yii::t("rooms","{who} voted on {what} in {where}", array("{who}" => Yii::app()->session['user']['name'],
		    																"{what}"=>$target["entry"]["name"],
		    																"{where}"=>$target['parent']["name"]),Yii::app()->controller->module->id);
		    $url = 'survey/entry/id/'.$target["id"];
	    }
	    /*if( $res = ActStr::getParamsByVerb($verb,$ctrl,$target,Yii::app()->session["user"]){
	    	$label = $res['label'];
	    	$url = $res['url']; 
	    } */
		else if($verb == ActStr::VERB_CONFIRM){
		    $label = Yii::app()->session['user']['name']." ".Yii::t("common","just added")." ".$member["name"]." ".Yii::t("common","as admin of")." ".$target["name"];
		    $url = $ctrl.'/directory/id/'.$target["id"].'?tpl=directory2';
	    }
	    else if($verb == ActStr::VERB_ACCEPT){
		    $label = Yii::app()->session['user']['name']." ".Yii::t("common","just added")." ".$member["name"]." ".Yii::t("common","as ".$typeOfConnect." of")." ".$target["name"];
		    // No directory for event but detail page
		    if ($target["type"] == Event::COLLECTION)
		    	$url = $ctrl.'/detail/id/'.$target["id"];
		    else 
		    	$url = $ctrl.'/directory/id/'.$targetId.'?tpl=directory2';
	    }
		else if($verb == ActStr::VERB_JOIN){
		    $label = Yii::app()->session['user']['name']." ".Yii::t("common","participates to the event")." ".$target["name"];
		    $url = $ctrl.'/detail/id/'.$targetId;
	    }
	    else if($verb == ActStr::VERB_SIGNIN){
			 $label = $member["name"]." ".Yii::t("common","confirms your invitation and create an account.");
			 $url = $ctrl.'/detail/id/'.$memberId;
		} 
		else
	    	$label = Yii::app()->session['user']['name']." ".$verb." you to ".$target["name"] ;

		if($invitation == ActStr::VERB_INVITE && $verb != ActStr::VERB_CONFIRM){
			 $label = Yii::app()->session['user']['name']." ".Yii::t("common","has invited")." ".$member["name"]." ".Yii::t("common","to join")." ".$target["name"];
			 if ($target["type"] == Event::COLLECTION)
		    	$url = $ctrl.'/detail/id/'.$target["id"];
		    else 
			 	$url = $ctrl.'/directory/id/'.$target["id"].'?tpl=directory2';
		}

		
	    $notif = array( 
	    	"persons" => $people,
            "label"   => $label,
            "icon"    => $icon ,
            "url"     => Yii::app()->createUrl('/'.Yii::app()->controller->module->id.'/'.$url ) 
        );
	    $stream["notify"] = ActivityStream::addNotification( $notif );
	    ActivityStream::addEntry($stream);

	    //TODO mail::invited
	}

	/* 
	TODO BOUBOULE => A DECALER OU NON OU RENOMER ACTIVITYSTREAM EMBED CREER LA NEWS ACTIVITY HISTORY
	inject to activity stream
	When a project, or event is create 
	It will appear for person or organization
	// => advanced notification to add if one user wants to be notified for all news projects in certain field (Tags)
	*/
	public static function createdObjectAsParam($authorType, $authorId, $objectType, $objectId, $targetType, $targetId, $geo, $tags, $address, $verb="create"){
		$param=array("type" => ActivityStream::COLLECTION, "verb" => ActStr::VERB_CREATE);
		if (!empty($objectType)){
			$param["object"] = array(
				"type" => $objectType, 
				"id" => $objectId
			);
		}
		if (!empty($targetType)){
			$param["target"] = array(
				"type" => $targetType, 
				"id" => $targetId
			);
		}

		if (!empty($tags))
			$param["tags"]=$tags;
		if(!empty($geo))
			$param["geo"]=$geo;
		if (!empty($address))
			$param["address"]=$address;	
		
		    $param["label"] = "A crée";
		$stream = ActivityStream::buildEntry($param);
	    ActivityStream::addEntry($stream);

	}


	/**
	 * When a moderate is occured, is create notification for author and superadmin
	notify the moderate
	 * @param type $news the news moderated
	 * @return type
	 */
	public static function moderateNews ($news) 
	{
	    $asParam = array(
	    	"type" => ActStr::TEST, 
            "verb" => ActStr::VERB_MODERATE,
            "author"=>array(
            	"type" => Person::COLLECTION,
            	"id"   => ( isset(Yii::app()->session["userId"]) ) ? Yii::app()->session["userId"] : null
            ),
            "object"=>array(
	            "type" => News::COLLECTION,
	            "id"   => (string)$news['_id']
            )
        );

	    $stream = ActStr::buildEntry($asParam);

	    $actionMsg = ($news['isAnAbuse'] == true ) ? "Modération : Votre news postée le ".date('d-m-Y à H:i', $news['created']->sec)." ne sera plus affichée" : "Modération : Votre news postée le ".date('d-m-Y à H:i', $news['created']->sec)." restera affichée";

		$notif = array( 
	    	"persons" => array($news['author']['id']),
            "label"   => $actionMsg , 
            "icon"    => "fa-rss" ,
            "url"     => Yii::app()->createUrl('/'.Yii::app()->controller->module->id.'/news/detail/id/'.@(string)$news['_id'])
        );

	    $stream["notify"] = ActivityStream::addNotification( $notif );
    	ActivityStream::addEntry($stream);
	    
	    //TODO mail::following
	    //add a link to follow back easily
	}

	/**
	 * Notification for the super admins.
	 * Exemple : The cron return a mail error caused by alice@example.com
	 * => The cron is the author
	 * => return is the verb
	 * => A mail error is the object
	 * => alice@example.com is the target
	 * @param String $verb Can be find on const of the ActStr class
	 * @param array $author the one making the action array(type, id)
	 * @param array $object the object. array(type, id, event)
	 * @param array $target the target. array(type, id, email)
	 * @return array : result : boolean / msg : string
	 */
	public static function actionToAdmin ( $verb, $author, $object, $target)  {
 		//Retrieve all super admins of the plateform
 		//TODO SBAR => superAdmins ID should be cached in order to make this request quicker ?
 		$superAdmins = Person::getCurrentSuperAdmins();

 		$asParam = array(
	    	"type" => ActStr::TEST, 
            "verb" => $verb,
            "author"=>$author,
            "object"=>$object,
 			"target"=>$target
        );

 		//Error 
 		if ($verb == ActStr::VERB_RETURN) {
 			if (@$object["event"] == MailError::EVENT_BOUNCED_EMAIL) {
 				$actionMsg = "Fatal error sending an email to ".$target["email"].". User should be deleted.";	
 			} else if (@$object["event"] == MailError::EVENT_DROPPED_EMAIL || @$object["event"] == MailError::EVENT_SPAM_COMPLAINTS) {
 				$actionMsg = "Error sending an email to ".$target["email"].". User is flagged and will not receive a mail anymore.";	
 			} else {
 				error_log("Unknown event in Mail Error : no notification generated.");
 				return(array("result" => false, "msg" => "Unknown event in Mail Error : no notification generated."));
 			}
 		} else if ($verb == ActStr::VERB_DELETE) {
 			if (@$object["event"] == Element::ERROR_DELETING) {
 				$actionMsg = "Error Deleting the element ".$target["name"].". Check the error log.";
 			}
 			$url = '#'.Element::getControlerByCollection($target['type']).".detail.id.".$target['id'];
 			error_log("URL notif => ".$url);
 		}
 		
	    $stream = ActStr::buildEntry($asParam);

		$notif = array( 
	    	"persons" => array_keys($superAdmins),
            "label"   => $actionMsg , 
            "icon"    => "fa-cog" ,
            "url"     => Yii::app()->createUrl('/'.Yii::app()->controller->module->id.'/admin/mailerrordashboard')
        );

	    $stream["notify"] = ActivityStream::addNotification( $notif );
    	ActivityStream::addEntry($stream);
	}
}
