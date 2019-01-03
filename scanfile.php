<?php

class GPIfix {
	private $skipbanks = true;
	private $save = true;
	

/* Read start date */
private function getGPIDate(DomDocument $xmlstruct): string
{
	$retDate = date("d/m/Y");
	$nodes = ['AssignmentData','AddressDetails'];
	foreach($nodes as $node) {
		$r = $xmlstruct->getElementsByTagNameNS("*",$node);
		printf("R %s = %d\n",$node,$r->length);
		if ($r->length != 0) {
			$date = $r[0]->getElementsByTagNameNS("*","StartDate")[0];
			if (strpos($date->nodeValue,"/12/2018") > 0) { 
				return $date->nodeValue;
			} 
		}
	}
	
	return $retDate;
}

private function splitfile(string $pernr,string $content): ?DomDocument
{
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
	$payroll = $target->getElementsByTagNameNS("*","Payroll");

	// Sanity check for 1 person per file
	if ($p->length == 1 ) {
			if ($this->skipbanks) {
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
		
		//<gpi:AssignmentAction>
	
		$mgr = $target->getElementsByTagNameNS("*","AssignmentAction");
		if ($mgr->length != 0) {
			$a = $mgr[0]->nodeValue;
			if ($a == 'MANAGER_CHANGE') {
				printf("Manager change\n");
				return null;
			}
		}
	
		$date = $this->getGPIDate($target);
	
		printf("Changing effective date to %s for %s\n",$date,$pernr);
		if ($date == date("d/m/Y")) {
			return null;
		}
		
		$target->getElementsByTagNameNS("*","EffectiveDate")[0]->nodeValue = $date;
		return $target;
	} else {
		printf("Fatal: Found %d records\n",$p->length);
		exit(0);
	}
}

private function deletefiles($dir) {
	$date = substr($dir,-8);
	$dir = str_replace("output","splitted2",$dir);
	foreach(glob($dir."\\*",GLOB_ONLYDIR) as $filefolder) { 
		array_map('unlink', glob($filefolder."\\*.xml"));
		rmdir($filefolder);
	}
}

/*
	Scan GPI files, split it to one file per person
	Update effective date
*/
public function __construct()
{
	printf("Operation, skip banks = %s\n",$this->skipbanks);
	//$this->dispatch();
}

public function split() {

	$d = new DomDocument;
	$cnt = 0;
	$version = 1;
	//$filterArr = ["8000000203",'8000034258','8000005485'];
	$filterArr = [
	'8000034820',
'8000034821',
'8000035020',
'8000035254',
'8000035026',
'8000034973',
'8000035024',
'8000004924',
'8000035302',
'8000035298',
'8000035252',
'8000035256',
'8000009724'];
	$filterArr = ['8000035033'];

	// LCC mapping
	$mapping = [];
	$mapping['Electrical Reliability Services, Inc (ERS)'] = ['US002','0814'];
	$mapping['Vertiv Corporation'] = ['US001','0737'];
	$mapping['Energy Labs, Inc.'] = ['US004','0692'];
	$mapping['High Voltage Maintenance Corporation'] = ['US003','0815'];

foreach(glob("..\\output\\20181227*",GLOB_ONLYDIR) as $dir) {
	printf("Processing %s\n",$dir);
	$this->deletefiles($dir);
	$cnt = 0;
	foreach(glob($dir."\\*858.xml") as $file) {
		$cnt++;
		printf("Processing %s\n",$file);
		$base = str_replace("output","splitted2",$dir);
		$folder = $base."\\file".$cnt;
		printf("Folder = %s\n",$folder);
		if (! is_dir($folder)) {
			mkdir($folder,0777,true);
		}
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
			if (!empty($filterArr) && ! in_array($pernr,$filterArr)) {
			
				printf("Skip!".$pernr.", count = %d\n",count($filterArr));
				
				continue;
			} 
			$modified = $this->splitfile($pernr,$xml);
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
			$clean = str_replace("..\\output\\","",$dir);
			$base = sprintf("..\\splitted2\\%s\\file%d\\VER%s1012%s_v%d_%s.xml",$clean,$cnt,$lcc,$clean,$version,$pernr);
			printf("Saving output to %s\n",$base);
				$modified->save($base);
		}
	}
}
}
	public function fixcontent()
	{
		foreach(glob("..\\inbound\\201*",GLOB_ONLYDIR) as $dir) {
	printf("Directory %s\n",$dir);
	$output = str_replace("inbound","output",$dir);
	array_map('unlink', glob($output."\\*.xml"));
	
	//continue;
	foreach(glob($dir."\\*.xml") as $file) {
		printf("Processing file %s\n",$file);
		$content = file_get_contents($file);	
		$content = str_replace("E8000","8000",$content);
		$d = new DomDocument;
		$d->formatOutput = true;
		$d->loadXML($content);
		$content = $d->saveXML();
		printf("Saving file %s\n",$file);
		
		file_put_contents(str_replace("inbound","output",$file),$content);
	}
}
	}


}
$g = new GPIfix;
//$g->fixcontent();
$g->split();
?>