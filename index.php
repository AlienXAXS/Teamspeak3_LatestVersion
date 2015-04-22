<?php

	/*
	 * Created by AlienX
	 * This poopy PHP script will attempt to return the latest server version number by scanning a download repo from teamspeak.com
	 *
	 * If you need help with this script or if it has an error please feel free to jump into
	 * my teamspeak over at ts3.agngaming.com, i am normally about in the evenings :)
	 */

	error_reporting(E_ALL);
	include_once ( "include/htmlParser.class.php" );
	include_once ( "include/teamspeak.class.php" );

	if ( !isset($_GET['bit']) )
		die ( "<strong>Error</strong> Please pass the bit type to the process (index.php?bit=x86/amd64)<br>
			<a href=\"http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]index.php?bit=x86\">http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]index.php?bit=x86</a>
			<br><a href=\"http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]index.php?bit=amd64\">http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]index.php?bit=amd64</a>" );

	$requestedBitVersion = $_GET['bit'];
	if (!( $requestedBitVersion == "amd64" || $requestedBitVersion == "x86" ))
		die ( "<strong>Error</strong> Supported bit versions are: amd64, x86" );

	//Base URL for the repo
	$baseURL = "http://dl.4players.de/ts/releases/";

	//Builds a URL so that WGET can be used against it.
	function buildDownloadURLForWGET($baseURL, $fileDetails)
	{
		$fileName = $fileDetails["file"];
		$fileVersion = $fileDetails["version"];
		return $baseURL . $fileVersion . "/" . $fileName;
	}

	$TeamspeakHandler = new Teamspeak();
	$TeamspeakHandler->setBinaryBitRequired($requestedBitVersion);

	$HTMLParser = new HTMLParser();
	$HTMLParser->setURL($baseURL);
	$HTMLParser->getHTML();
	if ( $HTMLParser->Status == null )
	{
		$teamspeakVersions = $TeamspeakHandler->getTeamspeakVersionsFromHTML($HTMLParser->Contents);

		if ( $teamspeakVersions == null )
			die ( "Unknown error, unable to get versions" );

		//Switch on the requested format (either x86 or amd64)
		switch ( $requestedBitVersion )
		{
			case "amd64":
				$amd64Binary = null;

				//Rotate around each version found in reverse and find a server binary.
				foreach ( array_reverse($teamspeakVersions) as $teamspeakVersion )
				{
					$serverBinaryAMD64 = $TeamspeakHandler->doesVersionContainServerBinary($baseURL, $teamspeakVersion);
					if ( ($serverBinaryAMD64 != null) && ($amd64Binary == null) )
					{
						$amd64Binary["file"] = $serverBinaryAMD64;
						$amd64Binary["version"] = $teamspeakVersion;
						break;
					}
				}

				//If we did not find anything then die with JSON Failure
				if ( $amd64Binary == null )
					die ( json_encode(array("-1", "no version found")) );

				echo json_encode(array($amd64Binary["version"], buildDownloadURLForWGET($baseURL, $amd64Binary)));
				break;

			case "x86":
				$i386Binary = null;

				//Rotate around each version found in reverse and find a server binary.
				foreach ( array_reverse($teamspeakVersions) as $teamspeakVersion )
				{
					$serverBinaryi386 = $TeamspeakHandler->doesVersionContainServerBinary($baseURL, $teamspeakVersion);
					if ( ($serverBinaryi386 != null) && ($i386Binary == null) )
					{
						$i386Binary["file"] = $serverBinaryi386;
						$i386Binary["version"] = $teamspeakVersion;
						break;
					}
				}

				//If we did not find anything then die with JSON Failure
				if ( $i386Binary == null )
					die ( json_encode(array("-1", "no version found")) );

				echo json_encode(array($i386Binary["version"], buildDownloadURLForWGET($baseURL, $i386Binary)));
				break;
		}
	} else {
		echo "Error in GetHTMLContentFromURL, CURL returned an HTTP error code of " . $htmlReleases["status"];
	}
?>