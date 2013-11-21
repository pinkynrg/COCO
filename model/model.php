<?php

	if (!class_exists("DB")) 		require("model/db.php");
	if (!class_exists("smartCSV")) 	require("model/smartCSV.php");
	if (!class_exists("error")) 	require("model/alert.php");
	if (!class_exists("PHPExcel")) 	require("view/PHPExcel/PHPExcel.php");	

	class Model {

		function __construct($controller) {
			$this->controller = $controller;
			$this->db = new DB();
			$this->csvHelper = new smartCSV();
		}

		function dropTable($name) {
			$this->db->setQuery("drop table `".$name."`;");
			return $this->db->query();
		}

		function emptyTable($name) {	
			$this->db->setQuery("delete from `".$name."`;");
			return $this->db->query();
		}

		function createTable($name, $header, $pk) {

			$query = " CREATE  TABLE `".$name."` (";
			if ($pk == -1) 
				$query .= "`id` INT NOT NULL AUTO_INCREMENT,";
			for ($i = 0; $i < count($header); $i++)
				$query .= " `".$header[$i]."` VARCHAR(200) NULL,";
	
			if ($pk == -1) 
				$query .= "PRIMARY KEY (`id`) );";
			else
				$query .= "PRIMARY KEY (`".$header[$pk]."`) );";

			$this->db->setQuery($query);

			return $this->db->query();
		}

		function insertIntoTable($name, $where, $content) {

			$result = true;

			$query_constant = "INSERT INTO `".$name."` (";
			
			for ($i=0; $i<count($where); $i++) {
				$query_constant .= "`".$where[$i]."`";
				if ($i<count($where)-1)
					$query_constant .= ",";
				else $query_constant .= ") VALUES (";
			}

			for ($i=0; $i<count($content); $i++) {
				$query = $query_constant;
				for ($k=0; $k<count($content[$i]); $k++) {
					$query .= "'".addslashes($content[$i][$k])."'";
					if ($k<count($content[$i])-1) 
						$query .= ",";
					else $query .= ");";
				}

				$this->db->setQuery($query);
				
				if ($result) $result = $this->db->query();
			}

			return $result;
		}

		function checkAuth() {

			$username = $_POST['username'];
			$password = $_POST['password'];

			if (isset($username)&&(isset($password))) {
				if (($username!="")&&($password!="")) {

					$conn = new PDO('mysql:dbname='.info::$DB_SCHEMA.';host='.info::$DB_HOST.';charset=utf8', info::$DB_USER, info::$DB_PASS);
					$conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
					$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

					$query = $conn->prepare("SELECT * FROM `users` WHERE `username` = :username AND `password` = :password");

					$query->bindParam(':username', $_POST['username']);
					$query->bindParam(':password', $_POST['password']);

					$query->execute();

					$query_result = $query->fetch();

					if ($query_result['username'] != "") {
						$this->setupSession($query_result['username'], $query_result['name'], $query_result['lastname'], $query_result['access_level']);
						$result = new alert(1,"Accesso effettuato con successo.");
					} 
					else $result = new alert(0,"Nome utente e / o password errato.");
				}
				else
					$result = new alert(2,"Campi vuoti non validi.");
			}
			else
				$result = new alert(2,"Campi nulli non validi.");;

			return $result;
		}

		function setupSession($username, $firstname, $lastname, $access_level) {
			$_SESSION['username']  = $username;
			$_SESSION['firstname'] = $firstname;
			$_SESSION['lastname']  = $lastname;
			$_SESSION['access_level'] = $access_level;
		}

		function getSession() {
			return isset($_SESSION['username']);
		}

		function getFile($types = array(), $extentions = array(), $size = 1048576) {
			
			if (is_array($types) && is_int($size)) {
			
				if ($_FILES["file"]["type"] != "") {
					
					$temp = explode(".", $_FILES["file"]["name"]);
					$extension = end($temp);
					$valid_type = false;
					
					foreach ($types as $type) {
						if (!$valid_type) 
							$valid_type = ($_FILES["file"]["type"] == $type) ? true : false;
					}

					if ($valid_type) {
						
						if ($_FILES["file"]["size"] <= $size) {
						
							if (!$_FILES["file"]["error"]) {
								
								$this->file = $_FILES["file"]["tmp_name"];

								$result = new alert(1, "L'upload del file &egrave; avvenuto con sucesso.");

							} else $result = new alert(0, "Si &egrave; manifestato il seguente errore: ".$_FILES["file"]["error"]);
						
						} else $result = new alert(2, "Il file &egrave; superiore ad un ".($size/1024)."Kb di larghezza. Upload non consentito.");
					
					} else $result = new alert(0, "I file di questo tipologia non sono accettati. Solo ".implode(", ", $extentions )." ammessi.");
				
				} else $result = new alert(2, "Selezionare un file dal proprio computer prima di effettuare un upload.");
			
			} else $result = new alert(0, "il setup dei types, delle extentions, o della size non &egrave; valido.");
			
			return $result;
		}

		function CSVtoSql($link, $separator, $name, $pk, $drop = false, $cols = null, $header = null) {

			$content = $this->csvHelper->CSVtoArray($link, $separator);
			$passed = true;

			if ($content) {
				if (is_null($header)) {
					$header = $content[0];
					unset($content[0]);
					$content = array_values($content);
				}

				if ((isset($content[0])) && (count($header)==count($content[0]))) {
					
					if (!(isset($cols) && $cols != count($header))) {
						
						if ($drop) {
							$this->dropTable($name); 
							$result = $this->createTable($name, $header, $pk);
						}

						if (($drop && $result->type) || (!$drop)) {
							$result = $this->insertIntoTable($name, $header, $content);
						}
						
						else $result = new alert(0, "Problemi con il drop ed il create della nuova tabella.");
				
					} else $result = new alert(0, "Il numero di colonne in questo file non &egrave; conforme. Selezionare un file con ".$cols." colonne. Il tuo file &egrave; ".count($content[0])." colonne.");
			
				} else $result = new alert(0, "Problemi con il set dell'header del CSV o il file CSV &egrave; privo di contenuti.");
			
			} else $result = new alert(0,"Problemi con la conversione del file CSV.");

			return $result;
		}

		function backup($tables = '*'){
			
			$this->db->thin =true;

			$return = "";

			//get all of the tables
			if($tables == '*') {
				$tables = array();
				$this->db->setQuery('SHOW TABLES');
				$this->db->query(false);
				$tables = $this->db->result;
			}
			else {
				$tables = is_array($tables) ? $tables : explode(',',$tables);
			}
			
			//cycle through
			foreach($tables as $table) {
				$return .= "DROP TABLE IF EXISTS `".$table."`;\n\n";
				$this->db->setQuery("SHOW CREATE TABLE ".$table);
				$this->db->query(false);
				$return .= $this->db->result[0][1].";\n";

				$this->db->setQuery("SELECT * FROM ".$table);
				$this->db->query(false);

				$result = $this->db->result;
				$num_fields = isset($this->db->result[0]) ? count($this->db->result[0]) : 0;

				$return .= "\n\nLOCK TABLES `".$table."` WRITE;\n\n";

				foreach($result as $row) {
					
					$return .= "INSERT INTO ".$table." VALUES(";

					for ($i=0; $i<$num_fields; $i++) {
						$row[$i] = str_replace("\n","\\n",addslashes($row[$i]));
						$return .= (isset($row[$i]) && ($row[$i] != '')) ? "'".$row[$i]."'" : "NULL";
						$return .= ($i < ($num_fields-1)) ? "," : "";
					}

					$return .= ");\n\n";
				}

				$return.="UNLOCK TABLES;\n\n\n";

				$this->db->thin =false;

			}
			
			//save file
			$handle = @fopen('backups/db-backup-'.info::$ALIAS.'-'.date("d.m.Y-H.i.s").'.sql','wb');
			
			if ($handle) {
				$result = @fwrite($handle,$return);
				if ($result) {
					$result = @fclose($handle);
					if ($result) {
					
						$result = new alert(1,"Backup effettuato con successo.");
				
					} else $result = new alert(0,"Qualcosa &egrave; andato storto durante la scittura del file.");
			
				} else $result = new alert(0,"Qualcosa &egrave; andato storto durante la scittura del file.");
		
			} else $result = new alert(0,"Non ci sono i permessi necessari di scrittura sulla cartella backups.");
		
			return $result;
		}

		function restoreBackup() {
			if (isset($_POST['backup'])) {
				$path = "backups/".$_POST['backup'];
				$query = file_get_contents($path);
				$this->db->setQuery($query);
				$result = $this->db->multiquery();
				if ($result->type)
					$result = new alert(1,"Restore dei dati effettuato con successo.");
			}
			else {
				$result = new alert(0,"Non &egrave; stato potuto effettuare il restore dei dati.");
			}

			return $result;
		}

		function scanDirectory($dir, $show_all = false) {
			$scan = scandir($dir);
			$accepted = array("directory","text/x-php","text/html","text/plain","inode/x-empty");
			$black_list = array(".","..","download_excel.php","home.php","menu.json","how_to_csv.php");

			foreach($scan as $key => $node) {
				if (!in_array(finfo_file(finfo_open(FILEINFO_MIME_TYPE),$dir."/".$node), $accepted)) {
					unset($scan[$key]);
				}
				else if (in_array($node, $black_list))
					unset($scan[$key]);
				else if ($show_all){
					$scan[$key] = $dir."/".$scan[$key];
				}
			}

			array_values($scan);

			return $scan;
		}

		function pullMenu($root) {
			$counter = 0;
			$scan = $this->scanDirectory($root);

			if (file_exists($root."/menu.json")) {
				$old_map = file_get_contents($root."/menu.json");
				$old_map = json_decode($old_map);
			}
			else 
				$old_map = new stdClass();

			$map = new stdClass();
			
			foreach($scan as $node) {

				$map->{$node} = new stdClass();
				$map->{$node}->url = $root."/".$node;
				$map->{$node}->label = isset($old_map->{$node}->label) ? $old_map->{$node}->label : $node;
				$map->{$node}->alias = isset($old_map->{$node}->alias) ? $old_map->{$node}->alias : $node;
				$map->{$node}->type  = finfo_file(finfo_open(FILEINFO_MIME_TYPE),$root."/".$node);
				$map->{$node}->order = isset($old_map->{$node}->order) ? $old_map->{$node}->order : $counter;
				$map->{$node}->perms = isset($old_map->{$node}->perms) ? $old_map->{$node}->perms : "10000";

				$counter++;
			}
			
			$handle = @fopen($root."/menu.json","wb");
				
			if ($handle)
				$result = fwrite($handle, json_encode($map));
			else 
				$result = new alert(0,"Ci sono stati problemi a creare i file menu.json.");


			foreach($scan as $node) {
				if (is_dir($root."/".$node)) {
					$this->pullMenu($root."/".$node);
				}
			}

			return new alert(1,"L'upload dei contenuti &egrave; avvenuto con successo.");
		}


		function getMenu($root, &$all = array()) {
			
			if ($root == info::$CONTENT_ROOT) {
				$json = json_decode(file_get_contents(info::$CONTENT_ROOT."/menu.json"));
				foreach ($json as $key => $elem) {
					$all[info::$CONTENT_ROOT."/".$key] = $elem;
				}
			}
			
			$content = $this->scanDirectory($root, true);
			
			foreach ($content as $node) {
				if (is_dir($node) && (file_exists($node."/menu.json"))) {
					$json = json_decode(file_get_contents($node."/menu.json"));
					foreach ($json as $key => $elem) {
						$all[$node."/".$key] = $elem;
					}
					$this->getMenu($node, $all);
				}
 			}
			return $all;
		}

		function updateMenu() {
			$counter = count($_POST)/5;
			for($i=0; $i<$counter; $i++) {

				$path = $_POST["path_".$i];
				$exploded = explode("/", $path);
				unset($exploded[count($exploded)-1]);
				$exploded = array_values($exploded);
				$path = implode("/",$exploded);

				$file = $_POST["path_".$i];
				$exploded = explode("/", $file);
				$file = $exploded[count($exploded)-1];

				$nodes = json_decode(file_get_contents($path."/menu.json"));

				$nodes->{$file}->label = $_POST["label_".$i];
				$nodes->{$file}->alias = $_POST["alias_".$i];
				$nodes->{$file}->order = $_POST["order_".$i];
				$nodes->{$file}->perms = $_POST["perms_".$i];

				$handle = fopen($path."/menu.json","wb");
				$result = fwrite($handle, json_encode($nodes));
			}
		}

		function updateAccessLevels() {
			$counter = count($_POST)/2;

			for ($i = 0; $i < $counter; $i++) {

				$query =  "UPDATE `users` SET `access_level`='".$_POST["level_".$i]."'";
				$query .= "WHERE `id`='".$_POST["id_".$i]."'";
				
				$this->db->setQuery($query);
				$result = $this->db->query(); 

				if ($result->type)
					$result = new alert(1,"Aggiornamento degli accessi effettuato con successo.");
			}

			return $result;
		}

		function addUser() {
			if ($_POST['nome'] != '')
				if ($_POST['cognome'] != '')
					if ($_POST['username'] != '')
						if ($_POST['email'] != '')
							if ($_POST['password'] != '')
								if ($_POST['password2'] != '')
									if ($_POST['password'] == $_POST['password2']) 
										if ($_POST['access_level'] != '') {
											$query = "INSERT INTO users (username,password,name, lastname,access_level) VALUES('".$_POST['username']."','".$_POST['password']."','".$_POST['nome']."','".$_POST['cognome']."','".$_POST['access_level']."')";
											$this->db->setQuery($query);
											$result = $this->db->query();
											if ($result->type != 0) {
												$result = new alert(1, "Nuovo utente inserito con successo");
											}
										}
										else {
											$result = new alert(0, "La tipologia di accesso deve essere inserita");
										}
									else { 
										$result = new alert(0, "La password ed il suo riscontro devono coincidere"); 
									}
								else $result = new alert(0,"Il riscontro della password deve essere inserito");
							else $result = new alert(0,"La password deve essere inserita");
						else $result = new alert(0, "L'e-mail dev'essere inserita");
					else $result = new alert(0, "Lo username deve essere inserito");
				else $result = new alert(0,"Il cognome deve essere inserito");
			else $result = new alert(0,"Il nome utente deve essere inserito");
		
			return $result;
		}
	}
?>