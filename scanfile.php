<?php

class GPIfix {
	private $skipbanks = true;
	public $save = true;
	private $destinationFolder = "splitted2";
	private $today;

	/* Read start date */
	private function getGPIDate(DomDocument $xmlstruct): string
	{
		$retDate = $this->today;
		$nodes = ['AssignmentData','AddressDetails','PaymentMethodData'];
		foreach($nodes as $node) {
			$r = $xmlstruct->getElementsByTagNameNS("*",$node);
			
			if ($r->length != 0) {
				$startdate = ($node == 'PaymentMethodData') ? 'PayMethodStartDate' : 'StartDate';
				$date = $r[0]->getElementsByTagNameNS("*",$startdate)[0];
				if (! empty($date) && ( strpos($date->nodeValue,"/12/2018") > 0 || strpos($date->nodeValue,"/01/2019") > 0)) { 
					return $date->nodeValue;
				} 
			}
		}
		
		return $retDate;
	}

	private function banks(string $pernr,Domnode $p) {
		
		// Sanity check for 1 person per file
			$b = $p->getElementsByTagNameNS("*","PaymentMethodData");
			if ($b->length != 0) {
				printf("Removing banks for %s. Child nodes = %d\n",$pernr,$p->childNodes->length);
				$p->removeChild($b[0]);
				if ($p->childNodes->length == 3) {
					printf("Skipping empty file for %s\n",$pernr);
					return false;
				} else {
					return $p;
					//return false;
				}
			} else {
				return $p;
			}
	}

private function splitfile(string $pernr,string $content,$type = "weekly"): ?DomDocument
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
			if ($this->skipbanks) {
				$banks = $this->banks($pernr,$per);
				if (! $banks) {
					//printf("Skipping %s\n",$pernr);
					//continue;
					return null;
				} 
			}
		}
		
	}
	
	
	// Remove outside of main loop
	//printf("Removing %d segments\n",count($remove));
	$i = 0;
	

	$p = $target->getElementsByTagNameNS("*","GlobalPersonData");
	

		
		foreach($remove as $k=>$del) {
		//printf("Counter %d\n",$i++);
			$del->parentNode->removeChild($del);
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
		//	return null;
		}
		
		$target->getElementsByTagNameNS("*","EffectiveDate")[0]->nodeValue = $date;
		return $target;
	
}

private function deletefiles($dir) {
	$date = substr($dir,-8);
	$dir = str_replace("output",$this->destinationFolder,$dir);
	foreach(glob($dir."\\*",GLOB_ONLYDIR) as $filefolder) { 
		array_map('unlink', glob($filefolder."\\*.xml"));
		rmdir($filefolder);
	}
}

/*
	Scan GPI files, split it to one file per person
	Update effective date
*/
public function __construct($destinationFolder = "")
{
	printf("Operation, skip banks = %s\n",$this->skipbanks);
	$this->today = date("d/m/Y");
	if (!empty($destinationFolder)) {
		$this->destinationFolder = $destinationFolder;
	}
	printf("Running with destination folder = %s\n",$this->destinationFolder);
	//$this->dispatch();
}

public function split($dirfilter,$filefilter,$type = "") {

	$d = new DomDocument;
	$cnt = 0;
	$version = 2;
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
	$filterArr = [];

	// LCC mapping
	$mapping = [];
	$mapping['Electrical Reliability Services, Inc (ERS)'] = ['US002','0814'];
	$mapping['Vertiv Corporation'] = ['US001','0737'];
	$mapping['Energy Labs, Inc.'] = ['US004','0692'];
	$mapping['High Voltage Maintenance Corporation'] = ['US003','0815'];

foreach(glob($dirfilter,GLOB_ONLYDIR) as $dir) {
	printf("Processing %s\n",$dir);
	$this->deletefiles($dir);
	$cnt = 0;
	foreach(glob($dir.$filefilter) as $file) {
		$cnt++;
		printf("Processing %s\n",$file);
		
		$d->load($file);
		$payroll = $d->getElementsByTagNameNS("*","Payroll")[0]->nodeValue;
	
		if (! empty($type) && strpos($payroll," ".$type." ") === false) {
			printf("Skipping Payroll %s\n",$payroll);
			continue;
		}
		
		$base = str_replace("output",$this->destinationFolder,$dir);
		$folder = $base."\\file".$cnt;
		printf("Target Folder = %s\n",$folder);
		if (! is_dir($folder)) {
			mkdir($folder,0777,true);
		}
		
		$p = $d->getElementsByTagNameNS("*","GlobalPersonData");
		if ($p->length == 0) {
				printf("No persons found in %s\n",$file);
				continue;
		} else {
			printf("There are %d persons in the file\n",$p->length);
		}
		$l = $p->length;
		$xml = $d->saveXML();
		$i =0;
		foreach($p as $res) {
			$i++;
			$pernr = $res->getAttribute("PersonNo");
			if (!empty($filterArr) && ! in_array($pernr,$filterArr)) {
			
				printf("Skip ".$pernr.", count = %d\n",count($filterArr));
				
				continue;
			} 
			
			printf("Processing %d of %d (%s %%)\n",$i,$l,round($i/$l*100,1));
			
			$modified = $this->splitfile($pernr,$xml);
			if ($modified == null) {
				printf("Skipping %s\n",$pernr);
				continue;
			}
			// Set LCC in filename and employee id
			
			if ($res->parentNode->getElementsByTagNameNS("*","LegalEmployerName")->length == 0) {
				printf("No legal employer found for %s\n",$pernr);
				$lcc = "00000";
				//$cc = "0737";
				$legal = false;
			} else {
				list($lcc,$cc) = $mapping[$res->parentNode->getElementsByTagNameNS("*","LegalEmployerName")[0]->nodeValue];
				$legal = true;
			}
			
			if ($legal) {
				/*
				$assignment = $modified->getElementsByTagNameNS("*","AssignmentData");
				
				if ($assignment->length == 2) {
					$assignment[0]->setAttribute("AssignmentNumber",$pernr.$cc);
					$assignment[1]->setAttribute("AssignmentNumber",$pernr.$cc);
				}
				*/
			}
			$clean = str_replace("..\\output\\","",$dir);
			$base = sprintf("..\\splitted2\\%s\\file%d\\VER%s1012%s_v%d_%s_%s.xml",$clean,$cnt,$lcc,$clean,$version,$type,$pernr);
			
			if ($this->save) {
				printf("Saving output to %s\n",$base);
				$modified->save($base);
			} else {
				echo $modified->saveXML();
				exit(0);
			}
		}
	}
}
}
	public function fixcontent($dirfilter,$filefilter)
	{
		foreach(glob($dirfilter,GLOB_ONLYDIR) as $dir) {
		printf("Directory %s\n",$dir);
		$output = str_replace("inbound","output",$dir);
		if (! is_dir($output)) {
			mkdir($output);
		} else {
			array_map('unlink', glob($output."\\*.xml"));
		}
	
		//continue;
		foreach(glob($dir.$filefilter) as $file) {
			printf("Processing file %s\n",$file);
			$d = new DomDocument;
			$d->formatOutput = true;
			$d->load($file);
			printf("Saving file %s\n",$file);
			
			$d->save(str_replace("inbound","output",$file));
		}
	}
}


}
$g = new GPIfix("splitted2");
$g->save = true;
$dirfilter = "..\\inbound\\20190105*";
$filefilter = "\\*.xml";
$g->fixcontent($dirfilter,$filefilter);
$dirfilter = "..\\output\\20190105*";
$filefilter = "\\*.xml";
$g->split($dirfilter,$filefilter,"");
?>