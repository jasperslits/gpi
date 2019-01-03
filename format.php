<?php
	
	//continue;
	echo getcwd();
	foreach(glob("..\\inbound - Copy\\20181229\\*.xml") as $file) {
		printf("Processing file %s\n",$file);
		$d = new DomDocument;
		$d->formatOutput = true;
		$d->load($file);
		printf("Saving file %s\n",$file);
		$output = str_replace("inbound - Copy","cleaned",$file);
		$d->save($output);
	
}
?>