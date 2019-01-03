<?php
/*
 <gpi:SalaryDetails StartDate="11/12/2018" EndDate="31/12/4712">
                <gpi:StartDate>11/12/2018</gpi:StartDate>
                <gpi:EndDate>31/12/4712</gpi:EndDate>
                <gpi:SalaryChangeAction>Hire</gpi:SalaryChangeAction>
                <gpi:SalaryActionReason>300000063937607</gpi:SalaryActionReason>
                <gpi:SalaryBasis>USD Hourly Salary</gpi:SalaryBasis>
                <gpi:Salary>41.28</gpi:Salary>
                <gpi:PaymentFrequency>HOURLY</gpi:PaymentFrequency>
                <gpi:CurrencyCode>USD</gpi:CurrencyCode>
              </gpi:SalaryDetails>
*/

class gpi {
	private $pernr;
	private $type;


	private function getValue($d,$value)
	{
		$r = $d->getElementsByTagNameNS("*",$value);

		if ($r->length == 1) {
			return $r[0]->nodeValue;
		} else {
			printf("Value %s not found\n",$value);
		}
	}

	public function dispatch($d) {
		
		$s = $d->getElementsByTagNameNS("*","GlobalPersonData");
		switch ($this->type)
		{
			case 'Salary':
				printf("Pernr;begda;endda;amount;wagetype\n");
				foreach($s as $emp) {
					
					$res = $this->salarydetails($emp);
					if (strlen($res) > 0 ) {
						$pernr = $emp->getAttribute("PersonNo");
						printf("%s;%s",$pernr,$res);
					}
				}
			break;
		
		case 'Termination':
			printf("PERNR;Term date;Term reason\n");
			foreach($s as $emp) {
				
				$res = $this->terminationdetails($emp);
				if (strlen($res) > 0 ) {
					$pernr = $emp->getAttribute("PersonNo");
					printf("%s;%s",$pernr,$res);
				}
			}
			break;
		
		case 'Hire':
			printf("Pernr;hire date;hire reason\n");
			foreach($s as $emp) {
				
				$res = $this->hiredetails($emp);
				if (strlen($res) > 0 ) {
					$pernr = $emp->getAttribute("PersonNo");
					printf("%s;%s",$pernr,$res);
				}
			}
			break;
		
		case 'Assignment':
			printf("Pernr;begda;endda;hire reason\n");
			foreach($s as $emp) {
				
				$res = $this->assignmentdetails($emp);
				if (strlen($res) > 0 ) {
					$pernr = $emp->getAttribute("PersonNo");
					printf("%s;%s",$pernr,$res);
				}
			}
			break;
			
		
			break;
		
		case 'Address':
			printf("Pernr;begda;endda;hire reason\n");
			foreach($s as $emp) {
				
				$res = $this->addressdetails($emp);
				if (strlen($res) > 0 ) {
					$pernr = $emp->getAttribute("PersonNo");
					printf("%s;%s",$pernr,$res);
				}
			}
			break;
		
		case 'Bank':
			printf("Pernr;begda;endda;bank;account;type;owner\n");
			foreach($s as $emp) {
				
				$res = $this->bankdetails($emp);
				if (strlen($res) > 0 ) {
					$pernr = $emp->getAttribute("PersonNo");
					printf("%s;%s",$pernr,$res);
				}
			}
			break;
		}
	}
	
	private function formatdate($date) {
		$d = explode("/",$date);
		if ($date == '31/12/4712') {
			return '9999-12-31';
		}
		return $d[2]."-".$d[1]."-".$d[0];
	}
	
	/*
	    <gpi:AssignmentData AssignmentNumber="80000076370737" StartDate="17/12/2018" EndDate="31/12/4712">
              <gpi:AssignmentNumber>8000007637</gpi:AssignmentNumber>
              <gpi:StartDate>17/12/2018</gpi:StartDate>
              <gpi:EndDate>31/12/4712</gpi:EndDate>
              <gpi:AssignmentAction>VTV_RLOA</gpi:AssignmentAction>
			  
	*/
	
	private function assignmentdetails($d)
	{
		$s = $d->getElementsByTagNameNS("*","AssignmentData");
		
		foreach($s as $result) {
			if ($result->childNodes->length > 4) {
				var_dump($result);
				exit(0);
				$begda = $this->formatdate($this->getValue($result,"StartDate"));

				$endda = $this->formatdate($this->getValue($result,"EndDate"));
				if (strpos($begda,"2018-12") !== false) {
					return sprintf("%s;%s;%s;%s\n",
					$begda,
					$endda,
					$this->getValue($result,"AssignmentAction"),
					$this->getValue($result,"AssignmentActionReason"));
				} 
			}
		}
	}

	private function salarydetails($d)
	{
		$s = $d->getElementsByTagNameNS("*","SalaryDetails");
		
		foreach($s as $result) {
			if ($result->childNodes->length != 0) {
				$begda = $this->formatdate($this->getValue($result,"StartDate"));

				$endda = $this->formatdate($this->getValue($result,"EndDate"));
				if (strpos($begda,"2018-12") !== false) {
					return sprintf("%s;%s;%s;%s\n",
					$begda,
					$endda,
					$this->getValue($result,"Salary"),
					$this->getValue($result,"PaymentFrequency"));
				} 
			}
		}
	}
	
	/*		<gpi:BankAccountDetails AccountName="Brumfield, Jamie">
								<gpi:AccountName>Brumfield, Jamie</gpi:AccountName>
								<gpi:BankName>USB Bank Test</gpi:BankName>
								<gpi:BankBranchName>044000642</gpi:BankBranchName>
								<gpi:BankBranchNumber>044000642</gpi:BankBranchNumber>
								<gpi:IBAN/>
								<gpi:Country>US</gpi:Country>
								<gpi:AccountType>CHECKING</gpi:AccountType>
								<gpi:AccountNumber>354692030035</gpi:AccountNumber>
								<gpi:SWIFTCode/>
								<gpi:BankAccountStartDate>16/11/2018</gpi:BankAccountStartDate>
								<gpi:BankAccountEndDate>31/12/4712</gpi:BankAccountEndDate>
							</gpi:BankAccountDetails> */
	
	
	
		private function bankdetails($d)
	{
		$s = $d->getElementsByTagNameNS("*","PaymentMethodDetails");
		
		foreach($s as $result) {
			if ($result->childNodes->length != 0) {
				$begda = $this->formatdate($this->getValue($result,"PayMethodStartDate"));
				$endda = $this->formatdate($this->getValue($result,"PayMethodEndDate"));
			
				if (strpos($begda,"2018-12") !== false || strpos($endda,"2018-12") !== false) {
					return sprintf("%s;%s;%s;%s;%s;%s\n",
					$begda,$endda,$this->getValue($result,"BankBranchNumber"),$this->getValue($result,"AccountNumber"),$this->getValue($result,"AccountType"),$this->getValue($result,"AccountName"));
				} 
			}
		}
	}
	/*
	<gpi:TerminationDetails>
						<gpi:TerminationAction>Involuntary Termination</gpi:TerminationAction>
						<gpi:TerminationReason>Attendance</gpi:TerminationReason>
						<gpi:NotificationDate>06/12/2018</gpi:NotificationDate>
						<gpi:TerminationDate>08/12/2018</gpi:TerminationDate>
						<gpi:LastStandardEarningsDate>08/12/2018</gpi:LastStandardEarningsDate>
						<gpi:LastStandardProcessDate>09/12/2018</gpi:LastStandardProcessDate>
						<gpi:FinalCloseDate>31/12/4712</gpi:FinalCloseDate>
						<gpi:RecommendedForRehire>N</gpi:RecommendedForRehire>
					</gpi:TerminationDetails>
	*/
	private function terminationdetails($d)
	{
		$s = $d->getElementsByTagNameNS("*","TerminationDetails");
		
		foreach($s as $result) {
			if ($result->childNodes->length != 0) {
				$begda = $this->formatdate($this->getValue($result,"TerminationDate"));

				if (strpos($begda,"2018-12") !== false) {
					return sprintf("%s;%s\n",
					$begda,$this->getValue($result,"TerminationReason"));
				} 
			}
		}
	}
	
	/*
	<gpi:ServiceData>
						<gpi:ActionTypeIndicator IndicatorRepeated="NA">O</gpi:ActionTypeIndicator>
						<gpi:IndicatorRepeated>NA</gpi:IndicatorRepeated>
						<gpi:HireDate>17/09/2018</gpi:HireDate>
						<gpi:EnterpriseSeniorityDate/>
						<gpi:LegalEmployerSeniorityDate/>
						<gpi:HireAction>HIRE</gpi:HireAction>
						<gpi:ActionReason>NEWHIRE</gpi:ActionReason>
					</gpi:ServiceData>
	*/
	
	private function hiredetails($d) {
		$s = $d->getElementsByTagNameNS("*","ServiceData");
		
		foreach($s as $result) {
			if ($result->childNodes->length != 0) {
				$begda = $this->formatdate($this->getValue($result,"HireDate"));

			//	$endda = $this->formatdate($this->getValue($result,"EndDate"));
				if (strpos($begda,"2018-12") !== false) {
					return sprintf("%s;%s;%s\n",
					$begda,$this->getValue($result,"HireAction"),$this->getValue($result,"ActionReason"));
				} 
			}
		}
	}

	/*
			<gpi:AddressDetails AddressType="HOME" StartDate="31/07/2017" EndDate="31/12/4712">
						<gpi:AddressType>HOME</gpi:AddressType>
						<gpi:PrimaryFlag>Y</gpi:PrimaryFlag>
						<gpi:StartDate>31/07/2017</gpi:StartDate>
						<gpi:EndDate>31/12/4712</gpi:EndDate>
						<gpi:AddressLine1 Label="Address Line 1">2889 Courtright Road</gpi:AddressLine1>
						<gpi:AddressLine2 Label="Address Line 2"/>
						<gpi:AddressLine3 Label="Address Line 3"/>
						<gpi:AddressLine4 Label="Address Line 4"/>
						<gpi:City>Columbus</gpi:City>
						<gpi:c>OH</gpi:State>
						<gpi:FloorNumber/>
						<gpi:Building/>
						<gpi:County/>
						<gpi:Province/>
						<gpi:PostalCode>43232</gpi:PostalCode>
						<gpi:Country>US</gpi:Country>
						
			*/

	private function addressdetails($d)
	{
		$s = $d->getElementsByTagNameNS("*","AddressDetails");
		
		foreach($s as $result) {
			if ($result->childNodes->length != 0) {
				$begda = $this->formatdate($this->getValue($result,"StartDate"));
				$endda = $this->formatdate($this->getValue($result,"EndDate"));
				if (strpos($begda,"2018-12") !== false) {
					return sprintf("%s;%s;%s;%s;%s;%s\n",
					$begda,$endda,$this->getValue($result,"AddressLine1"),$this->getValue($result,"City"),$this->getValue($result,"State"),$this->getValue($result,"PostalCode"));
				} 
			}
		}
	}
	
	public function __construct($file,$type) {
	
		$this->type = $type;
		if (! file_exists($file)) {
			printf("File %s does not exist\n",$file);
			exit(0);
		}
		printf("File %s\n",$file);
		$d = new DomDocument;
		$d->load($file);
		
		$this->dispatch($d);
	}
	
}

$f = new gpi('C:\temp\vertiv\output\20181227\VER000001012201812270918.bak','Hire');
?>