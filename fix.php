<?php

foreach(glob("..\\delta\\*.xml") as $file) {
	printf("Processing file %s\n",$file);
	$content = file_get_contents($file);
	
	$content = str_replace("<gpi:Province/>","",$content);
	
	$content = str_replace("gpi:State","gpi:Province",$content);
	$content = str_replace("ANNUAL","ANSAL",$content);
	
	$content = str_replace("E8000","8000",$content);
	printf("Saving file %s\n",$file);
	
	file_put_contents("..\\output\\".str_replace('..\\delta\\',"",$file),$content);
}

?>