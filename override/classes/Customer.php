<?php   
	
 namespace override\classes\Customer;

Class Customer extends \CustomerCore
{
     public $prueba1;

     public function __construct($id = null){
        self::$definition['fields']['prueba1'] = array('type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64);
        parent::__construct($id);

     }

     public function getPrueba1(){
            return $this->$prueba1;
     }

     public function setPrueba1($prueba1){
            $this->$prueba1 = $prueba1;
     }
}
?>