<?php

/**
* ownCloud - App Framework
*
* @author Bernhard Posselt
* @copyright 2012 Bernhard Posselt nukeawhale@gmail.com
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

namespace OCA\AppFramework\Db;


abstract class Entity {

	public $id;

	private $updatedFields = array();
	private $fieldTypes = array('id' => 'int');


	/**
	 * Simple alternative constructor for building entities from a request
	 * @param array $params the array which was obtained via $this->params('key')
	 * in the controller
	 * @return Entity
	 */
	public static function fromParams(array $params) {
		$instance = new static();

		foreach($params as $key => $value) {
			$method = 'set' . ucfirst($key);
			$instance->$method($value);
		}

		return $instance;
	}


	/**
	 * Maps the keys of the row array to the attributes
	 * @param array $row the row to map onto the entity
	 */
	public function fromRow(array $row){
		foreach($row as $key => $value){
			$prop = $this->columnToProperty($key);
			if($value !== null && array_key_exists($prop, $this->fieldTypes)){
				settype($value, $this->fieldTypes[$prop]);
			}
			$this->$prop = $value;
		}
		return $this;
	}

	
	/**
	 * Marks the entity as clean needed for setting the id after the insertion
	 */
	public function resetUpdatedFields(){
		$this->updatedFields = array();
	}


	/**
	 * Each time a setter is called, push the part after set
	 * into an array: for instance setId will save Id in the 
	 * updated fields array so it can be easily used to create the
	 * getter method
	 */
	public function __call($methodName, $args){

		// setters
		if(strpos($methodName, 'set') === 0){
			$attr = lcfirst( substr($methodName, 3) );

			// setters should only work for existing attributes
			if(property_exists($this, $attr)){
				$this->markFieldUpdated($attr);
				$this->$attr = $args[0];	
			} else {
				throw new \BadFunctionCallException($attr . 
					' is not a valid attribute');
			}
		
		// getters
		} elseif(strpos($methodName, 'get') === 0) {
			$attr = lcfirst( substr($methodName, 3) );

			// getters should only work for existing attributes
			if(property_exists($this, $attr)){
				return $this->$attr;
			} else {
				throw new \BadFunctionCallException($attr . 
					' is not a valid attribute');
			}
		} else {
			throw new \BadFunctionCallException($methodName . 
					' does not exist');
		}

	}


	/**
	 * Mark am attribute as updated
	 * @param string $attribute the name of the attribute
	 */
	protected function markFieldUpdated($attribute){
		$this->updatedFields[$attribute] = true;
	}


	/**
	 * Transform a database columnname to a property 
	 * @param string $columnName the name of the column
	 * @return string the property name
	 */
	public function columnToProperty($columnName){
		$parts = explode('_', $columnName);
		$property = null;

		foreach($parts as $part){
			if($property === null){
				$property = $part;
			} else {
				$property .= ucfirst($part);
			}
		}

		return $property;
	}


	/**
	 * Transform a property to a database column name
	 * @param string $property the name of the property
	 * @return string the column name
	 */
	public function propertyToColumn($property){
		$parts = preg_split('/(?=[A-Z])/', $property);
		$column = null;

		foreach($parts as $part){
			if($column === null){
				$column = $part;
			} else {
				$column .= '_' . lcfirst($part);
			}
		}

		return $column;
	}


	/**
	 * @return array array of updated fields for update query
	 */
	public function getUpdatedFields(){
		return $this->updatedFields;
	}


	/**
	 * Adds type information for a field so that its automatically casted to
	 * that value once its being returned from the database
	 * @param string $fieldName the name of the attribute
	 * @param string $type the type which will be used to call settype()
	 */
	protected function addType($fieldName, $type){
		$this->fieldTypes[$fieldName] = $type;
	}


	/**
	 * Slugify the value of a given attribute
	 * Warning: This doesn't result in a unique value
	 * @param string $attributeName the name of the attribute, which value should be slugified
	 * @return string slugified value
	 */
	public function slugify($attributeName){
		// toSlug should only work for existing attributes
		if(property_exists($this, $attributeName)){
			$value = $this->$attributeName;
			// replace everything except alphanumeric with a single '-'
			$value = preg_replace('/[^A-Za-z0-9]+/', '-', $value);
			$value = strtolower($value);
			// trim '-'
			return trim($value, '-');
		} else {
			throw new \BadFunctionCallException($attributeName .
				' is not a valid attribute');
		}
	}

}
