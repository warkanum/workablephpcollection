<?php

/**
* Provides GPS related functions
* 
* Static Class (GPS)
*
* @author  @Warkanum (hein@kinathka.co.za)
* @version 1.0
* @since 1
* @access public
* @updated: 2012-03-12
*/
abstract class GPS 
{
		
	/**
	*	Get the distance between two GPS points
	*	
	*	@param $lat1 Point A latitude
	*	@param $lon1 Point A longitude
	*	@param $lat2 Point B latitude
	*	@param $lon2 Point B longitude
	*	@param $unit Char The Unit to return in (K = KM, N = Nautical Miles, else Miles)
	*	@return Numeric The distance in the specified units
	*	@access public
	*/
	public static function gps_distance($lat1, $lon1, $lat2, $lon2, $unit = "K") 
	{ 
		$theta = $lon1 - $lon2;
		$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad(		$theta));
		$dist = acos($dist);
		$dist = rad2deg($dist);
		$miles = $dist * 60 * 1.1515;
		$unit = strtoupper($unit);

		if ($unit == "K") {
			return ($miles * 1.609344);
		} else if ($unit == "N") {
			return ($miles * 0.8684);
		} else {
			return $miles;
		}
	}
	
	/**
	*	Get the google street coordinates
	*	
	*	@param $address The address (street) string
	*	@return Array with coordinates. Array('lat' =>, 'long' => )
	*	@access public
	*/
	public static function getAddressLatLong($address)
	{
		if (!is_string($address))die("All Addresses must be passed as a string");
		$_url = sprintf('http://maps.google.com/maps?output=js&q=%s',rawurlencode($address));
		$_result = false;
		if($_result = file_get_contents($_url)) {
			if(strpos($_result,'errortips') > 1 || strpos($_result,'Did you mean:') !== false) return false;
			preg_match('!center:\s*{lat:\s*(-?\d+\.\d+),lng:\s*(-?\d+\.\d+)}!U', $_result, $_match);
			$_coords['lat'] = $_match[1];
			$_coords['long'] = $_match[2];
		}
		return $_coords;
	}
	
}

?>