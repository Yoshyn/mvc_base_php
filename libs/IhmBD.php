<?php
require_once 'PDOMySQL.php';
require_once 'Tableau.php';
require_once 'Formulaire.php';

// Classe g�n�rique pour acc�der � une table.
// Fonctionne quelle que soit la table, quel que soit le SGBD
// Peut �tre sp�cialis�e pour surcharger certaines m�thodes (voir IhmCarte).

class IhmBD
{
	// ----   Partie priv�e : les constantes et les variables
	const INS_BD = 1;
	const MAJ_BD = 2;
	const DEL_BD = 3;
	const EDITER = 4;
	const CHANGE_ORDER = 5;

	protected $bd, $maPage, $urlType, $nomTable, $schemaTable, $entetes;

	// Le constructeur
	function __construct ($nomTable, $bd, $page="", $urlType="?")
	{
		$this->bd = $bd;
		$this->nomTable = $nomTable;
		if (empty($page))
		$this->maPage = $_SERVER['PHP_SELF'];
		else
		$this->maPage = $page;
		$this->urlType = $urlType;
		$this->schemaTable = $bd->schemaTable($nomTable);
		foreach ($this->schemaTable as $nom => $options)
		$this->entetes[$nom] = $nom;
	}

	private function accesCle($tuple, $format="url")
	{
		$separateur = $chaine = "";
		foreach ($this->schemaTable as $nom => $options)
		{
			if ($options['cle_primaire'])
			{
				if ($format=="url")
				{
					$chaine .= "$separateur$nom=" .
					urlEncode($tuple[$nom]);
					$separateur = "&";
				}
				else
				{
					$chaine .= "$separateur$nom='" .
					addSlashes($tuple[$nom]) . "'";
					$separateur = " AND ";
				}
			}
		}
		return $chaine;
	}

	protected function controle($ligne)
	{
		//$lignePropre = array();
		// On commence par traiter toutes les cha�nes des attributs
		foreach ($this->schemaTable as $nom => $options)
		{
			$isOK = false;
			switch ($options['type'])
			{
				case "string":
					$isOK = is_string($ligne[$nom])?1:0;
					break;
				case "int":
					$isOK = is_numeric($ligne[$nom])?1:0;
					break;
				default:
					$isOK = true;
			}
			if(!$isOK) return $isOK;
		}
		return $isOK;
	}

	/*****************   Partie publique ********************/

	// Cr�ation d'un formulaire g�n�rique
	public function formulaire ($action, $ligne)
	{
		$form = new Formulaire ("post", $this->maPage, false);
		$form->debutTable();
		foreach ($this->schemaTable as $nom => $options)
		{
			if (!isSet($ligne[$nom])) $ligne[$nom] = "";
			$ligne[$nom] = htmlSpecialChars($ligne[$nom]);

			if($options['Extra'] and $action == IhmBD::INS_BD)
			{
				$requete = "SELECT MAX($nom) as newID FROM $this->nomTable WHERE $nom";
				$resultat = $this->bd->execRequete ($requete);
				$newID = $this->bd->ligneSuivante ($resultat);
				$form->champCache ($nom, $newID["newID"]+1);
				$resultat->closeCursor();
			}
			else if ($options['cle_primaire'] and $action == IhmBD::MAJ_BD)
			{
				$form->champCache ($nom, $ligne[$nom]);
			}
			else
			{
				if ($options['type'] == "text")
				$form->champfenetre ($this->entetes[$nom],$nom, $ligne[$nom],4, 30);
				else
				$form->champTexte ($this->entetes[$nom],$nom, $ligne[$nom],$options['longueur']);
			}
		}
		$form->champCache ("action", $action);
		$form->finTable();
		if ($action == IhmBD::MAJ_BD)
		$form->champValider ("Modifier", "submit");
		else
		$form->champValider ("Inserer", "submit");

		return $form->formulaireHTML();
	}

	// Fonction d'insertion d'une ligne. A faire: v�rifier
	// que la ligne n'existe pas d�j�!
	public function insertion ($ligne)
	{
		// Initisalisations
		$noms = $valeurs = $virgule = "";

		if (!$this->controle ($ligne))
		throw new Exception("Format invalide");

		// Parcours des attributs pour cr�er la requ�te
		foreach ($this->schemaTable as $nom => $options)
		{
			// Liste des noms d'attributs + liste des valeurs
			$noms .= $virgule . $nom;
			$valeurs .= $virgule . "'" . $ligne[$nom] . "'";
			// A partir de la seconde fois, on s�pare par des virgules
			$virgule= ",";
		}
		$requete = "INSERT INTO $this->nomTable($noms) VALUES ($valeurs) ";
		$this->bd->execRequete ($requete);
	}

	// Fonction de mise � jour  d'une ligne
	public function maj ($ligne)
	{
		// Initisalisations
		$listeAffectations = $virgule = "";

		if (!$this->controle ($ligne))
		throw new Exception("Format invalide");

		// Parcours des attributs pour cr�er la requ�te
		foreach ($this->schemaTable as $nom => $options)
		{
			// Cr�ation de la clause WHERE
			$clauseWhere = $this->accesCle($ligne, "SQL");
			// Cr�ation des affectations nom='valeur'
			if (!$options['cle_primaire'])
			{
				$listeAffectations .= $virgule . "$nom='" . $ligne[$nom] . "'";
				// A partir du second, on s�pare par des virgules
				$virgule= ",";
			}
		}
		$requete = "UPDATE $this->nomTable SET $listeAffectations "
		. "WHERE $clauseWhere";
		$this->bd->execRequete ($requete);
	}
	public function del ($ligne)
	{
		$clauseWhere = $this->accesCle($ligne, "SQL");
		$requete = "DELETE FROM $this->nomTable WHERE $clauseWhere";
		$this->bd->execRequete ($requete);
	}

	// Cr�ation d'un tableau g�n�rique
	public function tableau($attributs=array(),$orderby="")
	{
		// Cr�ation de l'objet tableau
		$tableau = new Tableau(2, $attributs);
		$tableau->setCouleurImpaire("silver");
		$tableau->setAfficheEntete(1, false);

		// Texte des ent�tes
		foreach ($this->schemaTable as $nom => $options)
		$tableau->ajoutEntete(2, $nom, $this->entetes[$nom]);

		// Parcours de la table

		$requete = "SELECT * FROM $this->nomTable";
		if(!empty($orderby))
		$requete .= " ORDER BY  $orderby";

		$resultat = $this->bd->execRequete ($requete);

		$i=0;
		while ($ligne = $this->bd->ligneSuivante ($resultat)) {
			$i++;
			// Cr�ation des cellules
			foreach ($this->schemaTable as $nom => $options)  {
				// Attention: traitement des balises HTML avant affichage
				$ligne[$nom] = htmlSpecialChars($ligne[$nom]);
				$tableau->ajoutValeur($i, $nom, $ligne[$nom]);
			}

			// Cr�ation de l'URL de modification
			$urlMod = $this->accesCle($ligne) . "&amp;action=" . IhmBD::EDITER;
			$urlSupp = $this->accesCle($ligne) . "&amp;action=" . IhmBD::DEL_BD;
			$modLink = "<a href='$this->maPage$this->urlType$urlMod'>modifier</a>";
			$modSupp = "<a href='$this->maPage$this->urlType$urlSupp'>supprimer</a>";

			$tableau->ajoutValeur($i, "Modifier", $modLink);
			$tableau->ajoutValeur($i, "Supprimer", $modSupp);
		}

		// Retour de la cha�ne contenant le tableau
		return $tableau->tableauHTML();
	}

	// M�thode permettant d'affecter un ent�te � un attribut
	public function setEntete($nomAttr, $texte)
	{
		$this->entetes[$nomAttr] = $texte;
	}


	// Fonction recherchant une ligne d'apr�s sa cl�
	public function chercheLigne($params, $format="tableau")
	{
		// On constitue la clause WHERE
		$clauseWhere = $this->accesCle ($params, "SQL");

		// Cr�ation et ex�cution de la requ�te SQL
		$requete = "SELECT * FROM $this->nomTable WHERE $clauseWhere";
		$resultat = $this->bd->execRequete($requete);
		if ($format == "tableau")
		return $this->bd->ligneSuivante($resultat);
		else
		return $this->bd->objetSuivant($resultat);
	}


	public function genererIHM ($paramsHTTP)
	{
		if (isSet($paramsHTTP['action']))
		$action = $paramsHTTP['action'];
		else
		$action = "";
		$affichage = "";
		switch ($action) {
			case IhmBD::INS_BD:
				try
				{
					$this->insertion($paramsHTTP);
					$affichage .= "<I>Insertion effectu�e.</I>";
				}
				catch (Exception $exc)
				{
					$affichage .="<I>Insertion impossible : "
					. $exc->getCode() . " ----- " . $exc->getMessage() . "\n</I>";
				}
				break;

			case IhmBD::MAJ_BD:
				try
				{
					$this->maj($paramsHTTP);
					$affichage .= "<I>Mise � jour effectu�e.</I>";
				}
				catch (Exception $exc)
				{
					$affichage .="<I>Mise � jour impossible : "
					. $exc->getCode() . " ----- " . $exc->getMessage() . "\n</I>";
				}
				break;

			case IhmBD::DEL_BD:
				try
				{
					$this->del($paramsHTTP);
					$affichage .= "<I>Suppression effectu�e.</I>";
				}
				catch (Exception $exc)
				{
					$affichage .="<I>Suppression impossible : "
					. $exc->getCode() . " ----- " . $exc->getMessage() . "\n</I>";
				}
				break;

			case IhmBD::EDITER:
				// On a demand� l'acc�s � une ligne en mise � jour
				$ligne  = $this->chercheLigne ($paramsHTTP);
				$affichage .= $this->formulaire(IhmBD::MAJ_BD,$ligne);
				break;
			case IhmBD::CHANGE_ORDER:
				// On a demand� a changer l'ordre d'afficahge
				$ordre  = $paramsHTTP['orderby'];
				break;
		}
		// Affichage du formulaire en insertion si on n'a pas �dit�
		// en mise � jour
		if ($action != IhmBD::EDITER) {
			$affichage .= "<h2>Saisie</h2>";
			$affichage .= $this->formulaire(IhmBD::INS_BD, array());
		}


		$affichage .= "<h2>Contenu de la table <I>$this->nomTable</I></h2>";

		$formOrderBy1 = new Formulaire ("post", $this->maPage, false);
		$formOrderBy1->debutTable();
		$formOrderBy1->champCache ("action", 5);
		foreach ($this->schemaTable as $nom => $options)
		{$tab[] = $nom;}
		$formOrderBy1->champListe("Ranger par ordre :","orderby","",1,array_combine($tab, $tab));
		$formOrderBy1->finTable();
		$formOrderBy1->champValider ("Trier", "submit");
		$affichage .= $formOrderBy1->formulaireHTML();
		$affichage .= $this->tableau(array("border" => 2),(!empty($ordre))? $ordre : "");

		return $affichage;
	}
}
?>