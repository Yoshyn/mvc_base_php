<?php
require_once CHEMIN_LIBS.'PDOMySQL.php';
require_once CHEMIN_LIBS.'IhmBD.php';
try {
	$ihmAdministrateur = new IhmBD("administrateurs", PDOMySqlSingleton::getInstance(),$_SERVER [ 'REQUEST_URI'],"&");
	include CHEMIN_VUE.'gestionAdministrateur.php';
}
catch ( Exception $exc)
{
	echo "<b> Erreur rencontree </b> " . $exc->getCode() . "-----" . $exc->getMessage() . "\n";
}
?>