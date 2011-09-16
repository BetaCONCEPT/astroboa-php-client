<?php 
/**
 * Copyright (C) 2005-2011 BetaCONCEPT LP.
 *
 * This file is part of Astroboa.
 *
 * Astroboa is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Astroboa is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Astroboa.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * * @author Gregory Chomatas (gchomatas@betaconcept.com)
 * 
 */

require 'RestRequest.php';

class AstroboaClient {
	
	private $repositoryIPAddressOrFQDN;
	private $repositoryName;
	private $resourceApiCommonPath;
	private $objectApiPath;
	private $taxonomyApiPath;
	private $topicApiPath;
	private $username;
	private $password;
	
	public function __construct($repositoryIPAddressOrFQDN="localhost", $repositoryName="astroboa", $username=null, $password=null) {
		$this->repositoryIPAddressOrFQDN = $repositoryIPAddressOrFQDN;
		$this->repositoryName = $repositoryName;
		
		$this->resourceApiCommonPath = "http://" . $repositoryIPAddressOrFQDN . "/resource-api/" . $repositoryName . "/";
		$this->objectApiPath = $this->resourceApiCommonPath . "contentObject";
		$this->taxonomyApiPath = $this->resourceApiCommonPath . "taxonomy";
		$this->topicApiPath = $this->resourceApiCommonPath . "topic";
		
		$this->username = $username;
		$this->password = $password;
		  
	}
	
	
	public function getObjectByIdOrName($idOrName) {
		$request = new RestRequest($this->objectApiPath . '/' . urlencode($idOrName), 'GET', null, null, $this->username, $this->password);
		$request->execute();
		
		return $request;
	}

	
	public function getObjectCollectionByObjectType($objectType, $offset=0, $limit=50, $orderBy) {
		
		$collectionQueryUrl = $this->objectApiPath . '?' . 'cmsQuery=contentTypeName=%22' . $objectType . '%22' . '&offset=' . $offset . '&limit=' . $limit;
		if ($orderBy != null && $orderBy != "") {
			$orderBy = urlencode($orderBy);
			$collectionQueryUrl = $collectionQueryUrl . '&orderBy=' . $orderBy;
		}
		error_log("Executing REST Call: " . $collectionQueryUrl, 0);
		$request = new RestRequest($collectionQueryUrl, 'GET', null, null, $this->username, $this->password);
		$request->execute();
		return $request;
	}
	
	
	public function getObjectCollection($query, $projectionPaths, $offset=0, $limit=50, $orderBy) {
		$query = urlencode($query);
		$collectionQueryUrl = $this->objectApiPath . '?' . 'cmsQuery=' . $query . '&offset=' . $offset . '&limit=' . $limit;
		if ($orderBy != null && $orderBy != "") {
			$orderBy = urlencode($orderBy);
			$collectionQueryUrl = $collectionQueryUrl . '&orderBy=' . $orderBy;
		}
		
		if ($projectionPaths != null && $projectionPaths != "") {
			$projectionPaths = urlencode($projectionPaths);
			$collectionQueryUrl = $collectionQueryUrl . '&projectionPaths=' . $projectionPaths;
		}
		
		error_log("Executing GET Call: " . $collectionQueryUrl, 0);
		$request = new RestRequest($collectionQueryUrl, 'GET', null, null, $this->username, $this->password);
		$request->execute();
		return $request;
	}
	
	
	public function getTaxonomyByIdOrName($idOrName) {
		$request = new RestRequest($this->taxonomyApiPath . '/' . urlencode($idOrName), 'GET', null, null, $this->username, $this->password);
		$request->execute();
		return $request;
	}
	
	//depth controls the depth of the topic hierarchy tree
	//Accepted values are
	// 0 : Topic properties and localized labels are returned only
	// 1 : Topic and its children are returned (default value)
	// -1 : Topic and all its descendants are returned
	public function getTopicByIdOrName($idOrName, $depth = 1) {
		$request = new RestRequest($this->topicApiPath . '/' . urlencode($idOrName) . '?depth=' . $depth, 'GET', null, null, $this->username, $this->password);
		$request->execute();
		return $request;
	}
	
	
	public function getTopicCollection($query, $projectionPaths, $offset=0, $limit=50, $orderBy) {
		$query = urlencode($query);
		$collectionQueryUrl = $this->objectApiPath . '?' . 'cmsQuery=' . $query . '&offset=' . $offset . '&limit=' . $limit;
		if ($orderBy != null && $orderBy != "") {
			$orderBy = urlencode($orderBy);
			$collectionQueryUrl = $collectionQueryUrl . '&orderBy=' . $orderBy;
		}
		
		if ($projectionPaths != null && $projectionPaths != "") {
			$projectionPaths = urlencode($projectionPaths);
			$collectionQueryUrl = $collectionQueryUrl . '&projectionPaths=' . $projectionPaths;
		}
		
		error_log("Executing GET Call: " . $collectionQueryUrl, 0);
		$request = new RestRequest($collectionQueryUrl, 'GET', null, null, $this->username, $this->password);
		$request->execute();
		return $request;
	}
	
	
	public function addObject($object) {
		$requestBody = json_encode($object);
		$request = new RestRequest($this->objectApiPath, 'POST', $requestBody, null, $this->username, $this->password);
		$request->execute();
		return $request;
	}

	
	private function getFileInfo($filenamePath) {
	  if (! is_readable($filenamePath)) {
	    throw new Exception("File $filenamePath does not exist or is not readable\n");
	  }
	  $info = array();
	  $info["lastModificationTime"] = date("c", filemtime($filenamePath));
	  $info["sourceFileName"] = basename($filenamePath);
	  $finfo = finfo_open(FILEINFO_MIME_TYPE);
	  $mimeType = finfo_file($finfo, $filenamePath);
	  finfo_close($finfo);
	  $info["mimeType"] = $mimeType; 
	  
	  return $info;
	}
	
	
	/**
	   Set a binary property (Binary Channel) to a file
	   
	   &$objectBinaryProperty: a reference to a property of a content object that holds binary data.
	   Examples of valid binary properties: 
	   * image property of a genericContentResourceObject, e.g. &$genericContentResourceObj["image"]
	   * content property of a fileResourceObject, e.g. &$fileResourceObj["content"]
	   * 1st, 2nd, etc, content property of an arrayOfFileResourceTypeObject, e.g. &$arrayOfFileResourceTypeObj["fileResource"][0]["content"],
	   &$arrayOfFileResourceTypeObj["fileResource"][1]["content"], etc

	   &$multiparts: a reference to a map that holds information about multipart data. Each key is a unique id that a identifies a tuple 
	   that consists of the full path name to a file and its mime-type.

	   $filenamePath: a full path name to a file.
	 */
	public function setBinaryProperty(&$objectBinaryProperty, &$multiparts, $filenamePath) {
	  $fileinfo = $this->getFileInfo($filenamePath);
	    	  
	  $generateNewId = empty($objectBinaryProperty) || 
	    empty($objectBinaryProperty["url"]) || 
	    (! array_key_exists($objectBinaryProperty["url"], $multiparts));

	  if (empty($objectBinaryProperty)) {
	    $objectBinaryProperty = array();
	  }
	  
	  $uniqueId = "";

	  if ($generateNewId){
	    $uniqueId = uniqid("");
	    $objectBinaryProperty["url"] = $uniqueId;
	  } else {
	    $uniqueId = $objectBinaryProperty["url"];
	  }

	  $objectBinaryProperty["lastModificationDate"] = $fileinfo["lastModificationTime"];
	  $objectBinaryProperty["sourceFileName"] = $fileinfo["sourceFileName"];
	  $objectBinaryProperty["mimeType"] = $fileinfo["mimeType"];
	  
	  $multiparts[$uniqueId] = array("filenamePath" => $filenamePath, "mimeType" => $objectBinaryProperty["mimeType"]);
	}
	
	
	/**
	   Add/Create a new object with binary data attached. 
	   $object: is map that represents the object to add.
	   $multiparts: a map that contains binary data information, see also setBinaryProperty() function
	 */
	public function addObjectWithBinaryData($object, $multiparts) {
	  $requestBody = json_encode($object);
	  $request = new RestRequest($this->objectApiPath,
				     'POST',
				     $requestBody,
				     $multiparts,
				     $this->username,
				     $this->password);
	  $request->execute();
	  return $request;
	}

	public function updateObject($object) {
		if (empty($object['cmsIdentifier'])) {
			throw new Exception("You try to update an object that does not have an identifier. If your object is new then use addObject() to create a new object");
		}
		$apiPathForPut = $this->objectApiPath . '/' . $object['cmsIdentifier'];
		error_log("Executing PUT Call: " . $apiPathForPut, 0);
		
		$requestBody = json_encode($object);
		
		// Unfortunately the json_encode function converts null values to strings that contain the word null, i.e. "name" : "null"
		// We should replace the quoted null values with non-quoted null i.e. "name" : "null" should become "name" : null
		// This is required because the quoted nulls are interpreted by astroboa API decoder as normal strings that contain the word "null" and not as null values.
		// But why should we need to put in our JSON object properties with null values? 
		// Since the API supports partial updates, the null values are needed for deleting the value of properties (if a property has no value it is completely removed from the persisted object in 
		// contrast with databases that keep columns with null values).
		// So we cannot remove a property by not including it in the json object. Due to partial update functionality, If a property is not included in the json object 
		// it will not be updated at all. It is ignored. In order to completly delete a property value we should add it 
		// in the json object with a value equal to null (be aware not "null", it should be without quotes).
		str_replace('"null"', 'null', $requestBody);
		
		// TODO:
		// if the property is multivalue and the provided value list is empty it will cause an error from astroboa api.
		// We should send a null in order to remove an empty value list. Sending an empty list as a property value is not allowed.
		// we may check at this point for empty lists in posted json objects and substitute them with null in order to protect the developer if she forgets to check
		// for empty lists and substitute them with nulls
		
		error_log($requestBody);
		
		$request = new RestRequest($apiPathForPut, 'PUT', $requestBody, null, $this->username, $this->password);
		$request->execute();
		return $request;
	}
	/**
	   Update an object with binary data attached. 
	   $object: is map that represents the object to update.
	   $multiparts: a map that contains binary data information, see also setBinaryProperty() function
	 */
	public function updateObjectWithBinaryData($object, $multiparts) {
	  if (empty($object['cmsIdentifier'])) {
	    throw new Exception("You try to update an object that does not have an identifier. If your object is new then use addObject() to create a new object");
	  }
	  $requestBody = json_encode($object);
	  $objectApiPathForPut = $this->objectApiPath . '/' . $object['cmsIdentifier'];

	  $request = new RestRequest($objectApiPathForPut ,
				     'PUT',
				     $requestBody,
				     $multiparts,
				     $this->username,
				     $this->password);
	  $request->execute();
	  return $request;
	}

	public function deleteObjectByIdOrName($idOrName) {
	  if (empty($idOrName)){
		throw new Exception('Could not delete object. Parameter $idOrName cannot be empty');
	  }
	  $request = new RestRequest($this->objectApiPath . '/' . urlencode($idOrName), 'DELETE', null, null, $this->username, $this->password);
	  $request->execute();
	  return $request;
	}
	
	public function addTaxonomy($taxonomy) {
	  $requestBody = json_encode($taxonomy);
	  $request = new RestRequest($this->taxonomyApiPath, 'POST', $requestBody, null, $this->username, $this->password);
	  $request->execute();
	  return $request;
	}

	public function updateTaxonomy($taxonomy) {
	  if (empty($taxonomy['cmsIdentifier'])) {
		throw new Exception('Could not update taxonomy because it misses an identifier. $taxonomy["identifier"] is empty');
	  }
	  $requestBody = json_encode($taxonomy);
	  $request = new RestRequest($this->taxonomyApiPath . '/' . $taxonomy['cmsIdentifier'], 'PUT', $requestBody, null,
								 $this->username, $this->password);
	  $request->execute();
	  return $request;
	}

	public function deleteTaxonomyByIdOrName($idOrName) {
	  if (empty($idOrName)){
		throw new Exception("Could not delete taxonomy. Parameter \$idOrName cannot be empty");
	  }
	  $request = new RestRequest($this->taxonomyApiPath . '/' . urlencode($idOrName), 'DELETE', null, null, $this->username, $this->password);
	  $request->execute();
	  return $request;
	}
	
	public function addTopic($topic) {
	  $requestBody = json_encode($topic);
	  $request = new RestRequest($this->topicApiPath, 'POST', $requestBody, null, $this->username, $this->password);
	  $request->execute();
	  return $request;
	}

	public function updateTopic($topic) {
	  if (empty($topic["cmsIdentifier"])) {
		throw new Exception('Could not update topic because it misses an identifier. $topic[cmsIdentifier] is empty');
	  }
	  $requestBody = json_encode($topic);
	  $request = new RestRequest($this->topicApiPath . '/' . $topic["cmsIdentifier"], 'PUT', $requestBody, null,
								 $this->username, $this->password);
	  $request->execute();
	  return $request;
	}

	public function deleteTopicByIdOrName($idOrName) {
	  if (empty($idOrName)){
		throw new Exception("Could not delete topic. Parameter \$idOrName cannot be empty");
	  }
	  $request = new RestRequest($this->topicApiPath . '/' . urlencode($idOrName), 'DELETE', null, null, $this->username, $this->password);
	  $request->execute();
	  return $request;
	}

	public function printResponse($array, $spaces = "") {
		$retValue = "";

		if(is_array($array)) {
			$spaces = $spaces
			."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";

			$retValue = $retValue."<br/>";

			foreach(array_keys($array) as $key) {
				$retValue = $retValue.$spaces
				."<strong>".$key."</strong>"
				.$this->printResponse($array[$key],
				$spaces);
			}
			$spaces = substr($spaces, 0, -30);
		}
		else $retValue =
		$retValue." - ".$array."<br/>";

		return $retValue;
	}
	
}
?>
