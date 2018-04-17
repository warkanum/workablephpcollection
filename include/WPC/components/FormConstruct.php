<?php
/**
* Class that generates form's 
*
* @author  @Warkanum (hein@kinathka.co.za)
* @version 1.0
* @since 1
* @access public
*/
class FormConstruct
{
	private $innerHTML = "";
	private $saveHTML = true;
	
	/**
	*	Begins a form.
	*	@param $name String
	*	@param $id String/Null
	*	@param $action Form Post Action
	*	@param $method String (post/get)
	*	@param $at_ar Extra Attributes: Assoc Array
	*	@access public
	*	@return Form data as string
	*/
	public function begin($name, $id = NULL, $action = '#', $method = 'post', $at_ar = array()) 
	{
        $str = sprintf('<form name="%s" action="%s" method="%s"', $name ,$action, $method);
        if ( isset($id) ) 
            $str .= sprintf(' id="%s"', $id);
			
        $str .= (is_array($at_ar) && count($at_ar) > 0) ? $this->addAttr($at_ar)  . '>': '>';
		if ($this->saveHTML)
			$this->innerHTML .= $str;
        return $str;
    }
	
	/**
	*	End the form. Outputs the closing tag
	*	@access public
	*	@return Form closing tag as string
	*/
	public function end() 
	{
		$str = "</form>";
		if ($this->saveHTML)
			$this->innerHTML .= $str;
        return $str;
	}
	
	/**
		Add an input to the form.
		Type: String (text,password,etc)
		Name: String
		ID: String/NULL
		Value: String
		Extra Attributes: Assoc Array
	
		Returns: the form data as string
	*/
	public function input($type, $name, $id = NULL, $value, $at_ar = array() ) 
	{
		$str = sprintf('<input type="%s" name="%s" value="%s"', $type, $name, $value);
		if ( isset($id) ) 
            $str .= sprintf(' id="%s"', $id);
			
		if (is_array($at_ar) && count($at_ar) > 0)
			$str .= $this->addAttr($at_ar);

		$str .= ' />';
		if ($this->saveHTML)
			$this->innerHTML .= $str;
		return $str;
	}
	
	/**
		Add an textarea to the form.
		Name: String
		ID: String/NULL
		Value: String
		Extra Attributes: Assoc Array
	
		Returns: the form data as string
	*/
	public function textarea($name, $id = NULL, $value = '', $at_ar = array() ) {
		$str = sprintf('<textarea name="%s" "', $name);
		
		if ( isset($id) ) 
            $str .= sprintf(' id="%s"', $id);
			
		if (is_array($at_ar) && count($at_ar) > 0) 
			$str .= $this->addAttr($at_ar);
			
		$str .= sprintf('>%s</textarea>', $value);
		
		if ($this->saveHTML)
			$this->innerHTML .= $str;
			
		return $str;
	}
	
	/**
		Add an a label for a element
		Name: String (Name of the element)
		Text: String
		ID: String/NULL
		Extra Attributes: Assoc Array
	
		Returns: the data as string
	*/
    public function labelFor($name,$text, $id = NULL, $at_ar = array()) 
	{
        $str = sprintf('<label for="%s"', $name);
		
		if ( isset($id) ) 
            $str .= sprintf(' id="%s"', $id);
		
        if (is_array($at_ar) && count($at_ar) > 0)
			$str .= $this->addAttr($at_ar);
			
        $str .= sprintf('>%s</label>', $text);
		
		if ($this->saveHTML)
			$this->innerHTML .= $str;
		
        return $str;
    }
	
	/**
		Add an a select element with options
		Name: String (Name of the element)
		ID: String/NULL
		Options: Array (Value => Name) (Associative)
		Default: The default selected value/name
		First Selected: String/Null (The first heading based element, selected by default.)
		Extra Attributes: Assoc Array
	
		Returns: the data as string
	*/
	public function addSelect($name, $id = NULL, $options_ar, $default_val, $first_sel = NULL, $at_ar = array() ) 
	{
		$str = sprintf('<select name="%s"', $name);
		
		if ( isset($id) ) 
            $str .= sprintf(' id="%s"', $id);
		
		if (is_array($at_ar) && count($at_ar) > 0)
			$str .= $this->addAttr($at_ar);
			
		$str .= ">\n";
		//Now for the options.
		if ( isset($first_sel) ) 
			$str .= sprintf(' <option value="%s">%s</option>%s ', $first_sel, $first_sel, "\n");
		
		foreach ( $options_ar as $val => $text ) 
		{
			$str .= sprintf(' <option value="%s" ', $val);
			
			if ( isset($default_val) && ((trim($default_val) == trim($val))
				 || (trim($default_val) == trim($text))) ) 
				$str .= ' selected="selected"';
				
			$str .= sprintf('>%s</option> %s', $text, "\n");
				
		}
		$str .= "</select>";
		
		if ($this->saveHTML)
			$this->innerHTML .= $str;
			
		return $str;
	}
	
	/**
		Add an a select element with two options arrays. One for values the other for the texts
		Name: String (Name of the element)
		ID: String/NULL
		Options Values: Array
		Options Text: Array
		Default: The default selected value/name
		First Selected: String/Null (The first heading based element, selected by default.)
		Extra Attributes: Assoc Array
	
		Returns: the data as string
	*/
	public function addSelectAr($name, $id = NULL, $options_ar, $options_text_ar, $default_val, $first_sel = NULL, $at_ar = array() )
	{
		$all = array_combine($options_ar, $options_text_ar);
		$this->addSelect($name, $id, $all, $default_val, $first_sel, $at_ar);
	}
	
	
	/**
	*	Get the inner html of the class
	*	@param $print Boolean Defautl to false. If true, prints the html instead of returning it.
	*	@access public
	*	@return String HTML contents of the class
	*/
	public function getHTML($print = false)
	{
		if ($print)
			print($this->innerHTML);
		else
			return $this->innerHTML;
			
		return "";
	}
	
	/**
	*	Add to the inner html of the class instance
	*	@param $html String HTML to add
	*	@access public
	*	@return Nothing
	*/
	public function addHTML($html)
	{
		$this->innerHTML .= $html;
	}

	/**
	*	Set the class to save all actions to it's own inner html for printing later.
	*	@param $true Boolean Save HTML
	*	@access public
	*	@return Nothing
	*/
	public function saveHTML($true)
	{
		$this->saveHTML = $true;	
	}
	
	/**
	*	Can the class save html to itself.
	*	@access public
	*	@return Boolean saveHTML value.
	*/
	public function getSaveHTML()
	{
		return $this->saveHTML;
	}
	
	/**
	Form Utitlities
	*/
	
	/**
	*	Pints attributs string from a array of attributs
	*	@param $at_ar Array List of attributes (associative array)
	*	@access private
	*	@return Attributes String
	*/
	private function addAttr( $at_ar ) 
	{
        $str = '';
        // check minimized attributes
        $specials_ar = array('checked', 'disabled', 'readonly', 'multiple');
		
        foreach( $at_ar as $key=>$val ) 
		{
            if ( in_array($key, $specials_ar) ) 
			{
                if ( !empty($val) ) 
				{ 
                    $str .= sprintf(' %s="%s"', $key, $key);
                }
            } else 
			{
                $str .= sprintf(' %s="%s"', $key, $val);
            }
        }
        return $str;
    }
}

?>