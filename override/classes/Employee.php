<?php   
	
 

Class Employee extends EmployeeCore 
{
     public $prueba2;

     public function __construct($id = null, $idLang = null, $idShop = null){
        self::$definition['fields']['prueba2'] = array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64);
        parent::__construct($id, null, $idShop);

     }

     public function getPrueba2(){
            return $this->$prueba2;
     }

     public function setPrueba2($prueba2){
            $this->$prueba2 = $prueba2;
     }


     
}
?>