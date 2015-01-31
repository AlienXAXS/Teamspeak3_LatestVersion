<?php

	class Teamspeak {
		
		private $binaryBitRequired = null;
		function setBinaryBitRequired($binBit) { $this->binaryBitRequired = $binBit; }
		function getBinaryBitRequired() { return $this->binaryBitRequired; }
		
		private function doesElementMatchServerBinaryRules($elementValue)
		{
			$elementsFound = 0;
			$findElements = array("linux", "server", "tar", $this->binaryBitRequired);
			foreach ( $findElements as $findElement )
			{
				if ( strpos($elementValue, $findElement) )
					$elementsFound++;
			}
			
			if ( $elementsFound == count($findElements) )
				return true;
			else
				return false;
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
							$return = $tableChildElement->nodeValue;
							
						/*if ( $bit == "i386" )
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
						};*/
					}
				}
			} else {
				echo "Error - " . $HTMLParser->Status . "<br />";
			}
			
			return $return;
		}
	}

?>