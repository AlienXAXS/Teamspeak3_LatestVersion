<?php

	class Teamspeak {
		
		private $binaryBitRequired = null;
		function setBinaryBitRequired($binBit) { $this->binaryBitRequired = $binBit; }
		function getBinaryBitRequired() { return $this->binaryBitRequired; }
		
		/*
		 * Function: doesElementMatchServerBinaryRules
		 * Input: String of server/client binary file name from repo
		 * Output: Boolean | True: All matching strings have been found, is a server binary | False: Some or none found, is not a server binary
		 *
		 */
		private function doesElementMatchServerBinaryRules($elementValue)
		{
			$elementsFound = true;
			$findElements = array("linux", "server", "tar", $this->binaryBitRequired);
			foreach ( $findElements as $findElement )
			{
				if ( !(strpos($elementValue, $findElement)) )
				{
					$elementsFound = false;
					break;
				}
			}
			
			return $elementsFound;
		}
		
		/*
		 * Function: getTeamspeakVersionsFromHTML
		 * Input: String of HTML source from repo (normally an apache file browser output)
		 * Output: An array(string) of teamspeak version numbers (aka folders which match regex of ?.?.?.?, eg version 11.20.33.1 inside the repo)
		 */
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
		
		/*
		 * Function: doesVersionContainServerBinary - Used to check if the version obtained from `getTeamspeakVersionsFromHTML` contains a server binary
		 * Input: String $baseURL which contains the baseURL from index.php
		 * Input: String $version which contains the pure version number obtained from `getTeamspeakVersionsFromHTML`.
		 * Output: Boolean | Success: Version number | Failure: Null
		 */
		function doesVersionContainServerBinary($baseURL, $version)
		{
			$return = null;
			$thisBaseURL = $baseURL . $version . "/";
			
			$HTMLParser = new HTMLParser();
			$HTMLParser->setURL($thisBaseURL);
			$HTMLParser->getHTML();
			if ( $HTMLParser->Status == null )
			{
				$dom = new DOMDocument;
				$dom->loadHTML($HTMLParser->Contents);
				$tableElements = $dom->getElementsByTagName("tr");
				foreach ( $tableElements as $tableElement )
				{
					foreach ( $tableElement->childNodes as $tableChildElement )
					{						
						if ($this->doesElementMatchServerBinaryRules($tableChildElement->nodeValue))
						{
							$return = $tableChildElement->nodeValue;
							break;
						}
					}
				}
			} else {
				echo "ERROR";
			}
			
			return $return;
		}
	}

?>