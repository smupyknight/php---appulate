<!DOCTYPE html>
<html>
<head>
	<title></title>
</head>
<body>
	<div id="response">

	</div>
</body>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
<script>
	$.ajax({
		url: "index.php",
		type: "POST",
		data: { data: "Data"},
		contentType: "application/x-www-form-urlencoded",
		success: function(response) {
			$("#response").text("Input: " + JSON.stringify(response));
		},
		error: function(response) {
			$("#response").text("Input: " + JSON.stringify(response));
		}
	})
</script>
</html>