<?php

function fixbod($file) 
{
	
	if (! file_exists($file)) {
		printf("File %s not found",$file);
		exit(0);
	}
	$d = new DomDocument;
	$d->load($file);
	$old = $file;
		$dir = dirname($file);
	$r = $d->getElementsByTagNameNS("*","LogicalID")[0]->nodeValue;
	
	
	$file = str_replace("-","",$r);
	$file = str_replace("1012","1002",$file);
	// VER-US001-1002
	// <oa:BODID>
	 
	$app = $d->getElementsByTagNameNS("*","ApplicationArea");
	$r = $app[0]->getElementsByTagNameNS("*","BODID");
	if ($r->length != 0 && $app->length != 0) {

		$app[0]->removeChild($r[0]); 
	}
	$app = $d->getElementsByTagNameNS("*","PayServEmpExtension");
	if ($app->length != 0) {
		$r = $app[0]->getElementsByTagNameNS("*","AlternateIdentifiers");
		if ($r->length != 0 && $app->length != 0) {
			$app[0]->removeChild($r[0]);
		}	
		$r = $d->getElementsByTagNameNS("*","OriginalApplicationArea");
		if ($r->length != 0 && $app->length != 0) {
			$app[0]->removeChild($r[0]); 
			}
	}	
	$person = $d->getElementsByTagNameNS("*","PersonID")[0]->nodeValue;
	$emp = $d->getElementsByTagNameNS("*","EmployeeID");
	if ($emp->length != 0) {
		$d->getElementsByTagNameNS("*","EmployeeID")[0]->nodeValue = 'E'.$person;
	}
//	$d->save($output);
		$pat = glob(sprintf("%s\\%s_%s*.xml",str_replace("inbound","outbound",$dir),$file,$person));

		printf("Deleting %d previous files for %s\n",count($pat),$person);
		array_map("unlink",$pat);
		$file = sprintf("%s\\%s_%s_%s.xml",str_replace("inbound","outbound",$dir),$file,$person,uniqid());
		$new = $file;
		
		printf("File %s saved as %s\n",$old,$new);
		$d->save($file);
}

$pat = 'C:\temp\vertiv\redrop\inbound\9B5D9AF8-F8C9-438D-B274-223F9AD45619.xml';
foreach(glob($pat) as $file) {
	fixbod($file);
}
?>