<?php
// Classe gérant les formulaires

// On a besoin d'instancier des objets Tableau
require_once ("Tableau.php");

// Début de la classe
class Formulaire
{
	// ----   Partie privée : les propriétés et les constantes
	const VERTICAL = 1;
	const HORIZONTAL = 2;

	// Propriétés de la balise <form>
	private $methode, $action, $nom, $transfertFichier=FALSE;

	// Propriétés de présentation
	private  $orientation="", $centre=TRUE, $classeCSS, $tableau ;

	// Propriétés stockant les composants du formulaire
	private $composants=array(), $nbComposants=0;

	// Constructeur de la classe
	function __construct  ($methode, $action, $centre=true, $classe="Form", $nom="Form")
	{
		// Initisalition des propriétés de l'objet avec les paramètres
		$this->methode = $methode;
		$this->action = $action;
		$this->classeCSS = $classe;
		$this->nom = $nom;
		$this->centre = $centre;
	}

	// ----   Partie privée : les méthodes

	// Méthode pour créer un champ INPUT général
	private function champINPUT ($type, $nom, $val, $taille, $tailleMax)
	{
		// Attention aux problèmes d'affichage
		$val = htmlSpecialChars($val);

		// Création et renvoi de la chaîne de caractères
		return "<input type='$type' name=\"$nom\" "
		. "value=\"$val\" size='$taille' maxlength='$tailleMax'/>\n";
	}

	// Champ de type texte
	private function champTEXTAREA ($pNom, $pVal, $pLig, $pCol)
	{
		return "<textarea name=\"$pNom\" rows='$pLig' "
		. "COLS='$pCol'>$pVal</textarea>\n";
	}

	// Champ pour sélectionner dans une liste
	private function  champSELECT ($nom, $liste, $defaut, $taille=1)
	{
		$s = "<select name=\"$nom\" size='$taille'>\n";
		while (list ($val, $libelle) = each ($liste)) {
			// Attention aux problèmes d'affichage
			$val = htmlSpecialChars($val);
			$defaut = htmlSpecialChars($defaut);

			if ($val != $defaut)
			$s .=  "<option value=\"$val\">$libelle</option>\n";
			else
			$s .= "<option value=\"$val\" selected='1'>$libelle</option>\n";
		}
		return $s . "</select>\n";
	}

	// Champ CHECKBOX ou RADIO
	private function  champBUTTONS ($pType, $pNom, $pListe, $pDefaut, $params)
	{
		if ($pType == "checkbox") $length = $params["length"];
		else $length = -1;

		// Toujours afficher dans une table
		$libelles=$champs="";
		$nbChoix = 0;
		$result = "<table border='0' cellspacing='5' cellpadding='2'><tr>\n";
		while (list ($val, $libelle) = each ($pListe))
		{
			$libelles .= "<td><b>$libelle</b></td>";
			$checked = " ";
			if (!is_array($pDefaut))
			{
				if ($val == $pDefaut) $checked = "checked='1'";
			}
			else
			{
				if (isSet($pDefaut[$val])) $checked = "checked='1'";
			}

			$champs .= "<td><input type='$pType' name=\"$pNom\" value=\"$val\" "
			. " $checked/> </td>\n";
			$nbChoix++;

			// Eventuellement on place plusieurs lignes dans la table
			if ($pType == "checkbox" and $length == $nbChoix)
			{
				$result .= "<tr>" . $libelles . "</tr><tr>" . $champs . "</tr>\n";
				$libelles = $champs = "";
				$nbChoix = 0;
			}
		}

		if (!empty($champs))
		return  $result . $libelles .  "</tr>\n<tr>" . $champs
		. "</tr></table>";
		else return $result . "</table>";
	}

	// Champ de formulaire
	private function champForm ($type, $nom, $val, $params, $liste=array())
	{
		switch ($type)
		{
			case "text": case "password": case "submit": case "reset":
			case "file": case "hidden":
				// Extraction des paramètres de la liste
				if (isSet($params['SIZE']))
				$taille = $params["SIZE"];
				else  $taille = 0;
				if (isSet($params['MAXLENGTH']) and $params['MAXLENGTH']!=0)
				$tailleMax = $params['MAXLENGTH'];
				else $tailleMax = $taille;

				// Appel de la méthode champINPUT
				$champ = $this->champINPUT ($type, $nom, $val, $taille, $tailleMax);
				// Si c'est un transfert de fichier: s'en souvenir
				if ($type == "file") $this->transfertFichier=TRUE;
				break;

			case "textarea":
				$lig = $params["ROWS"]; $col = $params["COLS"];
				// Appel de la méthode champTEXTAREA de l'objet courant
				$champ = $this->champTEXTAREA ($nom, $val, $lig, $col);
				break;

			case "select":
				$taille = $params["SIZE"];
				// Appel de la méthode champSELECT de l'objet courant
				$champ = $this->champSelect ($nom, $liste, $val, $taille);
				break;

			case "checkbox":
				$champ = $this->champBUTTONS ($type, $nom, $liste, $val, $params);
				break;

			case "radio":
				// Appel de la méthode champBUTTONS de l'objet courant
				$champ = $this->champBUTTONS ($type, $nom, $liste, $val, array());
				break;

			default: echo "<b>ERREUR: $type est un type inconnu</b>\n";
			break;
		}
		return $champ;
	}

	// Création d'un champ avec son libellé
	private function champLibelle ($libelle, $nom, $val,  $type,
	$params=array(),  $liste=array())
	{
		// Création de la balise HTML
		$champHTML = $this->champForm ($type, $nom, $val, $params, $liste);

		// On met le libellé en gras
		$libelle = "<b>$libelle</b>";

		// Stockage du libellé et de la balise dans le contenu
		$this->composants[$this->nbComposants] = array("type" => "CHAMP",
						   "libelle" => $libelle,
						   "champ" => $champHTML);

		// Renvoi de l'identifiant de la ligne, et incrémentation
		return $this->nbComposants++;
	}

	/* **************** METHODES PUBLIQUES ********************/
	// Méthode permettant de récupérer un champ par son identifiant
	public function getChamp($idComposant)
	{
		// On récupère le composant, on extrait le champ. Manque les tests...
		$composant = $this->composants[$idComposant];
		return $composant['champ'];
	}

	// Création d'un champ et de son libellé:
	// appel de la méthode générale, avec juste les paramètres nécessaires
	public function champTexte ($libelle, $nom, $val, $taille, $tailleMax=0)
	{
		return $this->champLibelle ($libelle, $nom, $val, "text", array ("SIZE"=>$taille,
					"MAXLENGTH"=>$tailleMax));
	}

	public function champMotDePasse ($pLibelle, $pNom, $pVal, $pTaille, $pTailleMax=0)
	{
		return $this->champLibelle ($pLibelle, $pNom, $pVal, "password",
		array ("SIZE"=>$pTaille, "MAXLENGTH"=>$pTailleMax));
	}

	public function champRadio ($libelle, $nom, $val, $liste)
	{
		return $this->champLibelle ($libelle, $nom, $val, "radio", array (), $liste);
	}

	public function champCheckBox ($pLibelle, $pNom, $pVal, $pListe, $length=-1)
	{
		return $this->champLibelle ($pLibelle, $pNom, $pVal, "checkbox",
		array ("LENGTH"=>$length), $pListe);
	}

	public function champListe ($pLibelle, $pNom, $pVal, $pTaille, $pListe)
	{
		return $this->champLibelle ($pLibelle, $pNom, $pVal, "select",
		array("SIZE"=>$pTaille), $pListe);
	}

	public function champFenetre ($libelle, $nom, $val, $lig, $col)
	{
		return $this->champLibelle ($libelle, $nom, $val, "textarea",
		array ("ROWS"=>$lig,"COLS"=>$col));
	}

	public function champValider ($pLibelle, $pNom)
	{
		return $this->champLibelle ("&nbsp;", $pNom, $pLibelle, "submit");
	}

	public function champAnnuler ($pLibelle, $pNom)
	{
		$this->champLibelle ("&nbsp;", $pNom, $pLibelle, "reset");
	}

	public function champFichier ($pLibelle, $pNom, $pTaille)
	{
		$this->champLibelle ($pLibelle, $pNom, "", "file",
		array ("SIZE"=>$pTaille));
	}

	public function champCache ($nom, $valeur)
	{
		return $this->champLibelle ("", $nom, $valeur, "hidden");
	}

	// Ajout d'un texte quelconque
	public function ajoutTexte ($texte)
	{
		// On ajoute un élément dans le tableau $composants
		$this->composants[$this->nbComposants] = array("type"=>"TEXTE",
						   "texte" => $texte);
		// Renvoi de l'identifiant de la ligne, et incrémentation
		return $this->nbComposants++;
	}

	// Début d'une table, mode horizontal ou vertical
	public function debutTable ($orientation=Formulaire::VERTICAL,
	$attributs=array(), $nbLignes=1)
	{
		// On instancie un objet pour créer ce tableau HTML
		$tableau = new Tableau (2, $attributs);

		// Jamais d'affichage de l'entête des lignes
		$tableau->setAfficheEntete (1, FALSE);

		// Action selon l'orientation
		if ($orientation == Formulaire::HORIZONTAL)
		{
			$tableau->setRepetitionLigne (1, "ligne", $nbLignes);
		}
		else // Pas d'affichage non plus de l'entête des colonnes
		$tableau->setAfficheEntete (2, FALSE);

		// On crée un composant dans lequel on place le tableau
		$this->composants[$this->nbComposants] =
		array("type"=>"DEBUTTABLE",
	    "orientation"=> $orientation,
	    "tableau"=> $tableau);

		// Renvoi de l'identifiant de la ligne, et incrémentation
		return $this->nbComposants++;
	}

	// Fin d'une table
	public function finTable ()
	{
		// Insertion d'une ligne marquant la fin de la table
		$this->composants[$this->nbComposants++] = array("type"=>"FINTABLE");
	}

	// Fin du formulaire, avec affichage éventuel
	public function formulaireHTML ()
	{
		// On met un attribut ENCTYPE si on transfère un fichier
		if ($this->transfertFichier) $encType = "enctype='multipart/form-data'";
		else                         $encType="";

		$formulaire = "";
		// Maintenant, on parcourt les composants et on crée le HTML
		foreach ($this->composants as $idComposant => $description)
		{
			// Agissons selon le type de la ligne
			switch ($description["type"])
			{
				case "CHAMP":
					// C'est un champ de formulaire
					$libelle = $description['libelle'];
					$champ = $description['champ'];
					if ($this->orientation == Formulaire::VERTICAL)
					{
						$this->tableau->ajoutValeur($idComposant, "libelle", $libelle);
						$this->tableau->ajoutValeur($idComposant, "champ", $champ);
					}
					else if ($this->orientation == Formulaire::HORIZONTAL)
					{
						$this->tableau->ajoutEntete(2, $idComposant, $libelle);
						$this->tableau->ajoutValeur("ligne", $idComposant, $champ);
					}
					else
					$formulaire .= $libelle . $champ;
					break;

				case "TEXTE":
					// C'est un texte simple à insérer
					$formulaire .= $description['texte'];
					break;
					 
				case "DEBUTTABLE":
					// C'est le début d'un tableau HTML
					$this->orientation = $description['orientation'];
					$this->tableau = $description['tableau'];
					break;
					 
				case "FINTABLE":
					// C'est la fin d'un tableau HTML
					$formulaire .= $this->tableau->tableauHTML();
					$this->orientation="";
					break;

				default: // Ne devrait jamais arriver...
					echo "<p>ERREUR CLASSE FORMULAIRE!!<p>";
			}
		}

		// Encadrement du formulaire par les balises
		$formulaire = "\n<form  method='$this->methode' " . $encType
		. "action='$this->action' name='$this->nom'>"
		. $formulaire . "</form>";
		// Il faut éventuellement le centrer
		if ($this->centre) $formulaire = "<center>$formulaire</center>\n";;

		// On retourne la chaîne de caractères contenant le formulaire
		return $formulaire;
	}

	// Fin de la classe
}
?>