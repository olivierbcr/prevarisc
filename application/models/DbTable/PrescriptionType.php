<?php

    class Model_DbTable_PrescriptionType extends Zend_Db_Table_Abstract
    {
        protected $_name="prescriptiontype"; // Nom de la base
        protected $_primary = "ID_PRESCRIPTIONTYPE"; // Clé primaire

        public function selectArticle($crit)
        {
            //Autocomplétion sur la liste des abréviations
            $select = "SELECT *
                FROM prescriptiontype
                WHERE ABREVIATION_PRESCRIPTIONTYPE LIKE '".$crit."%';
            ";
            //echo $select;
            return $this->getAdapter()->fetchAll($select);
        }

        public function searchIfAbreviationExist($abreviation)
        {
            //Autocomplétion sur la liste des abréviations
            $select = "SELECT COUNT(*)
                FROM prescriptiontype
                WHERE ABREVIATION_PRESCRIPTIONTYPE LIKE '".$abreviation."';
            ";
            //echo $select;
            return $this->getAdapter()->fetchRow($select);
        }
		
		public function getPrescType()
        {
            //Autocomplétion sur la liste des abréviations
            $select = "SELECT *
                FROM prescriptiontype, prescriptionlibelle
                WHERE prescriptiontype.LIBELLE_PRESCRIPTIONTYPE = prescriptionlibelle.ID_PRESCRIPTIONLIBELLE
            ";
            //echo $select;
            return $this->getAdapter()->fetchAll($select);
        }
		
		public function getPrescTypeInfo($idPrescType)
        {
            //Autocomplétion sur la liste des abréviations
            $select = "SELECT *
                FROM prescriptiontype, prescriptionlibelle
                WHERE prescriptiontype.LIBELLE_PRESCRIPTIONTYPE = prescriptionlibelle.ID_PRESCRIPTIONLIBELLE
				AND prescriptiontype.ID_PRESCRIPTIONTYPE = '".$idPrescType."'
            ";
            //echo $select;
            return $this->getAdapter()->fetchRow($select);
        }

    }
