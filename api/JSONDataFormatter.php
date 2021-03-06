<?php
/**
 * @package framework
 * @subpackage formatters
 */
class JSONDataFormatter extends DataFormatter {

	/**
	 * @config
	 * @todo pass this from the API to the data formatter somehow
	 */
	private static $api_base = "api/v1/";

	protected $outputContentType = 'application/json';

	public function supportedExtensions() {
		return array(
			'json',
			'js'
		);
	}

	public function supportedMimeTypes() {
		return array(
			'application/json',
			'text/x-json'
		);
	}

	/**
	 * Generate a JSON representation of the given {@link DataObject}.
	 *
	 * @param DataObjectInterface $obj The object
	 * @param array $fields If supplied, only fields in the list will be returned
	 * @param array $relations Not used
	 * @return String JSON
	 */
	public function convertDataObject(DataObjectInterface $obj, $fields = null, $relations = null) {
		return Convert::raw2json($this->convertDataObjectToJSONObject($obj, $fields, $relations));
	}

	/**
	 * Internal function to do the conversion of a single data object. It builds an empty object and dynamically
	 * adds the properties it needs to it. If it's done as a nested array, json_encode or equivalent won't use
	 * JSON object notation { ... }.
	 * @param DataObjectInterface|DataObject $obj
	 * @param array $fields
	 * @param array $relations
	 * @return stdClass
	 */
	public function convertDataObjectToJSONObject(DataObjectInterface $obj, $fields = null, $relations = null) {
		$className = $obj->class;
		$id = $obj->ID;

		$serobj = ArrayData::array_to_object();

		foreach($this->getFieldsForObj($obj) as $fieldName => $fieldType) {
			// Field filtering
			if($fields && !in_array($fieldName, $fields)) continue;

			$fieldValue = $obj->obj($fieldName)->forTemplate();
			$serobj->$fieldName = $fieldValue;
		}

		if($this->relationDepth > 0) {
			foreach($obj->hasOne() as $relName => $relClass) {
				if(!singleton($relClass)->stat('api_access')) continue;

				// Field filtering
				if($fields && !in_array($relName, $fields)) continue;
				if($this->customRelations && !in_array($relName, $this->customRelations)) continue;

				$fieldName = $relName . 'ID';
				if($obj->$fieldName) {
					$href = Director::absoluteURL($this->config()->api_base . "$relClass/" . $obj->$fieldName);
				} else {
					$href = Director::absoluteURL($this->config()->api_base . "$className/$id/$relName");
				}
				$serobj->$relName = ArrayData::array_to_object(array(
					"className" => $relClass,
					"href" => "$href.json",
					"id" => $obj->$fieldName
				));
			}

			foreach($obj->hasMany() as $relName => $relClass) {
				if(!singleton($relClass)->stat('api_access')) continue;

				// Field filtering
				if($fields && !in_array($relName, $fields)) continue;
				if($this->customRelations && !in_array($relName, $this->customRelations)) continue;

				$innerParts = array();
				$items = $obj->$relName();
				foreach($items as $item) {
					//$href = Director::absoluteURL($this->config()->api_base . "$className/$id/$relName/$item->ID");
					$href = Director::absoluteURL($this->config()->api_base . "$relClass/$item->ID");
					$innerParts[] = ArrayData::array_to_object(array(
						"className" => $relClass,
						"href" => "$href.json",
						"id" => $item->ID
					));
				}
				$serobj->$relName = $innerParts;
			}

			foreach($obj->manyMany() as $relName => $relClass) {
				if(!singleton($relClass)->stat('api_access')) continue;

				// Field filtering
				if($fields && !in_array($relName, $fields)) continue;
				if($this->customRelations && !in_array($relName, $this->customRelations)) continue;

				$innerParts = array();
				$items = $obj->$relName();
				foreach($items as $item) {
					//$href = Director::absoluteURL($this->config()->api_base . "$className/$id/$relName/$item->ID");
					$href = Director::absoluteURL($this->config()->api_base . "$relClass/$item->ID");
					$innerParts[] = ArrayData::array_to_object(array(
						"className" => $relClass,
						"href" => "$href.json",
						"id" => $item->ID
					));
				}
				$serobj->$relName = $innerParts;
			}
		}

		return $serobj;
	}

	/**
	 * Generate a JSON representation of the given {@link SS_List}.
	 *
	 * @param SS_List $set
	 * @param array $fields
	 * @return String XML
	 */
	public function convertDataObjectSet(SS_List $set, $fields = null) {
		$items = array();
		foreach($set as $do) {
			if(!$do->canView()) continue;
			$items[] = $this->convertDataObjectToJSONObject($do, $fields);
		}

		$serobj = ArrayData::array_to_object(array(
			"totalSize" => (is_numeric($this->totalSize)) ? $this->totalSize : null,
			"items" => $items
		));

		return Convert::raw2json($serobj);
	}

	public function convertStringToArray($strData) {
		return Convert::json2array($strData);
	}

}
