<?php

function getGPIDate(DomDocument $xmlstruct): string
{
	$retDate = date("d/m/Y");
	foreach($xmlstruct->getElementsByTagNameNS("*","StartDate") as $date) {
		if (strpos($date->nodeValue,"/12/2018") > 0) 
			return $date->nodeValue;
	}
	return $retDate;
}

function splitfile(string $pernr,string $content): DomDocument
{
	$target = new DomDocument;
	$target->formatOutput = true; 
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
	
	$date = getGPIDate($target);
	printf("Changing effective date to %s for %s\n",$date,$pernr);
	$target->getElementsByTagNameNS("*","EffectiveDate")[0]->nodeValue = $date;
	// Sanity check for 1 person per file
	if ($p->length == 1 ) {
		return $target;
	} else {
		printf("Fatal: Found %d records\n",$p->length);
		exit(0);
	}
}



/*
	Scan GPI files, split it to one file per person
	Update effective date
*/
$d = new DomDocument;
$cnt = 0;
$mapping = [];
$mapping['Electrical Reliability Services, Inc (ERS)'] = 'US002';
	$mapping['Vertiv Corporation'] = 'US001';
	$mapping['Energy Labs, Inc.'] = 'US004';
	$mapping['High Voltage Maintenance Corporation'] = 'US004';

foreach(glob("..\\output\\*.xml") as $file) {
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
		$lcc = $mapping[$d->getElementsByTagNameNS("*","LegalEmployerName")[0]->nodeValue];
		$base = sprintf("..\\splitted\\file%d\\VER%s101220181228_%s.xml",$cnt,$lcc,$pernr);
		printf("Saving output to %s\n",$base);
	//	$modified->save($base);
	}
}
?>