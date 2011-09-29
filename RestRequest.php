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

class RestRequest
{
	protected $url;
	protected $verb;
	protected $requestBody;
	protected $multiparts;
	protected $requestLength;
	protected $username;
	protected $password;
	protected $authenticationMethod;
	protected $acceptType;
	protected $responseBody;
	protected $responseInfo;
	
	private $boundary_mark = "part-marker";
	private $start_mark = "json-object";

	public function __construct ($url = null, $verb = 'GET', $requestBody = null, 
								 $multiparts = null, $username=null, $password=null)
	{
		if (!function_exists('curl_init')) 
		{ 
			die('CURL is not installed!');
		}
		
		
		$this->url				= $url;
		$this->verb				= $verb;
		$this->requestBody		= $requestBody;
		$this->multiparts = $multiparts;
		$this->requestLength	= 0;
		$this->username			= $username;
		$this->password			= $password;
		$this->acceptType		= 'application/json';
		$this->responseBody		= null;
		$this->responseInfo		= null;
		$this->authenticationMethod = CURLAUTH_BASIC;
		
		/*
		if ($this->requestBody !== null)
		{
			$this->buildPostBody();
		}
		*/
	}
	
	public function flush ()
	{
		$this->requestBody		= null;
		$this->requestLength	= 0;
		$this->verb				= 'GET';
		$this->responseBody		= null;
		$this->responseInfo		= null;
	}
	
	public function execute ()
	{
		$ch = curl_init();
		$this->setAuth($ch);
		
		try
		{
			switch (strtoupper($this->verb))
			{
				case 'GET':
					$this->executeGet($ch);
					break;
				case 'POST':
				  if (empty($this->multiparts)) {
					$this->executePost($ch);
				  } else {
					$this->executePostMultipart($ch);
				  }
					break;
				case 'PUT':
				  if (empty($this->multiparts)) {
					$this->executePut($ch);
				  } else {
				    $this->executePutMultipart($ch);
				  }
					break;
				case 'DELETE':
					$this->executeDelete($ch);
					break;
				default:
					throw new InvalidArgumentException('Current verb (' . $this->verb . ') is an invalid REST verb.');
			}
		}
		catch (InvalidArgumentException $e)
		{
			curl_close($ch);
			throw $e;
		}
		catch (Exception $e)
		{
			curl_close($ch);
			throw $e;
		}
		
	}
	
	public function buildPostBody ($data = null)
	{
		$data = ($data !== null) ? $data : $this->requestBody;
		
		if (!is_array($data))
		{
			throw new InvalidArgumentException('Invalid data input for postBody.  Array expected');
		}
		
		$data = http_build_query($data, '', '&');
		$this->requestBody = $data;
	}
	
	protected function executeGet ($ch)
	{		
		$this->doExecute($ch);	
	}
	
	protected function executePost ($ch)
	{
		/*
		if (!is_string($this->requestBody))
		{
			$this->buildPostBody();
		}
		*/
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->requestBody);
		curl_setopt($ch, CURLOPT_POST, 1);
		
		$this->doExecute($ch);	
	}

	protected function executePostMultipart($ch) {
	  $crlf = "\r\n";
	  
	  $multipart_body = "--$this->boundary_mark" . $crlf;
	  $multipart_body .= "Content-Type: application/json" . $crlf;
	  $multipart_body .= "Content-ID: $this->start_mark" . $crlf . $crlf;
	  $multipart_body .= $this->requestBody . $crlf;

	  foreach ($this->multiparts as $id => $resource) {
		$multipart_body .= "--$this->boundary_mark" . $crlf;
		$multipart_body .= "Content-Type: " . $resource["mimeType"] . $crlf;
		$multipart_body .= "Content-ID: $id" . $crlf . $crlf;
		$multipart_body .= file_get_contents($resource["filenamePath"]) . $crlf;		
	  }
	  $multipart_body .= "--" . $this->boundary_mark . "--";
	  
	  curl_setopt($ch, CURLOPT_POST, true);
	  curl_setopt($ch, CURLOPT_POSTFIELDS, $multipart_body);
	
	  $this->doExecute($ch);
	  
	  curl_close($ch);
	}
	
	protected function executePut ($ch)
	{
		if (!is_string($this->requestBody))
		{
			$this->buildPostBody();
		}
		
		$this->requestLength = strlen($this->requestBody);
		
		$fh = fopen('php://memory', 'rw');
		fwrite($fh, $this->requestBody);
		rewind($fh);
		
		curl_setopt($ch, CURLOPT_INFILE, $fh);
		curl_setopt($ch, CURLOPT_INFILESIZE, $this->requestLength);
		curl_setopt($ch, CURLOPT_PUT, true);
		
		$this->doExecute($ch);
		
		fclose($fh);
	}
	
	protected function executePutMultipart($ch) {
	 
	  $crlf = "\r\n";
	  
	  $multipart_body = "--$this->boundary_mark" . $crlf;
	  $multipart_body .= "Content-Type: application/json" . $crlf;
	  $multipart_body .= "Content-ID: $this->start_mark" . $crlf . $crlf;
	  $multipart_body .= $this->requestBody . $crlf;

	  foreach ($this->multiparts as $id => $resource) {
		$multipart_body .= "--$this->boundary_mark" . $crlf;
		$multipart_body .= "Content-Type: " . $resource["mimeType"] . $crlf;
		$multipart_body .= "Content-ID: $id" . $crlf . $crlf;
		$multipart_body .= file_get_contents($resource["filenamePath"]) . $crlf;		
	  }
	  $multipart_body .= "--" . $this->boundary_mark . "--";
	  print_r($multipart_body);	  
	  $fh = fopen('php://memory', 'rw');
	  fwrite($fh, $multipart_body);
	  rewind($fh);
		
	  curl_setopt($ch, CURLOPT_PUT, true);
	  curl_setopt($ch, CURLOPT_INFILE, $fh);
	  curl_setopt($ch, CURLOPT_INFILESIZE, strlen($multipart_body));
	
	  $this->doExecute($ch);
	  
	  fclose($fh);
	}

	protected function executeDelete ($ch)
	{
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
		
		$this->doExecute($ch);
	}
	
	protected function doExecute (&$curlHandle)
	{
		$this->setCurlOpts($curlHandle);
		$this->responseBody = curl_exec($curlHandle);
		$this->responseInfo	= curl_getinfo($curlHandle);
		
		curl_close($curlHandle);
	}
	
	protected function setCurlOpts (&$curlHandle)
	{
		curl_setopt($curlHandle, CURLOPT_TIMEOUT, 10);
		curl_setopt($curlHandle, CURLOPT_URL, $this->url);
		curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
		if ($this->verb == 'GET' || $this->verb == 'DELETE') {
			curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array ('Accept: ' . $this->acceptType));
		}
		else { // when we POST or PUT we specify in the header that the payload content-type is json
		  if (empty($this->multiparts)) {
			curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array ('Accept: ' . $this->acceptType, 'Content-type: application/json'));
		  } else {
		    // set multipart header
		    $header = array("Content-Type: multipart/related; boundary=$this->boundary_mark; start=$this->start_mark");
		    curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $header);
		  }
		}
	}
	
	protected function setAuth (&$curlHandle)
	{
		if ($this->username !== null && $this->password !== null)
		{
			curl_setopt($curlHandle, CURLOPT_HTTPAUTH, $this->authenticationMethod);
			curl_setopt($curlHandle, CURLOPT_USERPWD, $this->username . ':' . $this->password);
		}
	}
	
	public function getAcceptType ()
	{
		return $this->acceptType;
	} 
	
	public function setAcceptType ($acceptType)
	{
		$this->acceptType = $acceptType;
	} 
	
	public function getPassword ()
	{
		return $this->password;
	} 
	
	public function setPassword ($password)
	{
		$this->password = $password;
	} 
	
	public function getResponseBody ()
	{
		return $this->responseBody;
	}
	
	public function getResponseBodyAsArray ()
	{
		return json_decode($this->responseBody, true);
	}
	
	public function getResponseBodyAsObject ()
	{
		return json_decode($this->responseBody, false);
	}
	
	public function getResponseInfo ()
	{
		return $this->responseInfo;
	} 
	
	public function getUrl ()
	{
		return $this->url;
	} 
	
	public function setUrl ($url)
	{
		$this->url = $url;
	} 
	
	public function getUsername ()
	{
		return $this->username;
	} 
	
	public function setUsername ($username)
	{
		$this->username = $username;
	} 
	
	public function getVerb ()
	{
		return $this->verb;
	} 
	
	public function setVerb ($verb)
	{
		$this->verb = $verb;
	}
	
	/**
	 * 
	 * find if the request returned with success
	 * @return boolean
	 */
	public function ok() {
		if (!empty($this->responseInfo)) {
			if (
				(($this->verb == 'GET' || $this->verb == 'PUT' || $this->verb == 'DELETE') && $this->responseInfo['http_code'] == '200') 
				or 
				($this->verb == 'POST' && $this->responseInfo['http_code'] == '201')
			) {
				return true;
			}
			
			return false;
		}
	}
	
	/**
	 * Return true if the requested resource was not found 
	 * @return boolean
	 */
	public function notFound() {
		if (!empty($this->responseInfo) && $this->responseInfo['http_code'] == '404') {
			return true;
		}
		
		return false;
	}
}
