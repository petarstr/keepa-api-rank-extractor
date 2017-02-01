<?php


  ?>

<!DOCTYPE html>
<html>
<head>

	<title>AMZ Reports</title>
	<link rel="icon" href="favicon.ico">
	<meta charset='utf-8'>
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

    <style>
    	.buttons {

			margin-top: 5%;
		}

		.button2 {
			margin-top: 2%;
			
		}

    </style>
</head>
<body>

<div class='row'>
	<div class='col-sm-4'></div>

	<div class='col-sm-4' align='center'>
		<div class="notice" align="center"> <h2>Choose a file to check</h2> </div>

		<div class='buttons'>
			<form action="keepa.php" method='post' enctype="multipart/form-data">
			  <input type="file" class='btn btn-default btn-lg' name="file" accept=".xlsx" required>
			  <input type="submit" class='btn btn-info btn-lg button2' name="submit">
			</form>
		</div>

	</div>



</body>
</html>