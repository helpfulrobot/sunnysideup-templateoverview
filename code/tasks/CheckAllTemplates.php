<?php

/**
 * @description (see $this->description)
 *
 * @authors: Andrew Pett [at] sunny side up .co.nz, Nicolaas [at] Sunny Side Up .co.nz
 * @package: templateoverview
 * @sub-package: tasks
 **/

class CheckAllTemplates extends BuildTask {

	protected $title = 'Check URLs for HTTP errors';

	protected $description = "Will go through main URLs (all page types (e.g Page, MyPageTemplate), all page types in CMS (e.g. edit Page, edit HomePage, new MyPage) and all models being edited in ModelAdmin, checking for HTTP response errors (e.g. 404). Click start to run.";

	/**
	  * List of URLs to be checked. Excludes front end pages (Cart pages etc).
	  */
	private $modelAdmins = array();

	/**
	 * @var Array
	 * all of the public acessible links
	 */
	private $allOpenLinks = array();

	/**
	 * @var Array
	 * all of the admin acessible links
	 */
	private $allAdmins = array();

	/**
	 * @var Array
	 * all of the admin acessible links
	 */
	private $customLinks = array();

	/**
	 * @var Array
	 * Pages to check by class name. For example, for "ClassPage", will check the first instance of the cart page.
	 */
	private $classNames = array();

	/**
	 *
	 * @var curlHolder
	 */
	private $ch = null;

	/**
	 * temporary Admin used to log in.
	 * @var Member
	 */
	private $member = null;

	/**
	 * temporary username for temporary admin
	 * @var String
	 */
	private $username = "";

	/**
	 * temporary password for temporary admin
	 * @var String
	 */
	private $password = "";

	/**
	 * @var Boolean
	 */
	private $w3validation = true;

	/**
	 * @var Boolean
	 */
	private $debug = false;

	/**
	 * Main function
	 * has two streams:
	 * 1. check on url specified in GET variable.
	 * 2. create a list of urls to check
	 *
	 */
	public function run($request) {
		ini_set('max_execution_time', 3000);
		if(isset($_GET["debugme"])) {
			$this->debug = true;
		}
		$asAdmin = empty($_REQUEST["admin"]) ? false : true;$this->debugme(__LINE__);
		$testOne = isset($_REQUEST["test"]) ? $_GET["test"] : null;$this->debugme(__LINE__);

		//1. actually test a URL and return the data
		if($testOne) {
			$this->setupCurl();$this->debugme(__LINE__);
			if($asAdmin) {
				$this->createAndLoginUser();$this->debugme(__LINE__);
			}
			echo $this->testURL($testOne, $this->w3validation);$this->debugme(__LINE__);
			$this->cleanup();$this->debugme(__LINE__);

		}

		//2. create a list of
		else {
			Requirements::javascript(THIRDPARTY_DIR . '//ajax.googleapis.com/ajax/libs/jquery/1.8/jquery.min.js');$this->debugme(__LINE__);
			$this->classNames = $this->listOfAllClasses();$this->debugme(__LINE__);
			$this->modelAdmins = $this->ListOfAllModelAdmins();$this->debugme(__LINE__);
			$this->allNonAdmins = $this->prepareClasses();$this->debugme(__LINE__);
			$otherLinks = $this->listOfAllControllerMethods();$this->debugme(__LINE__);
			$this->allAdmins = $this->array_push_array($this->modelAdmins, $this->prepareClasses(1));$this->debugme(__LINE__);
			$this->allAdmins = $this->array_push_array($this->allAdmins, $this->customLinks);$this->debugme(__LINE__);
			$sections = array("allNonAdmins", "allAdmins");$this->debugme(__LINE__);
			$count = 0;$this->debugme(__LINE__);
			echo "<h1><a href=\"#\" class=\"start\">start</a> | <a href=\"#\" class=\"stop\">stop</a></h1>
			<table border='1'>
			<tr><th>Link</th><th>HTTP response</th><th>response TIME</th><th class'error'>error</th><th class'error'>W3 Check</th></tr>";
			foreach($sections as $isAdmin => $sectionVariable) {
				foreach($this->$sectionVariable as $link) {
					$count++;
					$id = "ID".$count;
					$linkArray[] = array("IsAdmin" => $isAdmin, "Link" => $link, "ID" => $id);
					echo "
						<tr id=\"$id\" class=".($isAdmin ? "isAdmin" : "notAdmin").">
							<td><a href=\"".Director::baseURL()."dev/tasks/CheckAllTemplates/?test=".urlencode($link)."&admin=".$isAdmin."\" style='color: purple' target='_blank'>$link</a></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
					";
				}
			}
			echo "
			</table>
			<script src='http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js' ></script>
			<script type='text/javascript'>

				jQuery(document).ready(
					function(){
						checker.init();
					}
				);

				var checker = {
					list: ".Convert::raw2json($linkArray).",

					baseURL: '/dev/tasks/CheckAllTemplates/',

					item: null,

					stop: true,

					init: function() {
						jQuery('a.start').click(
							function() {
								checker.stop = false;
								if(!checker.item) {
									checker.item = checker.list.shift();
								}
								checker.checkURL();
							}
						);
						jQuery('a.stop').click(
							function() {
								checker.stop = true;
							}
						);
						this.initShowMoreClick();
					},

					initShowMoreClick: function(){
						jQuery(\"table\").on(
							\"click\",
							\"a.showMoreClick\",
							function(event){
								event.preventDefault();
								jQuery(this).parent().find(\"ul\").slideToggle();
							}
						)
					},

					checkURL: function(){
						if(checker.stop) {

						}
						else {
							var testLink = (checker.item.Link);
							var isAdmin = checker.item.IsAdmin;
							var ID = checker.item.ID;
							jQuery('#'+ID).find('td')
								.css('border', '1px solid blue');
							jQuery('#'+ID).css('background-image', 'url(/cms/images/loading.gif)')
								.css('background-repeat', 'no-repeat')
								.css('background-position', 'top right');
							jQuery.ajax({
								url: checker.baseURL,
								type: 'get',
								data: {'test': testLink, 'admin': isAdmin},
								success: function(data, textStatus){
									checker.item = null;
									jQuery('#'+ID).html(data).css('background-image', 'none');
									jQuery('#'+ID).find('h1').remove();
									checker.item = checker.list.shift();
									jQuery('#'+ID).find('td').css('border', '1px solid green');

									window.setTimeout(
										function() {checker.checkURL();},
										1000
									);
								},
								error: function(){
									checker.item = null;
									jQuery('#'+ID).find('td.error').html('ERROR');
									jQuery('#'+ID).css('background-image', 'none');
									checker.item = checker.list.shift();
									jQuery('#'+ID).find('td').css('border', '1px solid red');
									window.setTimeout(
										function() {checker.checkURL();},
										1000
									);
								},
								dataType: 'html'
							});
						}
					}
				}
			</script>";
			echo "<h2>Want to add more tests?</h2>
			<p>
				By adding a public method <i>templateoverviewtests</i> to any controller,
				returning an array of links, they will be included in the list above.
			</p>
			";
			echo "<h3>Suggestions</h3>
			<p>Below is a list of suggested controller links.</p>
			<ul>";
			$className = "";
			foreach($otherLinks as $linkArray) {
				if($linkArray["ClassName"] != $className) {
					$className = $linkArray["ClassName"];
					echo "</ul><h2>".$className."</h2><ul>";
				}
				echo "<li><a href=\"".$linkArray["Link"]."\">".$linkArray["Link"]."</a> ".$linkArray["Error"]."</li>";
			}
			echo "</ul>";
		}
	}

	/**
	 * creates the basic curl
	 *
	 */
	private function setupCurl($type = "GET"){
		$user_agent='Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';
		$post = $type == "GET" ? false : true;
		$options = array(
			CURLOPT_CUSTOMREQUEST  =>$type,        //set request type post or get
			CURLOPT_POST           =>$post,        //set to GET
			CURLOPT_USERAGENT      => $user_agent, //set user agent
			CURLOPT_COOKIEFILE     =>"cookie.txt", //set cookie file
			CURLOPT_COOKIEJAR      =>"cookie.txt", //set cookie jar
			CURLOPT_RETURNTRANSFER => true,     // return web page
			CURLOPT_HEADER         => false,    // don't return headers
			CURLOPT_FOLLOWLOCATION => true,     // follow redirects
			CURLOPT_ENCODING       => "",       // handle all encodings
			CURLOPT_AUTOREFERER    => true,     // set referer on redirect
			CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
			CURLOPT_TIMEOUT        => 120,      // timeout on response
			CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
		);

		$this->ch = curl_init();
		curl_setopt_array( $this->ch, $options );
	}



	/**
	 * creates and logs in a temporary user.
	 *
	 */
	private function createAndLoginUser(){
		$this->username = "TEMPLATEOVERVIEW_URLCHECKER___";
		$this->password = rand(1000000000,9999999999);
		//Make temporary admin member
		$adminMember = Member::get()->filter(array("Email" => $this->username))->limit(1)->first();
		if($adminMember != NULL) {
			$adminMember->delete();
		}
		$this->member = new Member();
		$this->member->Email = $this->username;
		$this->member->Password = $this->password;
		$this->member->write();
		$adminGroup = Group::get()->filter(array("code" => "administrators"))->limit(1)->first();
		if(!$adminGroup) {
			user_error("No admin group exists");
		}
		$this->member->Groups()->add($adminGroup);

		curl_setopt($this->ch, CURLOPT_USERPWD, $this->username.":".$this->password);

		$loginUrl = Director::absoluteURL('/Security/LoginForm');
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($this->ch, CURLOPT_URL, $loginUrl);
		curl_setopt($this->ch, CURLOPT_POST, 1);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, 'Email='.$this->username.'&Password='.$this->password);


		//execute the request (the login)
		$loginContent = curl_exec($this->ch);
		$httpCode = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
		$err               = curl_errno( $this->ch );
		$errmsg            = curl_error( $this->ch );
		$header            = curl_getinfo( $this->ch );
		if($httpCode != 200) {
			echo "<span style='color:red'>There was an error logging in!</span><br />";
		}
	}

	/**
	 * removes the temporary user
	 * and cleans up the curl connection.
	 *
	 */
	private function cleanup(){
		if($this->member) {
			$this->member->delete();
		}
		curl_close($this->ch);
	}

	/**
	  * Takes an array, takes one item out, and returns new array
	  * @param Array $array Array which will have an item taken out of it.
	  * @param - $exclusion Item to be taken out of $array
	  * @return Array New array.
	  */
	private function arrayExcept($array, $exclusion) {
		$newArray = $array;
		for($i = 0; $i < count($newArray); $i++) {
			if($newArray[$i] == $exclusion) unset($newArray[$i]);
		}
		return $newArray;
	}

	/**
	 * ECHOES the result of testing the URL....
	 * @param String $url
	 */
	private function testURL($url, $validate = true) {
		if(strlen(trim($url)) < 1) {
			user_error("empty url"); //Checks for empty strings.
		}
		if(strpos($url, "/admin") === 0 || strpos($url, "admin") === 0 ) {
			$validate = false;
		}

		$url = Director::absoluteURL($url);

		//start basic CURL
		curl_setopt($this->ch, CURLOPT_URL, $url);
		$response          = curl_exec($this->ch);

		$httpCode = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
		if($httpCode == "401") {
			$this->createAndLoginUser();
			return $this->testURL($url, false);
		}
		//get more curl!

		$err               = curl_errno( $this->ch );
		$errmsg            = curl_error( $this->ch );
		$header            = curl_getinfo( $this->ch );
		$length            = curl_getinfo($this->ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
		$timeTaken         = curl_getinfo($this->ch, CURLINFO_TOTAL_TIME);
		$timeTaken = number_format((float)$timeTaken, 2, '.', '');
		$possibleError = false;
		$error = "none";
		if(substr($response, 0, 12) == "Fatal error") {
			$error = "<span style='color: red;'>$response</span> ";
			$possibleError = true;
		}
		if(strlen($response) < 2000) {
			$error = "<span style='color: red;'>$response</span> ";
			$possibleError = true;
		}

		$html = "";
		if($httpCode == 200 && !$possibleError) {
			$html .= "<td style='color:green'><a href='$url' style='color: grey!important; text-decoration: none;' target='_blank'>$url</a></td>";
		}
		else {
			if(!$possibleError) {
				$error = "unexpected response";
			}
			$html .= "<td style='color:red'><a href='$url' style='color: red!important; text-decoration: none;'>$url</a></td>";
		}
		$html .= "<td style='text-align: right'>$httpCode</td><td style='text-align: right'>$timeTaken</td><td>$error</td>";

		if($validate && $httpCode == 200 ) {
			$w3Obj = new CheckAllTemplates_W3cValidateApi();
			$html .= "<td>".$w3Obj->W3Validate("", $response)."</td>";
		}
		else {
			$html .= "<td>n/a</td>";
		}
		return $html;
	}

	/**
	  * Pushes an array of items to an array
	  * @param Array $array Array to push items to (will overwrite)
	  * @param Array $pushArray Array of items to push to $array.
	  */
	private function array_push_array($array, $pushArray) {
		foreach($pushArray as $pushItem) {
			array_push($array, $pushItem);
		}
		return $array;
	}

	/**
	 * returns a lis of all SiteTree Classes
	 * @return Array(String)
	 */
	private function listOfAllClasses(){
		$pages = array();$this->debugme(__LINE__);
		$list = null;$this->debugme(__LINE__);
		if(class_exists("TemplateOverviewPage")) {
			$templateOverviewPage = TemplateOverviewPage::get()->First();$this->debugme(__LINE__);
			if(!$templateOverviewPage) {
				$templateOverviewPage = singleton("TemplateOverviewPage");$this->debugme(__LINE__);
			}
			$list = $templateOverviewPage->ListOfAllClasses();$this->debugme(__LINE__);
			foreach($list as $page) {
				$pages[] = $page->ClassName;$this->debugme(__LINE__);
			}
		}
		if(!count($pages)) {
			$list = ClassInfo::subclassesFor("SiteTree");$this->debugme(__LINE__);
			foreach($list as $page) {
				$pages[] = $page;$this->debugme(__LINE__);
			}
		}
		return $pages;$this->debugme(__LINE__);
	}

	/**
	 * returns a list of all model admin links
	 * @return Array(String)
	 */
	private function ListOfAllModelAdmins(){
		$models = array();$this->debugme(__LINE__);
		$modelAdmins = CMSMenu::get_cms_classes("ModelAdmin");$this->debugme(__LINE__);
		if($modelAdmins && count($modelAdmins)) {
			foreach($modelAdmins as $modelAdmin) {
				if($modelAdmin != "ModelAdminEcommerceBaseClass") {
					$obj = singleton($modelAdmin);$this->debugme(__LINE__);
					$modelAdminLink = $obj->Link();$this->debugme(__LINE__);
					$modelAdminLinkArray = explode("?", $modelAdminLink);$this->debugme(__LINE__);
					$modelAdminLink = $modelAdminLinkArray[0];$this->debugme(__LINE__);
					//$extraVariablesLink = $modelAdminLinkArray[1];$this->debugme(__LINE__);
					$models[] = $modelAdminLink;$this->debugme(__LINE__);
					$modelsToAdd = $obj->getManagedModels();$this->debugme(__LINE__);
					if($modelsToAdd && count($modelsToAdd)) {
						foreach($modelsToAdd as $key => $model) {
							if(is_array($model) || !is_subclass_of($model, "DataObject")) {
								$model = $key;$this->debugme(__LINE__);
							}
							if(!is_subclass_of($model, "DataObject")) {
								continue;$this->debugme(__LINE__);
							}
							$modelAdminLink;$this->debugme(__LINE__);
							$modelLink = $modelAdminLink.$model."/";$this->debugme(__LINE__);
							$models[] = $modelLink;$this->debugme(__LINE__);
							$models[] = $modelLink."EditForm/field/".$model."/item/new/";$this->debugme(__LINE__);
							if($item = $model::get()->limit(1)->First()) {
								$models[] = $modelLink."EditForm/field/".$model."/item/".$item->ID."/edit";$this->debugme(__LINE__);
							}
						}
					}
				}
			}
		}
		$this->debugme(__LINE__);
		return $models;
	}

	protected function  listOfAllControllerMethods(){
		$array = array();$this->debugme(__LINE__);
		$classes = ClassInfo::subclassesFor("Controller");$this->debugme(__LINE__);
		//foreach($manifest as $class => $compareFilePath) {
			//if(stripos($compareFilePath, $absFolderPath) === 0) $matchedClasses[] = $class;$this->debugme(__LINE__);
		//}
		$manifest = SS_ClassLoader::instance()->getManifest()->getClasses();$this->debugme(__LINE__);
		$baseFolder = Director::baseFolder();$this->debugme(__LINE__);
		$cmsBaseFolder = Director::baseFolder()."/cms/";$this->debugme(__LINE__);
		$frameworkBaseFolder = Director::baseFolder()."/framework/";$this->debugme(__LINE__);
		foreach($classes as $className) {
			$lowerClassName = strtolower($className);$this->debugme(__LINE__);
			$location = $manifest[$lowerClassName];$this->debugme(__LINE__);
			if(strpos($location, $cmsBaseFolder) === 0 || strpos($location, $frameworkBaseFolder) === 0) {
				continue;$this->debugme(__LINE__);
			}
			if($className != "Controller") {
				$controllerReflectionClass = new ReflectionClass($className);$this->debugme(__LINE__);
				if(!$controllerReflectionClass->isAbstract()) {
					if(
						$className == "Mailto" ||
						$className instanceOF SapphireTest ||
						$className instanceOF BuildTask ||
						$className instanceOF TaskRunner
					) {
						continue;$this->debugme(__LINE__);
					}
					$methods = $this->getPublicMethodsNotInherited($controllerReflectionClass, $className);$this->debugme(__LINE__);
					foreach($methods as $methodArray){
						$array[$className."_".$methodArray["Method"]] = $methodArray;$this->debugme(__LINE__);
					}
				}
			}
		}
		$finalArray = array();$this->debugme(__LINE__);
		$doubleLinks = array();$this->debugme(__LINE__);
		foreach($array as $index  => $classNameMethodArray) {
			if(stripos($classNameMethodArray["ClassName"], "Mailto") == NULL) {
				$classObject = singleton($classNameMethodArray["ClassName"]);$this->debugme(__LINE__);
				if($classNameMethodArray["Method"] == "templateoverviewtests") {
					$this->customLinks = array_merge($classObject->templateoverviewtests(), $this->customLinks);$this->debugme(__LINE__);
				}
				else {
					$classNameMethodArray["Link"] = Director::absoluteURL($classObject->Link($classNameMethodArray["Method"]));$this->debugme(__LINE__);
					if(!isset($doubleLinks[$classNameMethodArray["Link"]])) {
						$finalArray[] = $classNameMethodArray;$this->debugme(__LINE__);
					}
					$doubleLinks[$classNameMethodArray["Link"]] = true;$this->debugme(__LINE__);
				}
			}
		}
		return $finalArray;$this->debugme(__LINE__);
	}

	private function getPublicMethodsNotInherited($classReflection, $className) {
		$classMethods = $classReflection->getMethods();$this->debugme(__LINE__);
		$classMethodNames = array();$this->debugme(__LINE__);
		foreach ($classMethods as $index => $method) {
			if ($method->getDeclaringClass()->getName() !== $className) {
			 unset($classMethods[$index]);$this->debugme(__LINE__);
			}
			else {
				$allowedActionsArray = Config::inst()->get($className, "allowed_actions", Config::FIRST_SET);$this->debugme(__LINE__);
				if(!is_array($allowedActionsArray)) {
					$allowedActionsArray = array();$this->debugme(__LINE__);
				}
				$methodName = $method->getName();$this->debugme(__LINE__);
				/* Get a reflection object for the class method */
				$reflect = new ReflectionMethod($className, $methodName);$this->debugme(__LINE__);
				/* For private, use isPrivate().  For protected, use isProtected() */
				/* See the Reflection API documentation for more definitions */
				if($reflect->isPublic()) {
					if($methodName == strtolower($methodName)) {
						if(strpos($methodName, "_") == NULL) {
							if(!in_array($methodName, array("index", "run", "init"))) {
								/* The method is one we're looking for, push it onto the return array */
								$error = "";$this->debugme(__LINE__);
								if(!in_array($methodName, $allowedActionsArray) && !isset($allowedActionsArray[$methodName])) {
									$error = "Can not find ".$className."::".$methodName." in allowed_actions";$this->debugme(__LINE__);
								}
								$classMethodNames[$methodName] = array(
									"ClassName" => $className,
									"Method" => $methodName,
									"Error" => $error
								);$this->debugme(__LINE__);
							}
						}
					}
				}
			}
		}
		return $classMethodNames;$this->debugme(__LINE__);
	}

	/**
	 * Takes {@link #$classNames}, gets the URL of the first instance of it (will exclude extensions of the class) and
	 * appends to the {@link #$urls} list to be checked
	 * @return Array(String)
	 */
	private function prepareClasses($publicOrAdmin = 0) {
		//first() will return null or the object
		$return = array();$this->debugme(__LINE__);
		foreach($this->classNames as $class) {
			$page = $class::get()->exclude(array("ClassName" => $this->arrayExcept($this->classNames, $class)))->first();$this->debugme(__LINE__);
			if($page) {
				if($publicOrAdmin) {
					$url = "/admin/pages/edit/show/".$page->ID;$this->debugme(__LINE__);
				}
				else {
					$url = $page->link();$this->debugme(__LINE__);
				}
				$return[] = $url;$this->debugme(__LINE__);
			}
		}
		return $return;$this->debugme(__LINE__);
	}


	private function debugme($lineNumber, $variable ="") {
		if($this->debug) {echo "<br />".$lineNumber .": ".round(memory_get_usage() / 1048576)."MB"."=====".$variable;  flush();ob_flush(); }
	}

}


/*
   Author:	Jamie Telin (jamie.telin@gmail.com), currently at employed Zebramedia.se

   Scriptname: W3C Validation Api v1.0 (W3C Markup Validation Service)

*/

class CheckAllTemplates_W3cValidateApi{

	private $baseURL = 'http://validator.w3.org/check';
	private $output = 'soap12';
	private $uri = '';
	private $fragment = '';
	private $postVars = array();
	private $validResult = false;
	private $errorCount = 0;
	private $errorList = array();
	private $showErrors = true;


	private function W3cValidateApi(){
		//Nothing...
	}

	private function makePostVars(){
		$this->postVars['output'] = $this->output;
		if($this->fragment) {
			$this->postVars['fragment'] = $this->fragment;
		}
		elseif($this->uri) {
			$this->postVars['uri'] = $this->uri;
		}
	}

	private function setUri($uri){
		$this->uri = $uri;
	}

	private function setFragment($fragment){
		$fragment = preg_replace('/\s+/', ' ', $fragment);
		$this->fragment = $fragment;
	}

	private function makeValidationCall(){
		return $out;
	}

	private function validate(){

		sleep(1);

		$this->makePostVars();


		$user_agent= 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)';
		$options = array(
			CURLOPT_CUSTOMREQUEST  =>"POST",        //set request type post or get
			CURLOPT_POST           =>1,            //set to GET
			CURLOPT_USERAGENT      => $user_agent, //"test from www.sunnysideup.co.nz",//$user_agent, //set user agent
			CURLOPT_COOKIEFILE     =>"cookie.txt", //set cookie file
			CURLOPT_COOKIEJAR      =>"cookie.txt", //set cookie jar
			CURLOPT_RETURNTRANSFER => true,     // return web page
			CURLOPT_HEADER         => false,    // don't return headers
			CURLOPT_FOLLOWLOCATION => true,     // follow redirects
			CURLOPT_ENCODING       => "",       // handle all encodings
			CURLOPT_AUTOREFERER    => true,     // set referer on redirect
			CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
			CURLOPT_TIMEOUT        => 120,      // timeout on response
			CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
			CURLOPT_POSTFIELDS     => $this->postVars,
			CURLOPT_URL            => $this->baseURL
		);
		// Initialize the curl session
		$ch = curl_init();
		curl_setopt_array( $ch, $options );
		// Execute the session and capture the response
		$out = curl_exec($ch);



		//$err               = curl_errno( $ch );
		//$errmsg            = curl_error( $ch );
		//$header            = curl_getinfo( $ch );
		$httpCode          = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if($httpCode == 200) {
			$doc = simplexml_load_string($out);
			$doc->registerXPathNamespace('m', 'http://www.w3.org/2005/10/markup-validator');

			//valid ??
			$nodes = $doc->xpath('//m:markupvalidationresponse/m:validity');
			$this->validResult = strval($nodes[0]) == "true" ? true : false;

			//error count ??
			$nodes = $doc->xpath('//m:markupvalidationresponse/m:errors/m:errorcount');
			$this->errorCount = strval($nodes[0]);
			//errors
			$nodes = $doc->xpath('//m:markupvalidationresponse/m:errors/m:errorlist/m:error');
			foreach ($nodes as $node) {
				//line
				$nodes = $node->xpath('m:line');
				$line = strval($nodes[0]);
				//col
				$nodes = $node->xpath('m:col');
				$col = strval($nodes[0]);
				//message
				$nodes = $node->xpath('m:message');
				$message = strval($nodes[0]);
				$this->errorList[] = $message."($line,$col)";
			}
		}
		return $httpCode;
	}

	function get_headers_from_curl_response($response){

		return $header;
	}


	public function W3Validate($uri = "", $fragment = ""){
		if($uri){
			$this->setUri($uri);
		}
		elseif($fragment){
			$this->setFragment($fragment);
		}
		$this->validate();
		if($this->validResult){
			$type = 'PASS';
			$color1 = '#00CC00';
		}
		else {
			$type = 'FAIL';
			$color1 = '#FF3300';
		}
		$errorDescription = "";
		if($this->errorCount) {
			$errorDescription = " - ".$this->errorCount."errors: ";
			if($this->showErrors) {
				if(count($this->errorList)) {
					$errorDescription .= "<ul style=\"display: none;\"><li>".implode("</li><li>", $this->errorList)."</li></ul>";
				}
			}
			else {
				$errorDescription .= '<a href="'.$this->baseURL.'?uri='.urlencode($uri).'">check</a>';
			}
		}

		return '<div style="background:'.$color1.';"><a href="#" class="showMoreClick">'.$type.'</a></strong>'.$errorDescription.'</div>';
	}


}
