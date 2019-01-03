<?php

function fixbod($file) 
{
	$d = new DomDocument;
	$d->load($file);
		$dir = dirname($file);
	$r = $d->getElementsByTagNameNS("*","LogicalID")[0]->nodeValue;
	$file = str_replace("-","",$r);
	$file = str_replace("1012","1002",$file);
	// VER-US001-1002
	// <oa:BODID>
	 
	$app = $d->getElementsByTagNameNS("*","ApplicationArea");
	$r = $app[0]->getElementsByTagNameNS("*","BODID");
	if ($r->length != 0 && $app->length != 0) {
		printf("%d = %d",$r->length,$app->length);
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
//	$d->save($output);


		$file = sprintf("%s\\%s_%s_%s.xml",$dir,$file,$person,uniqid());
		$d->save($file);
}

fixbod('C:\temp\vertiv\redrop\VERUS0011002_20190103_v1.xml');
?>