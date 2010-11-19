<?php
require_once("config.php");
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/site.php");
require_once(WWW_DIR."/lib/category.php");

class NZB 
{
	function NZB() 
	{
		
	}
	
	//
	// Writes out the nzb when processing releases. Moved out of smarty due to memory issues
	// of holding all parts in an array.
	//
	function writeNZBforReleaseId($relid, $relguid, $name, $catId, $path, $echooutput=false)
	{

		$db = new DB();
		$binaries = array();
		$cat = new Category();
		$catrow = $cat->getById($catId);

		$fp = gzopen($path, "w"); 
		if ($fp)
		{
			gzwrite($fp, "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n"); 
			gzwrite($fp, "<!DOCTYPE nzb PUBLIC \"-//newzBin//DTD NZB 1.1//EN\" \"http://www.newzbin.com/DTD/nzb/nzb-1.1.dtd\">\n"); 
			gzwrite($fp, "<nzb xmlns=\"http://www.newzbin.com/DTD/2003/nzb\">\n\n"); 
			gzwrite($fp, "<head>\n"); 
			if ($catrow)
				gzwrite($fp, " <meta type=\"category\">".htmlentities($catrow["title"], ENT_QUOTES)."</meta>\n"); 
			if ($name != "")
				gzwrite($fp, " <meta type=\"name\">".$name."</meta>\n"); 
			gzwrite($fp, "</head>\n\n"); 
	
			$result = $db->queryDirect(sprintf("SELECT binaries.*, UNIX_TIMESTAMP(date) AS unixdate, groups.name as groupname FROM binaries inner join groups on binaries.groupID = groups.ID WHERE binaries.releaseID = %d ORDER BY binaries.name", $relid));
			while ($binrow = mysql_fetch_array($result, MYSQL_BOTH)) 
			{				
				$groups = array();
				$groupsRaw = explode(' ', $binrow['xref']);
				foreach($groupsRaw as $grp) 
					if (preg_match('/^([a-z0-9\.\-_]+):(\d+)?$/i', $grp, $match) && strtolower($grp) !== 'xref') 
						$groups[] = $match[1];
				
				if (count($groups) == 0)
					$groups[] = $binrow["groupname"];

				gzwrite($fp, "<file poster=\"".htmlentities($binrow["fromname"], ENT_QUOTES)."\" date=\"".$binrow["unixdate"]."\" subject=\"".htmlentities($binrow["name"], ENT_QUOTES)." (1/".$binrow["totalParts"].")\">\n"); 
				gzwrite($fp, " <groups>\n"); 
				foreach ($groups as $group)
					gzwrite($fp, "  <group>".$group."</group>\n"); 
				gzwrite($fp, " </groups>\n"); 
				gzwrite($fp, " <segments>\n"); 

				$resparts = $db->queryDirect(sprintf("SELECT DISTINCT(messageID), size, partnumber FROM parts WHERE binaryID = %d ORDER BY partnumber", $binrow["ID"]));
				while ($partsrow = mysql_fetch_array($resparts, MYSQL_BOTH)) 
				{				
					gzwrite($fp, "  <segment bytes=\"".$partsrow["size"]."\" number=\"".$partsrow["partnumber"]."\">".htmlentities($partsrow["messageID"], ENT_QUOTES)."</segment>\n"); 
				}
				gzwrite($fp, " </segments>\n</file>\n"); 
			}
			gzwrite($fp, "<!-- generated by newznab -->\n</nzb>"); 
			gzclose($fp); 
		}
	}
	
	//
	// builds a full path to the nzb file on disk. nzbs are stored in a subdir of their first char.
	//
	function getNZBPath($releaseGuid, $sitenzbpath = "", $createIfDoesntExist = false)
	{
		if ($sitenzbpath == "")
		{
			$s = new Sites;
			$site = $s->get();
			$sitenzbpath = $site->nzbpath;
		}

		$nzbpath = $sitenzbpath.substr($releaseGuid, 0, 1)."/";

		if ($createIfDoesntExist && !file_exists($nzbpath))
				mkdir($nzbpath);
		
		return $nzbpath.$releaseGuid.".nzb.gz";
	}
    
	function nzbFileList($nzb) 
	{
	    $result = array();
	   
	    $nzb = str_replace("\x0F", "", $nzb);
	   	$num_pars = 0;
	    $xml = @simplexml_load_string($nzb);
	    if (!$xml || strtolower($xml->getName()) != 'nzb') 
	      return false;

	    $i=0;
	    foreach($xml->file as $file) 
	    {
			//subject
			$title = $file->attributes()->subject;
			if (preg_match('/\.par2/i', $title)) 
				$num_pars++;

			$result[$i]['title'] = "$title";

			//filesize
			$filesize = 0;
			foreach($file->segments->segment as $segment)
				$filesize += $segment->attributes()->bytes;

			$result[$i]['size'] = $filesize;

			$i++;
	    }
	   
	    return $result;
	}

}
?>
