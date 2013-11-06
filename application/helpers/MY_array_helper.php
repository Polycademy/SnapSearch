<?php

//extending the elements functionality to allow undefined filtering
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