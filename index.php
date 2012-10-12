<?php

// Initialisation
include 'global/init.php';

// Début de la tamporisation de sortie
ob_start();

// Si un module est specifié, on regarde s'il existe
if (!empty($_GET['module'])) {

	$module = dirname(__FILE__).'/modules/'.$_GET['module'].'/';
	
	// Si l'actionMod est specifiée, on l'utilise, sinon, on tente une actionMod par défaut
	$actionMod = (!empty($_GET['actionMod'])) ? $_GET['actionMod'].'.php' : 'index.php';

	// Si l'actionMod existe, on l'exécute
	if (is_file($module.$actionMod)) {

		include $module.$actionMod;

	// Sinon, on affiche la page d'accueil !
	} else {
		include 'global/accueil.php';
	}

// Module non specifié ou invalide ? On affiche la page d'accueil !
} else {

	include 'global/accueil.php';
}

// Fin de la tamporisation de sortie
$contenu = ob_get_clean();

// Début du code HTML
include 'global/haut.php';

echo $contenu;

// Fin du code HTML
include 'global/bas.php';
