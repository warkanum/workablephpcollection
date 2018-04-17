<?php

/**
* This File is part of WPC (Warkanum's PHP Collection)
* Provides Social website related functions
* Stuff like linking with facebook etc.
*
* Static Class (Social)
*
* @author  @Warkanum (hein@kinathka.co.za)
* @version 1.0
* @since 1
* @access public
* @updated: 2012-03-12
*/
abstract class Social 
{
		
	/**
	*	Prints a facebook widget
	*	
	*	@param $nameToLike String URL/Name to like on facebook.
	*	@param $print Boolean Should the widget be printed or just returned
	*	@return String The widget string.
	*	@access public
	*/
	public static function facebook_likewidget($nameToLike, $print = false, $faces = false, $send = false, 
		$layout = "standard", $action = "like", $colorscheme = "light") 
	{ 
		$strfaces = "no";
		$sendbtn = "false";

		$faces == true ? $strfaces = 'yes' : $strfaces = 'no';
		$send == true ? $sendbtn = 'true' : $sendbtn = 'false';
		
		
		$data = array('href' => $nameToLike,
					'show_faces' =>$strfaces,
					'send' => $sendbtn,
					'layout' => $layout,
					'action' => $action,
					'colorscheme' => $colorscheme,
					'font' => 'verdana',
					'height' => '30',
					'width' => '300',
		);
		
		$url = http_build_query($data);
		$htmlc = sprintf('<iframe src="http://www.facebook.com/plugins/like.php?%s"
				scrolling="no" frameborder="0" allowtransparency="1" title="Facebook"
				style="border:none; width:300px; height:80px">
			</iframe>',$url);
					
		if ($print)
			print($htmlc);
			
		return $htmlc;
	}
	/*
	
	*/
}

?>