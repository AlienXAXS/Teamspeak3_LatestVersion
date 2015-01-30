<?php

	/*
	 * Created by AlienX
	 * This poopy PHP script will attempt to return the latest server version number by scanning a download repo from teamspeak3.com
	 *
	 * If you need help with this script or if it has an error please feel free to jump into
	 * my teamspeak over at ts3.agngaming.com, i am normally about in the evenings :)
	 */

	error_reporting(E_ALL);
	
	if ( !isset($_GET['bit']) )
		die ( "<strong>Error</strong> Please pass the bit type to the process (index.php?bit=i386/amd64)" );

	$requestedBitVersion = $_GET['bit'];
	if (!( $requestedBitVersion == "amd64" || $requestedBitVersion == "i386" ))
		die ( "<strong>Error</strong> Supported bit versions are: amd64, i386" );
		
	//Base URL for the repo
	$baseURL = "http://dl.4players.de/ts/releases/";
	
	/*
	 * Reads a websites HTML
	 * Returns array containing "status" and "contents"
	 * "status" can either be NULL (no error) or the CURL error.
	 * "contents" is the HTML string
	 */
	function getHTMLContentFromURL($URLInput)
	{
		//setup array.
		$temp["status"] = null;
		$temp["contents"] = null;
	
		$c = curl_init($URLInput);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		$temp["contents"] = curl_exec($c);

		if (curl_error($c))
		{
			$temp["status"] = curl_error($c);
		} else {
			// Get the status code
			$status = curl_getinfo($c, CURLINFO_HTTP_CODE);
			if ( $status != "200" ) //Only update the status from NULL to something else if the status was NOT successful
				$temp["status"] = $status;
		};
		
		curl_close($c);
		
		return $temp;
	}
	
	function getTeamspeakVersionsFromHTML($htmlSource)
	{
		$teamspeakVersionArray = null;
		$dom = new DOMDocument;
		try {
			$dom->loadHTML($htmlSource);
			$tableElements = $dom->getElementsByTagName("tr");
			foreach ( $tableElements as $tableElement )
			{
				foreach ( $tableElement->childNodes as $tableChildElement )
				{
					$tableElementValue = $tableChildElement->nodeValue;

					//If an element ends with a forward slash then it is a directory
					if ( substr($tableElementValue, -1) == "/" )
					{
						$tableElementValue = substr($tableElementValue, 0, strlen($tableElementValue)-1);
						//Does the element match a version number?
						if ( preg_match ( "^((?:\d+\.)?(?:\d+\.)?\d+\.\d+)$^", $tableElementValue ) )
						{
							//Build array.
							$teamspeakVersionArray[] = $tableElementValue;
						}
					}
				}
			}
		} catch (Exception $e) {
			
		};
		
		//Sort the array naturally
		natsort($teamspeakVersionArray);
		return $teamspeakVersionArray;
	}
	
	function doesVersionContainServerBinary($baseURL, $version, $bit)
	{
		$return = null;
		$thisBaseURL = $baseURL . $version . "/";
		
		$htmlContent = getHTMLContentFromURL($thisBaseURL);
		if ( $htmlContent["status"] == null )
		{
			$dom = new DOMDocument;
			$dom->loadHTML($htmlContent["contents"]);
			$tableElements = $dom->getElementsByTagName("tr");
			foreach ( $tableElements as $tableElement )
			{
				foreach ( $tableElement->childNodes as $tableChildElement )
				{
					$tableElementValue = $tableChildElement->nodeValue;
					if ( $bit == "i386" )
					{
						if ( strpos($tableElementValue, "server") &&
							 strpos($tableElementValue, "linux") &&
							 strpos($tableElementValue, "tar") &&
						     strpos($tableElementValue, "x86") )
						{
							$return = $tableElementValue;
						}
					} else {
						if ( strpos($tableElementValue, "server") &&
							 strpos($tableElementValue, "linux") &&
							 strpos($tableElementValue, "tar") &&
							 strpos($tableElementValue, "amd64") )
						{
							$return = $tableElementValue;
						}
					};
				}
			}
		} else {
			echo "Error - " . $htmlContent["status"] . "<br />";
		}
		
		return $return;
	}
	
	//Builds a URL so that WGET can be used against it.
	function buildDownloadURLForWGET($baseURL, $fileDetails)
	{
		$fileName = $fileDetails["file"];
		$fileVersion = $fileDetails["version"];
		return $baseURL . $fileVersion . "/" . $fileName;
	}
	
	$htmlReleases = getHTMLContentFromURL($baseURL);
	if ( $htmlReleases["status"] == null )
	{
		$teamspeakVersions = getTeamspeakVersionsFromHTML($htmlReleases["contents"]);
		
		if ( $teamspeakVersions == null )
			die ( "Unknown error, unable to get versions" );
					
		$amd64Binary = null;
		$i386Binary = null;
				
		//Output the stuff in JSON format.
		switch ( $requestedBitVersion )
		{
			case "amd64":
				foreach ( array_reverse($teamspeakVersions) as $teamspeakVersion )
				{
					$serverBinaryAMD64 = doesVersionContainServerBinary($baseURL, $teamspeakVersion, "amd64");
					if ( ($serverBinaryAMD64 != null) && ($amd64Binary == null) )
					{
						$amd64Binary["file"] = $serverBinaryAMD64;
						$amd64Binary["version"] = $teamspeakVersion;
						break;
					}
				}
				
				if ( $amd64Binary == null )
					die ( json_encode(array("-1", "no version found")) );
					
				echo json_encode(array($amd64Binary["version"], buildDownloadURLForWGET($baseURL, $amd64Binary)));
				break;
				
			case "i386":
				foreach ( array_reverse($teamspeakVersions) as $teamspeakVersion )
				{
					$serverBinaryi386 = doesVersionContainServerBinary($baseURL, $teamspeakVersion, "i386");
					if ( ($serverBinaryi386 != null) && ($i386Binary == null) )
					{
						$i386Binary["file"] = $serverBinaryi386;
						$i386Binary["version"] = $teamspeakVersion;
						break;
					}
				}
				
				if ( $i386Binary == null )
					die ( json_encode(array("-1", "no version found")) );
					
				echo json_encode(array($i386Binary["version"], buildDownloadURLForWGET($baseURL, $i386Binary)));
				break;
		}
		
	} else {
		echo "Error in GetHTMLContentFromURL, CURL returned an HTTP error code of " . $htmlReleases["status"];
	}

?>