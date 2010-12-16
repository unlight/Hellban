<?php if (!defined('APPLICATION')) exit();?>
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html>
<head>
<title><?php echo $this->Code;?> <?php echo $this->Status;?></title>
</head>
<body>
<h1><?php echo $this->Status;?></h1>
<p><?php echo $this->Description; ?></p>
<hr>
<address><?php echo $_SERVER['SERVER_SOFTWARE'];?> Server at <?php echo $_SERVER['SERVER_NAME'];?> Port <?php echo $_SERVER['SERVER_PORT'];?></address>
</body>
</html>