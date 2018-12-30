<?php

$skipbanks = true;
$save = false;

/* Read start date */
function getGPIDate(DomDocument $xmlstruct): string
{
	$retDate = date("d/m/Y");
	foreach($xmlstruct->getElementsByTagNameNS("*","StartDate") as $date) {
		if (strpos($date->nodeValue,"/12/2018") > 0) { 
			return $date->nodeValue;
		} else {
		//	printf("Found %s\n",$date->nodeValue);
		//	exit(0);
		}
	}
	return $retDate;
}

function splitfile(string $pernr,string $content): ?DomDocument
{
	global $skipbanks;
	global $save;
	$target = new DomDocument;
	$target->formatOutput = true;
	$target->preserveWhiteSpace = false; 
	$target->loadXML($content);
	$p = $target->getElementsByTagNameNS("*","GlobalPersonData");
	//printf("Length = %d\n",$p->length);
	$remove = [];
	foreach($p as $per) {
		if ($per->getAttribute("PersonNo") != $pernr) {
		//	printf("Removing %s => %s\n",$pernr,$per->getAttribute("PersonNo"));
			$remove[$per->getAttribute("PersonNo")] = $per;
		} else {
		//	printf("Keeping %s => %s\n",$pernr,$per->getAttribute("PersonNo"));
		
		}
		
	}
	// Remove outside of main loop
	//printf("Removing %d segments\n",count($remove));
	$i = 0;
	foreach($remove as $k=>$del) {
		//printf("Counter %d\n",$i++);
		$del->parentNode->removeChild($del);
	}

	$p = $target->getElementsByTagNameNS("*","GlobalPersonData");
	

	// Sanity check for 1 person per file
	if ($p->length == 1 ) {
			if ($skipbanks) {
			$b = $p[0]->getElementsByTagNameNS("*","PaymentMethodData");
			if ($b->length != 0 && $p[0]->childNodes->length == 4) {
				printf("Child nodes == %d, pernr = %d\n",$p[0]->childNodes->length,$pernr);
				return null;
			}
			if ($b->length == 0 && $p[0]->childNodes->length < 4)
			{
				printf("Child nodes < 4: %d, pernr = %d\n",$p[0]->childNodes->length,$pernr);
				return null;
			}
		}
		
			printf("Checking %s\n",$pernr);
			$date = getGPIDate($target);
			//printf("Changing effective date to %s for %s\n",$date,$pernr);
			$target->getElementsByTagNameNS("*","EffectiveDate")[0]->nodeValue = $date;
			return $target;
	} else {
		printf("Fatal: Found %d records\n",$p->length);
		exit(0);
	}
}

function deletefiles($dir)
{
	for($i=1;$i<=4;$i++) {
		$date =substr($dir,-8);
		$splitfolder = sprintf("..\\splitted\\%s\\file%d\\*.xml",$date,$i);
		array_map('unlink', glob($splitfolder));
	}
}

/*
	Scan GPI files, split it to one file per person
	Update effective date
*/
$d = new DomDocument;
$cnt = 0;
$version = 1;
// LCC mapping
$mapping = [];
$mapping['Electrical Reliability Services, Inc (ERS)'] = ['US002','0814'];
$mapping['Vertiv Corporation'] = ['US001','0737'];
$mapping['Energy Labs, Inc.'] = ['US004','0692'];
$mapping['High Voltage Maintenance Corporation'] = ['US004','0815'];

foreach(glob("..\\output\\2018122*",GLOB_ONLYDIR) as $dir) {
	deletefiles($dir);
	foreach(glob($dir."\\*918*.xml") as $file) {
		$cnt++;
		printf("Processing %s\n",$file);
		

		$d->load($file);
		$p = $d->getElementsByTagNameNS("*","GlobalPersonData");
		if ($p->length == 0) {
				printf("No persons found in %s\n",$file);
				continue;
		} else {
			printf("There are %d persons in the file\n",$p->length);
		}
		$xml = $d->saveXML();
		foreach($p as $res) {
		
			$pernr = $res->getAttribute("PersonNo");
			$modified = splitfile($pernr,$xml);
			if ($modified == null) {
				printf("Skipping %s\n",$pernr);
				continue;
			}
			// Set LCC in filename and employee id
			if ($res->parentNode->getElementsByTagNameNS("*","LegalEmployerName")->length == 0) {
				printf("No legal employer found for %s\n",$pernr);
				$lcc = "US001";
				$cc = "0737";
			} else {
				list($lcc,$cc) = $mapping[$res->parentNode->getElementsByTagNameNS("*","LegalEmployerName")[0]->nodeValue];
			}
			$assignment = $modified->getElementsByTagNameNS("*","AssignmentData");
			
			if ($assignment->length == 2) {
				$assignment[0]->setAttribute("AssignmentNumber",$pernr.$cc);
				$assignment[1]->setAttribute("AssignmentNumber",$pernr.$cc);
			}
			$dir = str_replace("..\\output\\","",$dir);
			$base = sprintf("..\\splitted\\%s\\file%d\\VER%s1012%s_v%d_%s.xml",$dir,$cnt,$lcc,$dir,$version,$pernr);
			printf("Saving output to %s\n",$base);
				$modified->save($base);
		}
	}
}
?>