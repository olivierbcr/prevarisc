<?php

    class ContactController extends Zend_Controller_Action
    {
        private $droit;

        public function init()
        {
            // Actions à effectuées en AJAX
            $ajaxContext = $this->_helper->getHelper('AjaxContext');
            $ajaxContext->addActionContext('index', 'html')
                        ->addActionContext('display', 'html')
                        ->addActionContext('delete', 'json')
                        ->addActionContext('add', 'json')
                        ->addActionContext('get', 'json')
                        ->addActionContext('edit', 'html')
                        ->initContext();

            // Droits
            $droits = $this->_helper->Droits()->get();
            $this->view->droit_ecriture = false;

            // Droits sur la page
            if (isset($this->_request->item)) {
                switch ($this->_request->item) {

                    case "etablissement" :
                        if($this->_helper->Droits()->checkEtablissement($this->_request->id))
                            $this->_helper->Droits()->redirect();
                        else {

                            $model_etablissement = new Model_DbTable_Etablissement;
                            $informations = $model_etablissement->getInformations( $this->_request->id );

                            $droit_ecriture = $droits->ID_GENRE[$informations["ID_GENRE"]]["DROITECRITURE_GROUPEGENRE"];
                            $this->view->droit_ecriture = $droit_ecriture;

                            if($droit_ecriture == 0 && !in_array($this->getRequest()->getActionName(), array("index", "get", "form")))
                                $this->_helper->Droits()->redirect();
                        }
                        break;

                    case "dossier":
                        if($this->_helper->Droits()->checkDossier($this->_request->id))
                            $this->_helper->Droits()->redirect();
                        else
                            $this->view->droit_ecriture = true;
                        break;

                    case "groupement" :
                    case "commission" :
                        if($droits->DROITADMINPREV_GROUPE == 0)
                            $this->_helper->Droits()->redirect();
                        else
                            $this->view->droit_ecriture = true;
                        break;
                }
            }
        }

        public function indexAction()
        {
            $DB_contact = new Model_DbTable_UtilisateurInformations;
            $this->view->contacts = $DB_contact->getContact($this->_request->item, $this->_request->id);

            // Placement
            $this->view->item = $this->_request->item;
            $this->view->id = $this->_request->id;

            $this->view->ajax = $this->_request->ajax;

            // Si on est dans un établissement, on cherche les contacts des ets parents
            if ($this->_request->item == "etablissement") {

                $model_ets = new Model_DbTable_Etablissement;
                $etablissement_parents = $model_ets->getAllParents($this->_request->id);
                $array = array();

                if($etablissement_parents != null)
                    foreach($etablissement_parents as $ets)
                        if ($ets != null) {
                            $contacts = $DB_contact->getContact($this->_request->item, $ets["ID_ETABLISSEMENT"]);
                            if($contacts != null)
                                $array[] = $contacts;
                        }
                $this->view->contacts_parent = $array;
            }

            // Taille des cases
            $this->view->size = ( $this->_request->item == "dossier" ) ? 3 : 4;
        }

        public function formAction()
        {
            // On récupère la liste des fonctions des contacts
            $DB_contactfonction = new Model_DbTable_Fonction;
            $this->view->contact_fonction_list = $DB_contactfonction->fetchAll()->toArray();

            // On récupère la liste des civilités
            $DB_civilite = new Model_DbTable_UtilisateurCivilite;
            $this->view->civilite_list = $DB_civilite->fetchAll()->toArray();

            // Groupes
            $DB_groupe = new Model_DbTable_Groupe;
            $this->view->groupes = $DB_groupe->fetchAll()->toArray();

            // Placement
            $this->view->item = $this->_request->item;
            $this->view->id = $this->_request->id;

            $this->view->droitsSYS = $this->_helper->Droits()->get()->DROITADMINSYS_GROUPE;
        }

        public function addAction()
        {
            // Update
            // $row->setFromArray($form->getValues('postForm'))->save();

            $key = null;
            $DB_contact = null;

            // Initalisation des modèles
            $DB_informations = new Model_DbTable_UtilisateurInformations;

            switch ($this->_request->item) {
                case "etablissement":
                    $DB_contact = new Model_DbTable_EtablissementContact;
                    $key = "ID_ETABLISSEMENT";
                    break;
                case "dossier":
                    $DB_contact = new Model_DbTable_DossierContact;
                    $key = "ID_DOSSIER";
                    break;
                case "groupement":
                    $DB_contact = new Model_DbTable_GroupementContact;
                    $key = "ID_GROUPEMENT";
                    break;
                case "commission":
                    $DB_contact = new Model_DbTable_CommissionContact;
                    $key = "ID_COMMISSION";
                    break;
            }

            $id_item = $this->_request->id;
            $exist = isset($_POST["exist"]) ? $_POST["exist"] : false;

            if (!$exist) {
                // Mise en base du contact
                $id = $DB_informations->insert(array_intersect_key($_POST, $DB_informations->info('metadata')));
            }

            // Association du contact
            $contact = $DB_contact->createRow();
            $contact->$key = $id_item;
            $contact->ID_UTILISATEURINFORMATIONS = $exist ? $_POST["ID_UTILISATEURINFORMATIONS"] : $id;
            $contact->save();
        }

        public function editAction()
        {
            $DB_informations = new Model_DbTable_UtilisateurInformations;
            $row = $DB_informations->find( $this->_request->id )->current();
            $this->view->user_info = $row;

            if ($_POST) {
                $this->_helper->viewRenderer->setNoRender(); // On desactive la vue
                $row->setFromArray(array_intersect_key($_POST, $DB_informations->info('metadata')))->save();
            } else
                $this->_forward("form");
        }

        public function deleteAction()
        {
            $this->_helper->viewRenderer->setNoRender();

            $DB_current = null;
            $DB_informations = new Model_DbTable_UtilisateurInformations;
            $DB_contact = array(
                new Model_DbTable_EtablissementContact,
                new Model_DbTable_DossierContact,
                new Model_DbTable_GroupementContact,
                new Model_DbTable_CommissionContact
            );
            $primary = null;

            // Initalisation des modèles
            switch ($this->_request->item) {
                case "etablissement":
                    $DB_current = $DB_contact[0];
                    $primary = "ID_ETABLISSEMENT";
                    break;
                case "dossier":
                    $DB_current = $DB_contact[1];
                    $primary = "ID_DOSSIER";
                    break;
                case "groupement":
                    $DB_current = $DB_contact[2];
                    $primary = "ID_GROUPEMENT";
                    break;
                case "commission":
                    $DB_current = $DB_contact[3];
                    $primary = "ID_COMMISSION";
                    break;
            }

            // Appartient à d'autre ets ?
            $exist = false;
            foreach ($DB_contact as $key => $model) {
                if (count($model->fetchAll("ID_UTILISATEURINFORMATIONS = " . $this->_request->id)->toArray()) > (($model == $DB_current) ? 1 : 0) ) {
                    $exist = true;
                }
            }

            // Est ce que le contact n'appartient pas à d'autre etablissement ?
            if (!$exist) {
                $DB_current->delete("ID_UTILISATEURINFORMATIONS = " . $this->_request->id); // Porteuse
                $DB_informations->delete( "ID_UTILISATEURINFORMATIONS = " . $this->_request->id ); // Contact
            } else {
                $DB_current->delete("ID_UTILISATEURINFORMATIONS = " . $this->_request->id . " AND " . $primary . " = " . $this->_request->id_item); // Porteuse
            }
        }

        public function getAction()
        {
            $DB_informations = new Model_DbTable_UtilisateurInformations;
            $this->view->resultats = $DB_informations->getAllContacts($this->_request->q);
        }

    }
