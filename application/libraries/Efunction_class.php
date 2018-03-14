<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Function_class {

	public $level_2_group = ",16,101,102,61,62,63,65,91,92,93,94,21,22,23,31,32,33,34,41,51,52,53,54,55,56,71,82,83,130,131,42,43,44,45,46,47,150,151,152,153,154,155,156,86,87,88,95,99,106,67,157,68,158,159,69,160,161,";   //level2 可见的oid必须配在这里   oid

	function getXml($file) {
		$doc = new DOMDocument ();
		$xmlStr = file_get_contents($file);
                $doc->loadXML($xmlStr);
		return $doc;
	}
	
	function getModulesXml($node) {
		$m = $this->getXml ( $_SERVER ['DOCUMENT_ROOT'] . '/res/config/modules.xml' );
		$r = $m->getElementsByTagName ( "Menu" )->item ( 0 )->getElementsByTagName ( $node )->item ( 0 )->getElementsByTagName ( "name" );
		return $r;
	}
}
?>
