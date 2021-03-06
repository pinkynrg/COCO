<?php
	
	if (!class_exists("table")) 	require("view/table.php");


	class View {

		private $pageTitle;
		private	$title;
		private $footer;

		function __construct($controller) {
			$this->Controller = $controller;
			$this->pageTitle = info::$PAGE_TITLE;
			$this->title = info::$TITLE;
			$this->footer = info::$FOOTER;
		}

		function getAuthPanel($result) {
			$authPanel  = "<img id='logo' src='".$this->getLogo()."'>";
			$authPanel .= "<div class='auth_panel_wrapper'>";
			$authPanel .= "<div class='auth_panel'>";
			$authPanel .= "<h2><i class='fa fa-lock'></i> ".info::$TITLE."</h2>";
			$authPanel .= "<form method='POST' action=''>";
			$authPanel .= "<input type='hidden' name='action' value='checkAuth'>";
			$authPanel .= "<div class='auth_row'><label for='username'>Username</label><input type='text' name='username' id='username'></div>";
			$authPanel .= "<div class='auth_row'><label for='password'>Password</label><input type='password' name='password' id='password'></div>";
			$authPanel .= $this->getUserMessage(@$result);
			$authPanel .= "<div class='auth_row'><label for='submit'></label><input name='submit' type='submit' value='Entra' id='login_button'>";
			$authPanel .= "</form>";
			$authPanel .= "</div>";
			$authPanel .= "</div>";
			return $authPanel;
		}

		function getSetupPanel($result) {

			$db_name = isset($_POST['db_name']) ? $_POST['db_name'] : '';
			$db_user = isset($_POST['db_user']) ? $_POST['db_user'] : '';
			$db_host = isset($_POST['db_host']) ? $_POST['db_host'] : '';
			$db_prefix = isset($_POST['db_prefix']) ? $_POST['db_prefix'] : '';

			$setupPanel = "<img id='logo' src='".$this->getLogo()."'>";
			$setupPanel .= "<div class='setup_wrapper'>";
			$setupPanel .= "<div class='setup'>";
			$setupPanel .= "<p> Prima di iniziare ad usare COCO ho bisogno di connettermi ad un database dove salvare i dati: </p>";
			$setupPanel .= "<form method='POST' action=''>";
			$setupPanel .= "<input type='hidden' name='action' value='install'>";
			$setupPanel .= "<div><label for='db_name'>Database name</label><input type='text' name='db_name' value='".$db_name."'><span>(Esempio: myHugeSchema)</span></div>";
			$setupPanel .= "<div><label for='db_user'>Username</label><input type='text' name='db_user' value='".$db_user."'><span>(Esempio: root)</span></div>";
			$setupPanel .= "<div><label for='db_passowrd'>Password</label><input type='password' name='db_pass'><span>(Esempio: batman4ever)</span></div>";
			$setupPanel .= "<div><label for='db_host'>Database Host</label><input type='text' name='db_host' value='".$db_host."'><span>(Esempio: localhost)</span></div>";
			$setupPanel .= $this->getUserMessage(@$result);
			$setupPanel .= "<div id='save_button'><input name='submit' type='submit' value='Salva'></div>";
			$setupPanel .= "</div>";
			$setupPanel .= "</div>";
			return $setupPanel;
		}

		function getPath($path) {
			return "<div class='path'><i class='fa fa-folder'></i> ".str_replace("/"," / ",$path)."</div>";
		}

		function getLogout() {
			$logout = "<div class='logout'>";
			$logout .= "<form method='POST' action=''>";
			$logout .= "<input type='hidden' value='logout' name='action'>";
			$logout .= "<input type='submit' value='".info::$LOGOUT_LABEL."'>";
			$logout .= "</form>";
			$logout .= "</div>";
			return $logout;
		}

		function getMenu($items, $back) {
			$menu = "<h3 class='header_menu'>".info::$MENU_LABEL."</h3>";
			$menu .= "<ul>";

			foreach($items as $item)
				$menu .= "<a href='".info::$BASE_PATH."/".$item->route."'><li>".$item->label. "</li></a>";

			if (!is_null($back))
				$menu .= "<a href='".info::$BASE_PATH."/".$back."'><li class='back'>".info::$MENU_BACK_LABEL."</li></a>";

			$menu .= "</ul>";

			return $menu;
		}

		function getPageTitle() {
			return $this->pageTitle;
		}

		function getTitle() {
			return $this->title;
		}

		function getFooter() {
			return $this->footer;
		}

		function newTable() {
			return new table();
		}

		function getSearchbox() {
		
			$search =  "<div class='search_panel'>";
			
				$search .= "<div class='basic_search'>";
					$search .= "<label for='basic_search'><i class='fa fa-search'></i> Cerca </label> <input name='basic_search' type='text'>";
					$search .= "<input id='basic_search_button' type='button' value='Cerca'>";
					$search .= "<span class='search_label'>+ Ricerca avanzata</span>";
				$search .= "</div>";
			
				$search .= "<div class='advanced_search'>";
					$search .= "<label for='adavanced_search'><i class='fa fa-search'></i> Cerca </label> <input name='adavanced_search' type='text'>";
					$search .= "<input id='advanced_search_button' type='button' value='Cerca'>";
					$search .= "<span class='search_label'>- Ricerca base</span>";
					$search .= "<p>";
					$search .= "</p>";
					$search .= "<p>";
					$search .= "<ul>";
					$search .= "<li>parentesi semplici come <b>(</b> o <b>)</b> permettono di creare un layering di ricerca.</b></li>";
					$search .= "<li>parole chiavi <b>AND</b>, <b>OR</b> e <b>NOT</b> per condizionare la ricerca in modo piu' avanzato.</li>";
					// $search .= "<li>apici semplici come <b>'</b> per specificare keyword contenenti spazi.</li>";
					$search .= "</ul>";
					$search .= "</p>";
					$search .= "<p>";
					$search .= "Una ricerca come:";
					$search .= "</p>";
					// $search .= "<span class='search_example'><b>(</b> <b>(</b>Bedendo <b>AND</b> RE<b>)</b> <b>OR</b> <b>(</b>Perra <b>AND</b> PR<b>)</b> <b>)</b> <b>AND</b> <b>(</b>S.p.A <b>OR</b> SPA <b>OR</b> spa <b>OR</b> <b>'</b>S P A<b>'</b><b>)</b></span>";
					$search .= "<span class='search_example'><b>(</b> <b>(</b>Bedendo <b>AND</b> RE<b>)</b> <b>OR</b> <b>(</b>Perra <b>AND</b> PR<b>)</b> <b>)</b> <b>AND</b> <b>(</b>S.p.A <b>OR</b> SPA <b>OR</b> spa <b>OR</b> <b>'</b>S P A<b>'</b><b>)</b></span>";
					$search .= "<p>";					
					$search .= "resituirá tutti gli appuntamenti di Bedendo a Reggio Emilia e gli appuntamenti di Perra a Parma con una S.p.A.";
					$search .= "</p>";
				$search .= "</div>";
			
			$search .= "</div>";
		
			return $search;
		}

		function rowCounterBox() {
			$search =  "<div class='row_counter_panel'>";
			$search .= "<i class='fa fa-sort-numeric-asc'></i> <span id='row_counter'></span>";
			$search .= "</div>";
			return $search;
		}

		function getUserMessage($result) {

			$status = "";
			if (isset($result->type))
				switch ($result->type) {
					
					case 0: 
						$status .= "<div class='status_panel error'>";
						$status .= "<i class='fa fa-exclamation-circle'></i> ".$result->message;
						$status .= "</div>";
					break;
					
					case 1: 
						$status .= "<div class='status_panel success'>";
						$status .= "<i class='fa fa-check-circle'></i> ".$result->message;
						$status .= "</div>";
					break;
					
					case 2: 
						$status .= "<div class='status_panel alert'>";
						$status .= "<i class='fa fa-info-circle'></i> ".$result->message;
						$status .= "</div>";
					break;

					default: break; 
				}

			return $status;
		}

		function getLogo() {
			return info::$BASE_PATH."/images/coco_logo.png";
		}
	}

?>