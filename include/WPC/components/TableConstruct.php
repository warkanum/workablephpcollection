<?php

/**
* Base class of TableConstruct classes
* Class that generates tables
*
* @author  @Warkanum (hein@kinathka.co.za)
* @version 1.0
* @since 1
* @access public
*/
abstract class TableConstruct_Shared
{
	/**
	*	Pints attributs string from a array of attributs
	*	@param $at_ar Array List of attributes (associative array)
	*	@access protected
	*	@return Attributes String
	*/
	protected function addAttr( $at_ar ) 
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

/**
* Class that generates tables
*
* @author  @Warkanum (hein@kinathka.co.za)
* @version 1.0
* @since 1
* @access public
*/
class TableConstruct extends TableConstruct_Shared
{
	private $innerHTML = "";
	private $saveHTML = true;
	private $rows = array();
	
	//subclasses
	
	/**
	*	Begins a table.
	*	@param $name String Table Name
	*	@param $id String Table ID
	*	@param $class String Table CSS Class
	*	@param $at_ar Array Extra attributes
	*	@access public
	*	@return Table data as string
	*/
	public function begin($name, $id = NULL, $class = NULL, $at_ar = array()) 
	{
        $str = sprintf('<table name="%s" ', $name);
        if ( isset($id) ) 
            $str .= sprintf(' id="%s"', $id);
			
		if ( isset($class) ) 
            $str .= sprintf(' class="%s"', $class);
			
        $str .= (is_array($at_ar) && count($at_ar) > 0) ? $this->addAttr($at_ar)  . '>': '>';
		if ($this->saveHTML)
			$this->innerHTML .= $str;
        return $str;
    }
	
	/**
	*	End the table. Outputs the closing tag.
	*	Must be called last just.
	*	@access public
	*	@return Form closing tag as string
	*/
	public function end($printCells = true) 
	{
		$cells = "";
		if ($printCells)
			$cells = $this->outputCells(false);
			
		$str = "</table>";
		if ($this->saveHTML)
			$this->innerHTML .= $str;
        return ($cells . $str);
	}
	
	/**
	*	Add a new row to the table
	*	@param $class CSS class name of the row
	*	@param $at_ar Array Extra attributes
	*	@access public
	*	@return Table data as string
	*/
	public function addRow($class = NULL, $at_ar = array()) 
	{
		$row = new TableConstruct_Row($class, $at_ar);
		array_push( $this->rows, $row );
	}
	
    /**
	*	Add a cell to the current table row. addRow must be called once before calling this.
	*	@param $data String The contents the to put in the cell
	*	@param $type String Type of cell (header,footer,body)
	*	@param $class CSS class name of the row
	*	@param $at_ar Array Extra attributes
	*	@access public
	*	@return Table data as string
	*/
	public function addCell($data = '', $type = 'body',  $class = NULL , $at_ar = array() ) 
	{
		$cell = new TableConstruct_Cell( $data, $type, $class, $at_ar );
		$this->rows[ count( $this->rows ) - 1 ]->addCell($cell);
	}
	
	/**
	*	Returns the body of the tables. All the cells and their data.
	*	Must only be called after adding cell and rows. 
	*	@param $print Boolean Should the output be printed
	*	@access public
	*	@return String Contents of the table. Rows and Cols.
	*/
	public function outputCells($print = false) 
	{
		$data = "";
		foreach ($this->rows as $row)
		{
			$data .= $row->begin();
			foreach ($row->getCells() as $cell)
			{
				$data .= $cell->output();
			}
			$data .= $row->end();
		}
		
		if ($print)
			print($data);	
		
		if ($this->saveHTML)
			$this->innerHTML .= $data;
		
		return $data;
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
	
}

/**
* Class that represents a row in a TableConstruct class
*
* @author  @Warkanum (hein@kinathka.co.za)
* @version 1.0
* @since 1
* @access public
*/
class TableConstruct_Row extends TableConstruct_Shared
{
	public $class;
	public $at_ar;
	public $cell_ar; //array of TableConstruct_Cell
	
	/**
	*	Make a new row and set the default values
	*	@param $class String CSS class for the row
	*	@param $at_ar Array List of attributes (associative array)
	*	@access public
	*	@return Nothing
	*/
    function __construct($class = NULL, $at_ar = array()) 
	{
        $this->class = $class;
        $this->at_ar = $at_ar;
        $this->cell_ar = array();
    }
	
	/**
	*	Get the row's cells
	*	@access public
	*	@return Cells Array of TableConstruct_cell
	*/
	public function getCells()
	{
		return $this->cell_ar;
	}
	
	/**
	*	Add a cell to the row.
	*	@param $cell TableConstruct_Cell The cell class to be added to the row
	*	@access public
	*	@return Nothing
	*/
	public function addCell($cell)
	{
		array_push($this->cell_ar, $cell);
	}
	
	/**
	*	Returns the html text of the row and end with the first opening tag.
	*	@access public
	*	@return String HTML Text
	*/
	public function begin()
	{
		$str = sprintf('<tr ');
		if ( isset($this->class) && strlen($this->class) > 0 ) 
			$str .= sprintf(' class="%s"', $this->class);
			
		$str .= (is_array($this->at_ar) && count($this->at_ar) > 0) ? $this->addAttr($this->at_ar)  . '>': '>';
		
		return $str;
	}
	
	/**
	*	Returns the html text of the closing tag of the row.
	*	@access public
	*	@return String HTML Text
	*/
	public function end()
	{
		$str = sprintf('</tr>');
		return $str;
	}
	

}


/**
* Class that represents a cell in row in the TableConstruct class
*
* @author  @Warkanum (hein@kinathka.co.za)
* @version 1.0
* @since 1
* @access public
*/
class TableConstruct_Cell extends TableConstruct_Shared
{
	public $data;
	public $type;
	public $class;
	public $at_ar;
	
	/**
	*	Make a new cell and set the default values
	*	@param $data String Data to be put into the row
	*	@param $type String The type of cell (header,footer,body)
	*	@param $class String CSS class for the row
	*	@param $at_ar Array List of attributes (associative array)
	*	@access public
	*	@return Nothing
	*/
    function __construct($data, $type, $class = NULL, $at_ar = array()) 
	{
		$this->data = $data;
		$this->type = $type;
        $this->class = $class;
        $this->at_ar = $at_ar;
    }
	
	/**
	*	Returns the html text of the cell.
	*	@access public
	*	@return String HTML Text
	*/
	public function output()
	{
		$str = "";
		if ($this->type == "header")
		{
			$str = sprintf('<th ');
			if ( isset($this->class) && strlen($this->class) > 0 ) 
				$str .= sprintf(' class="%s"', $this->class);
				
			$str .= (is_array($this->at_ar) && count($this->at_ar) > 0) ? $this->addAttr($this->at_ar)  . '>': '>';
			$str .= sprintf('%s</th>', $this->data);
		}
		elseif ($this->type == "footer")
		{
			$str = sprintf('<tfoot ');
			if ( isset($this->class) && strlen($this->class) > 0 ) 
				$str .= sprintf(' class="%s"', $this->class);
				
			$str .= (is_array($this->at_ar) && count($this->at_ar) > 0) ? $this->addAttr($this->at_ar)  . '>': '>';
			$str .= sprintf('%s</tfoot>', $this->data);
		}
		else
		{
			$str = sprintf('<td ');
			if ( isset($this->class) && strlen($this->class) > 0 ) 
				$str .= sprintf(' class="%s"', $this->class);
				
			$str .= (is_array($this->at_ar) && count($this->at_ar) > 0) ? $this->addAttr($this->at_ar)  . '>': '>';
			$str .= sprintf('%s</td>', $this->data);
		
		}
        return $str;
	}
}



?>