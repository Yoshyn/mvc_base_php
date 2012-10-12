<?php
if (!administrateur_connecte())
{
	include CHEMIN_VUE_GLOBALE.'erreur_non_connecte.php';
}
else {
	$_SESSION = array();
	session_destroy();
	include CHEMIN_VUE.'deconnexion_ok.php';
}