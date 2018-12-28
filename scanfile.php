<?php

function getGPIDate($xmlstruct)
{
	$retDate = date("d/m/Y");
	foreach($xmlstruct->getElementsByTagNameNS("*","StartDate") as $date) {
		if (strpos($date->nodeValue,"/12/2018") > 0) 
			return $date->nodeValue;
	}
	return $retDate;
}

function splitfile($pernr,$content)
{
	$target = new DomDocument;
	$target->loadXML($content);
	$p = $target->getElementsByTagNameNS("*","GlobalPersonData");
	//printf("Length = %d\n",$p->length);
	$remove = [];
	foreach($p as $per) {
		if ($per->getAttribute("PersonNo") != $pernr) {
		//	printf("Removing %s => %s\n",$pernr,$per->getAttribute("PersonNo"));
			$remove[] = $per;
		} else {
		//	printf("Keeping %s => %s\n",$pernr,$per->getAttribute("PersonNo"));
		}
		
	}
	foreach($remove as $del) {
		$target->getElementsByTagNameNS("*","EmployerData")[0]->removeChild($del);
			
	}
	

	$p = $target->getElementsByTagNameNS("*","GlobalPersonData");
	
	$date = getGPIDate($target);
	printf("Changing effective date to %s for %s\n",$date,$pernr);
	$target->getElementsByTagNameNS("*","EffectiveDate")[0]->nodeValue = $date;
	if ($p->length == 1 ) {
		$target->save("splitted\\VER000001102_$pernr.xml");
	} else {
		printf("Fatal: Found %d records\n",$p->length);
		exit(0);
	}
}

$d = new DomDocument;
foreach(glob("output\\VERUS0031012201812270858_v4.xml") as $file) {
	printf("Processing %s\n",$file);
	$d->load($file);
	$p = $d->getElementsByTagNameNS("*","GlobalPersonData");
	if ($p->length == 0) {
			printf("No records found in %s\n",$file);
	}
	foreach($p as $res) {
	//	echo $res->getAttribute("PersonNo")."\n";
		splitfile($res->getAttribute("PersonNo"),$d->saveXML());
	
	}
}
?>