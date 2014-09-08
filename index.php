<html>
<head>
	<title></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>
<?php

require_once('config.php');
require_once('bitbucket.class.php');

$arr_issues = array();

$bitbucket = new Bitbucket(ACCOUNTNAME, REPO_SLUG, USER, PWD);
$arr_issues = $bitbucket->getIssues();

$bitbucket->exportExcel($arr_issues);

echo 'Done !';


?>
</body>
</html>
