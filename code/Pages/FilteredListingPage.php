<?php

class FilteredListingPage extends DataObjectAsPageHolder
{
	private static $hide_ancestor = 'FilteredListingPage';	
}

class FilteredListingPage_Controller extends DataObjectAsPageHolder_Controller {
	
	private static $ajax_template = 'FilteredListingPage';
	private static $ajax_filter = true;
	
	public function init() {
		parent::init();
		
		if($this->stat('ajax_filter'))
		{
			$this->getAJAXJS();
		}
	}

	public function getAJAXJS()
	{
		//Ajax for the filter
		Requirements::customScript(<<<JS
	
			jQuery(document).ready(function(){
					
				//Container for inserting template
				Container = jQuery('.main > .inner');
					
				jQuery('.filter a').on('click', function(ev){
					
					ev.preventDefault();
					
					href = jQuery(this).attr('href');
					
					jQuery.ajax({
						  url: href,
						  beforeSend: function(){
						  	Container.addClass('loading');
						  },
						  success: function(data) {
						    Container.html(data).removeClass('loading');
						  }
					});
					
					return false;
				});
			});
JS
		);		
	}

	public function index()
	{
		if($this->request->isAjax())
		{
			return $this->ajaxRender();
		}
		
		return $this;
	}
	
	function getFilterSettings()
	{
		if($filterSettings = $this->stat('filter_settings'))
		{
			$filters = array();
			
			foreach($filterSettings as $varName => $settings)
			{
				//Set options
				//Select Multiple options at once (default is true)
				$multiselect = (is_array($settings) && isset($settings['MultiSelect'])) ? $settings['MultiSelect'] : true;
				
				//Match all the multi selected items, i.e. select a Product which has category x AND y. Requires a Many_Many or Has_Many
				$matchAll = (is_array($settings) && isset($settings['MatchAll'])) ? $settings['MatchAll'] : false;
				
				//Define the preposition in the filter message, e.g. Products IN x or y category (Defaults to "in")
				$preposition = (is_array($settings) && isset($settings['Preposition'])) ? $settings['Preposition'] : 'in';

				
				//Define the filter
				$filters[$varName] = array(
					'Title' => $settings['Title'],
					'ClassName' => $settings['ClassName'],
					'MultiSelect' => $multiselect,
					'MatchAll' => $matchAll,
					'Preposition' => $preposition
				);
			}
			
			return $filters;	
		}
	}

	/*
	 * Builds the Category filters for the template
	 */
	function getFilters()
	{
		if($filterSettings = $this->getFilterSettings())
		{
			$filters = new ArrayList();
			
			foreach($filterSettings as $varName => $settings)
			{
				$dataClass = $settings['ClassName'];

				//Categories
				if($options = $dataClass::get())
				{
					$filters->push(
						$this->getFilterOptionSet(
							$settings['Title'], 
							$varName, 
							$options,  
							$settings['MultiSelect']
						)
					);
				}			
			}

			return $filters;		
		}
	}

	/*
	 * Build the SQL statement for the WHERE section of the Items function above.
	 * 
	 * If you need a more complex Where statement overload this function in your class 
	 */
	public function getItemsWhere()
	{
		if($filters = $this->getFilterSettings())
		{
			$filter = array();
			
			foreach($filters as $varName => $settings)
			{
				if($values = $this->getFilterValue($varName))
				{
					$filterTitle = $varName;
					
					$relation = Singleton($this->stat('item_class'))->$varName();
					
					//If we are a has_many or Many_many we need the . notation "FieldName.ID"
					if(get_class($relation) == "UnsavedRelationList")
					{
						$filterTitle .= ".ID";
					}
					//Otherwise it's a has one so the Column is "FieldNameID"
					else
					{
						$filterTitle .= "ID";
					}
					
					if($settings['MatchAll'])
					{
						$filterTitle .=":DataObjectAsPageMatchAll";
					}

					$filter[$filterTitle] = explode(",",$values);
				}
			}

			return $filter;
		}
	}

	/*
	 * Overload the Sorting of Items
	 */	
	function getItemsSort()
	{
		if($Sort = $this->stat('item_sort'))
		{
			return $Sort;	
		}
	}	

	
	public function checkFilterValue($filter, $value)
	{
		$raw = $this->getFilterValue($filter);
		
		$values = explode(',', $raw);

		return in_array($value, $values);
	}
	
	//Test for showing all types, used for type filter
	function ShowAll()
	{
		return (!$this->getCurrentFilterString());
	}
	
	//Get a value from the current filter
	function getFilterValue($Key)
	{		
		if($this->request && $Value = $this->request->getVar($Key))
		{
			//Decode
			$Value = urldecode($Value); 
			
			//Sanitize and return
			return Convert::raw2sql($Value);
		}
	}
	
	/**
	 * Creates a set of options from a DOS for use as filters on the front end
	 * Returns a DOS with $LinkingMode, $Title and $Link for use in the templates
	 * 
	 * @param string $VarName the name of the GET var for this option
	 * @param string $FilterTitle the title of the Filter it self, used in the template
	 * @param string $OptionObjects The Set of objects to generate the list from
	 * @param string $TitleField the field used for the option name (usually the objects 'Title')
	 * @param string $KeyField the field used as the key for filtering (usually the objects ID)
	 * @param Bool $MultiSelect Decide whether the filter is single or MultiSelect
	 */
	function getFilterOptionSet($FilterTitle, $VarName, $OptionObjects, $MultiSelect = true, $TitleField = 'Title', $KeyField = 'ID')
	{
		$Options = new ArrayList();

		//Cycle through results
		foreach($OptionObjects as $Object) 
		{
			//The linking mode of this object/option
			$LinkingMode = $this->getOptionLinkingMode($Object,$VarName,$KeyField);
			//This objects/options value
			$Value = $Object->$KeyField;

			if($MultiSelect && $CurrentValues = $this->getFilterValue($VarName))
			{
				//If selected we need to add the item to the existing values
				if($LinkingMode == 'selected')
				{
					$CurrentValuesArray = explode(',',$CurrentValues);
					//Remove the value from the array
					$NewValuesArray = array_values(array_diff($CurrentValuesArray,array($Value)));
				}
				//If unselected we need to add the item to the existing values
				else
				{
					$CurrentValuesArray = explode(',',$CurrentValues);
					//Add the value to the array
					$NewValuesArray = array_merge($CurrentValuesArray, array($Value));					
				}

				$NewValues = implode(',',$NewValuesArray);
			}
			elseif($LinkingMode == 'selected')
			{
					
					//Remove the value from the array
					$NewValues = '';
			}
			else //If none are selected we can just return the current value
			{
				$NewValues = $Value;
			}
			
			$getVar = ($NewValues) ? '?' . $VarName . '='. urlencode($NewValues) : '';
			
			$Link = $this->Link() . $getVar . $this->getCurrentFilterString(array($VarName));
			
			//Push the results into our array
			$Options->push(new ArrayData(array(
					'LinkingMode' => $LinkingMode,
					'Title' => $Object->$TitleField,
					'Link' => $Link
				)
			));
		}
		
		//Add the extra bits (Title and show all, if applicable)
		$Filter = new ArrayData(array(
			'Title' => $FilterTitle,
			'Options' => $Options,
			'ResetLink' => ($this->getFilterValue($VarName)) ? $this->Link() . $this->getCurrentFilterString(array($VarName)) : false,
			'CurrentlyFiltered' => $this->getFilterValue($VarName) ? true : false
		));
		
		return $Filter;	
	}

	/*
	 * Returns the linking mode for the specified filter option
	 */
	function getOptionLinkingMode($Object, $VarName, $KeyField = 'ID')
	{
		//Set linking mode 'all','selected','unselected'
		if(!$this->getFilterValue($VarName))
		{
			$LinkingMode = 'all';
		}
		else
		{
			$CurrentVars = explode(',',urldecode($this->getFilterValue($VarName)));
			$LinkingMode = (in_array($Object->$KeyField, $CurrentVars)) ? 'selected' : 'unselected';
		}	

		return $LinkingMode;	
	}
	
	//Get the current filter string for use in the link generation
	function getCurrentFilterString($Exclude = Null, $Symbol = '&', $ExcludePagination = true)
	{
		if(!is_array($Exclude))
			$Exclude = array($Exclude);
		
		//Define the vars that are part of the filter
		$include = array();
		
		if($filterSettings = $this->getFilterSettings())
		{
			foreach($filterSettings as $varname => $settings)
			{
				if(!in_array($varname,$Exclude))
				{
					$include[] = $varname;
				}
			}
		}

		//add start to the include array if we are not excluding it
		if(!$ExcludePagination)
			$include[] = 'start';
			
		if(is_object($this->request))
		{
			$Values = $this->request->getVars();
			
			if($Values['url']) unset($Values['url']);
			
			$String = '';
			
			if($Values && count($Values))
			{
				foreach($Values as $Key => $Value)
				{
					if(in_array($Key, $include))
					{					
						$String .= $Symbol . $Key . '=' . urlencode($Value);
						$Symbol = "&";
					}
				}
			}
			
			return $String;
		}
	}

	/*
	 * Generates the filter message for one value, which can then be passed into the filter builder to put the whole message together
	 * 
	 * $VarName - the name of the GET variable we are creating the message for
	 * $Preposition - The correct grammer to place before e.g. 'in' category or 'of' type etc.
	 * $FilterClass - The Class of the item that is being filtered by that this message applies to, for example DataObjectAsPageCategory 
	 */
	public function getIndividualFilterMessage($VarName = 'category', $Preposition = 'in', $ClassName = 'DataObjectAsPageCategory', $matchAll = false)
	{
		$CurrentValues = $this->getFilterValue($VarName);
		$Message = '';
		$Values = explode(',', $CurrentValues);
			
		if($Values[0])
		{
			$ValueCount = count($Values);
			$ItemName = ($ValueCount > 1) ? singleton($ClassName)->stat('plural_name') : singleton($ClassName)->stat('singular_name');
			
			$Message = ' ' . $Preposition . ' ' . $ItemName;
			
			$i = $ValueCount;
			
			foreach($Values as $Value)
			{
				if(is_numeric($Value) && $Item = $ClassName::get()->byID($Value))
				{
					//Set the correct joining grammer either 'and' ',' or nothing.
					if($ValueCount > 1 && $i == 1)
					{
						$Grammer = $matchAll ? ' and' : ' or';
					}
					elseif($i != $ValueCount && $i)
					{
						$Grammer = ',';
					}
					else
					{
						$Grammer = '';
					} 
					
					$Message .=  $Grammer . ' <strong><a href="' . $Item->Link() .'">' . $Item->Title . '</a></strong>';
				}
				
				$i--;
			}
		}
		
		return $Message;
	}
	
	/*
	 * Builds the message to return to the user after filtering
	 * 
	 * $Messages - an array of filter state messages telling the user the current filters applied
	 */
	public function CurrentFilterMessage()
	{
		if($filterSettings = $this->getFilterSettings())
		{
			$OutputMessages = array();
			
			//Loop through each message and build it if there is a filter applied for that var
			foreach($filterSettings as $varName => $settings)
			{
				if($this->getFilterValue($varName))
				{			
					$OutputMessages[] = $this->getIndividualFilterMessage($varName, $settings['Preposition'], $settings['ClassName'], $settings['MatchAll']);	
				}
			}
			
			if($Items = $this->Items())
			{
				$ItemCount = $Items->Count();			
			}
			else
			{
				$ItemCount = 0;
			}
	
			$ItemInstance = singleton($this->stat('item_class'));
				
			$PluralName = $ItemInstance->stat('plural_name') ? $ItemInstance->stat('plural_name') : 'items'; 
			$SinglularName = $ItemInstance->stat('singular_name') ? $ItemInstance->stat('singular_name') : 'item'; 				
			
			if(count($OutputMessages))
			{
	
				//Set the message class & count 
				//if Found some
				if($ItemCount > 0)
				{
					$ItemName = ($ItemCount == 1) ? $SinglularName : $PluralName;
					$Class = 'info';
	
					//Initial String
					$CompleteMessage = '<span class="count">' . $ItemCount . ' ' . $ItemName . ':</span> You are viewing ' . $PluralName; 
				}
				//None Found
				else
				{
					$ItemName = $PluralName;
					$Class = 'danger';
					
					//Initial String
					$CompleteMessage = 'There were no ' . $PluralName . ' found ';
				}
				
				//Construct single message from array
				$CompleteMessage .= implode(' and ', $OutputMessages);
			}
			else
			{
				$CompleteMessage = 'You are viewing all <span class="count">' . $ItemCount . ' ' . $PluralName . '</span>'; 
				$Class = 'info';
			}
			
			//Place into an array ready to return to the template
			$Output = new ArrayData(array(
				'Message' => $CompleteMessage,
				'Class' => $Class
			));
			
			return $Output;					
		}
	}

	/*
	 * Get backlink from URL
	 */
	function getBackLink()
	{
		if($string = $this->getCurrentFilterString(null, '?'))
		{
			return '?backlink=' . base64_encode($string);
		}
	}
	
	/*
	 * Function to render the part of the page when using ajax for filtering.
	 */
	function ajaxRender()
	{
		if($this->stat('ajax_template'))
		{
			$template = $this->stat('ajax_template');
		}
		else
		{
			$template = $this->ClassName;
		}
		
		
		$HTML = $this->renderWith($template);

		return $HTML;//$HTML;
	}
}