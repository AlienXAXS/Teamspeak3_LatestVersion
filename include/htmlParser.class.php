<?php
	
	class HTMLParser {
		
		private $URL = null;
		function setURL ( /*string*/ $url ) { $this->URL = $url; }
		function getURL () { return $this->URL; }
		
		
		/*
		 * Function: getHTML
		 * Input: None
		 * Output: Array[status], Contains error messages - null of no error
		 * Output: Array[contents], Contains the HTML page contents that were downloaded from the $URL
		 */
		function getHTML()
		{
			if ( $this->URL == null )
				die ( "Invalid usage of HTMLParser, set the URL first via setURL(\"url\")" );
			
			//setup array.
			$temp["status"] = null;
			$temp["contents"] = null;
		
			$c = curl_init($this->URL);
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
	}

?>