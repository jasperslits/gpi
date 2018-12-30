<?php
foreach(glob("..\\inbound\\*",GLOB_ONLYDIR) as $dir) {
	printf("Directory %s\n",$dir);
	//continue;
	foreach(glob($dir."\\*.xml") as $file) {
		printf("Processing file %s\n",$file);
		$content = file_get_contents($file);
		
		$content = str_replace("<gpi:Province/>","",$content);
		
		$content = str_replace("gpi:State","gpi:Province",$content);
		$content = str_replace(">ANNUAL<",">ANSAL<",$content);
		
		$content = str_replace("E8000","8000",$content);
		$d = new DomDocument;
		$d->formatOutput = true;
		$d->loadXML($content);
		$content = $d->saveXML();
		printf("Saving file %s\n",$file);
		
		file_put_contents(str_replace("inbound","output",$file),$content);
	}
}
?>