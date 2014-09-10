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
	 * Return last comment of an issue
	 * API reference : https://bitbucket.org/api/1.0/repositories/{accountname}/{repo_slug}/issues/{issue_id}/comments
	*/
	private function getLastComment($id)
	{
		$url = 'https://bitbucket.org/api/1.0/repositories/'.$this->accountname.'/'.$this->repo_slug.'/issues/'.$id.'/comments';

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

		$arr_issue = array();
		$arr_issue = json_decode($response);

		if (empty($arr_issue)) {
			return '';
		}
		else {
			$comment = $arr_issue[0]->content;
			$author = $arr_issue[0]->author_info->display_name.' - '.$this->convertBitbucketDate($arr_issue[0]->utc_updated_on);
			return $comment.' ['.$author.']';
		}

		
	}


	/*
	 * Convert Bitbucket date (ex : 2014-07-08 23:30:23+00:00) into convenient date without hour
	*/
	private function convertBitbucketDate($date)
	{
		$arr_date = date_parse($date);
		$timestamp = mktime($arr_date['hour'], $arr_date['minute'], $arr_date['second'], $arr_date['month'], $arr_date['day'], $arr_date['year']);
		return strftime("%d %B %Y", $timestamp);
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

		// Define columns (Id 6, Type 16, Priority 12, Title 72, Reported_by 22, Responsible 22, Content 65, Status 10, Created 26, Last_updated 26)
		// NB : columns in bitbuckets --> Id, Title, Type, Priority, Status, Assignee, Created, Updated
		$sheet->SetCellValue('A1', 'Title');
		$sheet->SetCellValue('B1', 'Type');
		$sheet->SetCellValue('C1', 'Priority');
		$sheet->SetCellValue('D1', 'Content');
		$sheet->SetCellValue('E1', 'Last_comment');
		$sheet->SetCellValue('F1', 'Status');
		$sheet->SetCellValue('G1', 'Author');
		$sheet->SetCellValue('H1', 'Assignee');
		$sheet->SetCellValue('I1', 'Created');
			
		// Define size of the columns
		$sheet->getColumnDimension('A')->setWidth(75);
		$sheet->getColumnDimension('B')->setWidth(16);
		$sheet->getColumnDimension('C')->setWidth(12);
		$sheet->getColumnDimension('D')->setWidth(65);
		$sheet->getColumnDimension('E')->setWidth(50);
		$sheet->getColumnDimension('F')->setWidth(10);
		$sheet->getColumnDimension('G')->setWidth(22);
		$sheet->getColumnDimension('H')->setWidth(22);
		$sheet->getColumnDimension('I')->setWidth(18);

		// Style
		$styleArray = array( 'font' => array( 'bold' => true));
		$objPHPExcel->getActiveSheet()->getStyle('A1:I1')->applyFromArray($styleArray);


		// Add data
		$i = 2;
		foreach ($arr_issues as $issue) {
			$sheet->SetCellValue('A'.$i, '#'.$issue['id'].' - '.$issue['title']);
			$sheet->SetCellValue('B'.$i, $issue['type']);
			$sheet->SetCellValue('C'.$i, $issue['priority']);
			$sheet->SetCellValue('D'.$i, $issue['content']);
			$sheet->getStyle('D'.$i)->getAlignment()->setWrapText(true);
			$sheet->SetCellValue('E'.$i, $this->getLastComment($issue['id']));
			$sheet->getStyle('E'.$i)->getAlignment()->setWrapText(true);
			$sheet->SetCellValue('F'.$i, $issue['status']);
			$sheet->SetCellValue('G'.$i, $issue['reported_by']);
			$sheet->SetCellValue('H'.$i, $issue['responsible']);
			$sheet->SetCellValue('I'.$i, $this->convertBitbucketDate($issue['created']));
				
			$i++;
		}

		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, "Excel5");
		$filename ='Issues';
		$objWriter->save($filename.'.xls');
	}

}