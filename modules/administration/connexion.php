<?php

include CHEMIN_LIBS.'Formulaire.php';

function combinaison_connexion_valide($nom_administrateur, $mot_de_passe) {

	$pdo = PDOMySqlSingleton::getInstance();
	$requete = $pdo->query("SELECT id FROM administrateurs
		WHERE nom_administrateur = '$nom_administrateur' AND
		mot_de_passe = '$mot_de_passe'");
	if ($result = $requete->fetch(PDO::FETCH_ASSOC))
	{
		$requete->closeCursor();
		return $result['id'];
	}
	return false;
}

if (administrateur_connecte())
{
	include CHEMIN_VUE.'erreur_deja_connect.php';
}
else
{
	if(empty($_REQUEST['login']) || empty($_REQUEST['password']) )
	{
		$form_connexion = new Formulaire ("post", $_SERVER['REQUEST_URI']);
		$form_connexion->debutTable();
		$form_connexion->champTexte("Login : ","login","admin",32);
		$form_connexion->champMotDePasse("Password : ","password","root",40);
		$form_connexion->finTable();
		$form_connexion->champValider ("Connexion", "connexion");
		include CHEMIN_VUE.'form_connexion.php';
	}
	else
	{
		$nom_administrateur = $_REQUEST['login'];
		$mot_de_passe = $_REQUEST['password'];
		$id_administrateur = combinaison_connexion_valide($nom_administrateur, $mot_de_passe);
		if ($id_administrateur) {
			$_SESSION['id'] = $id_administrateur;
			$_SESSION['pseudo'] = $nom_administrateur;
			include CHEMIN_VUE.'connexion_ok.php';

		}
		else
		{
			include CHEMIN_VUE.'connexion_erreur.php';
		}
	}
}
