<?php

/*
 * 
 * API Reference : https://confluence.atlassian.com/display/BITBUCKET/issues+Resource
 *
*/

class Bitbucket
{
	private $accountname;
	private $repo_slug;
	private $user;
	private $pwd;


	public function __construct($accountname, $repo_slug, $user, $pwd)
	{
		$this->accountname = $accountname;
		$this->repo_slug = $repo_slug;
		$this->user = $user;
		$this->pwd = $pwd;
	}


	/*
	 * Return open/new issues order by priority
	 * Syntaxe : https://bitbucket.org/api/1.0/repositories/{accountname}/{repo_slug}/issues?parameter=value&parameter=value
	*/
	public function getIssues()
	{
		$url = 'https://bitbucket.org/api/1.0/repositories/'.$this->accountname.'/'.$this->repo_slug.'/issues?limit=50&status=new&status=open&sort=-priority';

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $this->user . ":" . $this->pwd);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);

		if ($info['http_code'] != 200) {
			echo 'Error HTTP '.$info['http_code'];
			return false;
		}

		$result = json_decode($response);

		$arr_issues = array();

		$i = 0;
		foreach ($result->issues as $issue) {
			$arr_issues[$i]['id'] = $issue->local_id;
			$arr_issues[$i]['status'] = $issue->status;
			$arr_issues[$i]['priority'] = $issue->priority;
			$arr_issues[$i]['title'] = $issue->title;
			$arr_issues[$i]['reported_by'] = $issue->reported_by->display_name;
			$arr_issues[$i]['responsible'] = $issue->responsible->display_name;
			$arr_issues[$i]['type'] = $issue->metadata->kind;
			$arr_issues[$i]['content'] = $issue->content;
			$arr_issues[$i]['created'] = $issue->created_on;
			$arr_issues[$i]['last_updated'] = $issue->utc_last_updated;
			++$i;
		}

		return $arr_issues;
	}


	/*
	 * Export issues into Excel file, using PHPExcel library
	*/
	public function exportExcel($arr_issues)
	{

		require_once 'lib\PHPExcel.php';
				
		$objPHPExcel = new PHPExcel();
		$objPHPExcel->setActiveSheetIndex(0);
		$sheet = $objPHPExcel->getActiveSheet();
		$sheet->setTitle('Issues');

		// Define columns (Id, Type, Priority, Title, Reported_by, Responsible, Content, Status, Created, Last_updated)
		// NB : columns in bitbuckets --> Id, Title, Type, Priority, Status, Assignee, Created, Updated
		$sheet->SetCellValue('A1', 'Id');
		$sheet->SetCellValue('B1', 'Type');
		$sheet->SetCellValue('C1', 'Priority');
		$sheet->SetCellValue('D1', 'Title');
		$sheet->SetCellValue('E1', 'Reported_by');
		$sheet->SetCellValue('F1', 'Responsible');
		$sheet->SetCellValue('G1', 'Content');
		$sheet->SetCellValue('H1', 'Status');
		$sheet->SetCellValue('I1', 'Created');
		$sheet->SetCellValue('J1', 'Updated');
			
		// Define size of the columns
		$sheet->getColumnDimension('A')->setWidth(6);
		$sheet->getColumnDimension('B')->setWidth(16);
		$sheet->getColumnDimension('C')->setWidth(12);
		$sheet->getColumnDimension('D')->setWidth(72);
		$sheet->getColumnDimension('E')->setWidth(22);
		$sheet->getColumnDimension('F')->setWidth(22);
		$sheet->getColumnDimension('G')->setWidth(65);
		$sheet->getColumnDimension('H')->setWidth(10);
		$sheet->getColumnDimension('I')->setWidth(26);
		$sheet->getColumnDimension('J')->setWidth(26);

		// Style
		$styleArray = array( 'font' => array( 'bold' => true));
		$objPHPExcel->getActiveSheet()->getStyle('A1:J1')->applyFromArray($styleArray);


		// Add data
		$i = 2;
		foreach ($arr_issues as $issue) {
			$sheet->SetCellValue('A'.$i, $issue['id']);
			$sheet->SetCellValue('B'.$i, $issue['type']);
			$sheet->SetCellValue('C'.$i, $issue['priority']);
			$sheet->SetCellValue('D'.$i, $issue['title']);
			$sheet->SetCellValue('E'.$i, $issue['reported_by']);
			$sheet->SetCellValue('F'.$i, $issue['responsible']);
			$sheet->SetCellValue('G'.$i, $issue['content']);
			$sheet->getStyle('G'.$i)->getAlignment()->setWrapText(true);
			$sheet->SetCellValue('H'.$i, $issue['status']);
			$sheet->SetCellValue('I'.$i, $issue['created']);
			$sheet->SetCellValue('J'.$i, $issue['last_updated']);
				
			$i++;
		}

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, "Excel5");
		$filename ='Issues';
		$objWriter->save($filename.'.xls');
	}

}