<?php

// if $null is true, then the filtered elements will only be the items allowed
// the items that don't exist are not set to null, they are simply undefined
function elements($items, $array, $default = NULL, $null = false){

	$return = array();

	is_array($items) OR $items = array($items);

	foreach ($items as $item){
		if($null){
			if(array_key_exists($item, $array)){
				$return[$item] = $array[$item];
			}
		}else{
			$return[$item] = array_key_exists($item, $array) ? $array[$item] : $default;
		}
	}

	return $return;

}