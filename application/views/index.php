<!DOCTYPE>
<html>
<head>
    <title>Oficinas Virtuales Amuebladas</title>
</head>

<body>

<?php
//Defining template variables if they went not defined in the last request
$_T = (isset($_T) ? $_T : '');
$_B = (isset($_B) ? $_B : '');
$_L = (isset($_L) ? $_L : '');
$_R = (isset($_R) ? $_R : '');
$_F = (isset($_F) ? $_F : '');
?>

<div id="wrapper">
    <?php require(template_top($_T)); ?>
    <?php require(template_left($_L)); ?>
    <?php require(template_right($_R)); ?>
    <?php require(template_body($_B)); ?>
    <?php require(template_footer($_F)); ?>
</div>
</body>
</html>