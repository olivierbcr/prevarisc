<?php
    class SearchController extends Zend_Controller_Action
    {
        public function init()
        {
            // Appels ajax
            $ajaxContext = $this->_helper->getHelper('AjaxContext');
            $ajaxContext->addActionContext('run', 'json')
                        ->addActionContext('search-child', 'json')
                        ->addActionContext("next", 'json')
                        ->initContext();

            // Définition du layout
            $this->_helper->layout->setLayout('search');

            // Titre
            $this->view->title = "Recherche";

            // Nom de l'action appellée
            $this->view->action = $this->_request->getActionName();
        }

        public function indexAction()
        {
            // On redirige vers la recherche par établissement
            $this->_forward('etablissement');
        }

        public function etablissementAction()
        {
            // Modèles
            $DB_categorie = new Model_DbTable_Categorie;					$this->view->DB_categorie = $DB_categorie->fetchAll()->toArray();
            $DB_classe = new Model_DbTable_Classe;							$this->view->DB_classe = $DB_classe->fetchAll()->toArray();
            $DB_famille = new Model_DbTable_Famille;						$this->view->DB_famille = $DB_famille->fetchAll()->toArray();
            $DB_type = new Model_DbTable_Type;								$this->view->DB_type = $DB_type->fetchAll()->toArray();
            $DB_avis = new Model_DbTable_Avis;								$this->view->DB_avis = $DB_avis->fetchAll()->toArray();
            $DB_statut = new Model_DbTable_Statut;							$this->view->DB_statut = $DB_statut->fetchAll()->toArray();
            $DB_genre = new Model_DbTable_Genre;							$this->view->DB_genre = $DB_genre->fetchAll()->toArray();
        }

        public function dossierAction()
        {
            // Modèles
            $DB_nature = new Model_DbTable_DossierNatureliste();
            $this->view->DB_nature = $DB_nature->fetchAll()->toArray();
        }

        public function utilisateurAction()
        {
            // Modèles
            $DB_fonction = new Model_DbTable_Fonction();			$this->view->DB_fonction = $DB_fonction->fetchAll()->toArray();
        }

        public function searchChildAction()
        {
            // Resultats HTML
            $html = null;

            // Création de l'objet recherche
            $search = new Model_DbTable_Search;

            // On set le type de recherche
            $search->setItem("etablissement");

            // On gère l'affichage
            $html = $this->view->partial("search/search.phtml", array('item' => "etablissement", 'resultats' => $search->run($this->_request->id), 'niveau' => true));

            // Envoi du html sur la vue
            $this->view->html = $html;
        }

        public function runAction()
        {
            // On execute la requete si il y a quelquechose a traiter
            if ( count($this->_request->getQuery()) > 0 ) {

                $this->view->html = $this->_helper->Search($_GET, 1);
            }
        }

        public function nextAction()
        {
            $this->view->html = $this->_helper->Search($_GET, $this->_request->page);
        }
    }
