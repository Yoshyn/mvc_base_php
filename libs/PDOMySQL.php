<?php

class PDOMySQL extends PDO
{
	public function __construct($dsn, $user=NULL, $password=NULL)
	{
		parent::__construct($dsn, $user, $password);
		$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	public function execRequete ($requete)
	{
		$result = parent::query($requete);
		return $result;
	}

	public function objetSuivant ($resultat)
	{
		return $resultat->fetch(PDO::FETCH_OBJ);
	}

	public function ligneSuivante ($resultat)
	{
		return $resultat->fetch(PDO::FETCH_ASSOC);
	}

	function SchemaTable ($tableName) {
		$recordset = parent::query("DESCRIBE $tableName");
		$fields = $recordset->fetchAll(PDO::FETCH_ASSOC);
		foreach ($fields as $field) {
			$nom = $field['Field'];
			$feature = explode("(",$field['Type']);
			switch ($feature[0])
			{
				case "varchar":
					$longueur = implode(explode(")", $feature[1]));
					$type = "string";
					break;
				case "enum":
					$enum_part = explode(",", implode(explode(")", $feature[1])));
					$longueur = 0;
					for ($i=0; $i<sizeof($enum_part); $i++)
					$longueur = ($longueur < strlen($enum_part[$i])) ? strlen($enum_part[$i])-2 : $longueur;
					$type = "string";
					break;
				case "int":
					$longueur = implode(explode(")", $feature[1]));
					$type = $feature[0];
					break;
				case "text":
					$longueur =65535;
					$type= $feature[0];
					break;
				case "date":
					$longueur = 10;
					$type = $feature[0];
					break;
				case "time":
					$longueur = 8;
					$type = $feature[0];
					break;
				case "decimal":
					$decimal_part = explode(",", implode(explode(")", $feature[1])));
					$longueur = 0;
					for ($i=0; $i<sizeof($decimal_part); $i++)
					$longueur = $longueur+$decimal_part[$i];
					$type = "real";
					break;
				default:
					$longueur = (empty($feature[1])) ? 40 : implode(explode(")", $feature[1]));
					$type = $feature[0];
			}
			$schema[$nom]['longueur'] = $longueur;
			$schema[$nom]['type'] = $type;
			$schema[$nom]['cle_primaire'] = ($field['Key'] == "PRI") ? 1 : 0;
			$schema[$nom]['notNull'] = ($field['Null'] == "NO") ? 1 : 0;
			$schema[$nom]['Extra'] = ($field['Extra'] == "auto_increment") ? 1 : 0;
		}
		return $schema;
	}
}

class PDOMySqlSingleton extends PDOMySQL {

	private static $_instance;

	public function __construct( ) {}
	/* Singleton */
	public static function getInstance() {

		if (!isset(self::$_instance)) {
			try {
				self::$_instance = new PDOMySQL(SQL_DSN, SQL_USERNAME, SQL_PASSWORD);
					
			} catch (PDOException $e) {
				echo $e;
			}
		}
		return self::$_instance;
	}
	// End of PDO2::getInstance() */
}



?>
