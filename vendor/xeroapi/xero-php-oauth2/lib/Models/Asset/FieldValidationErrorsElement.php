<?php
/**
 * FieldValidationErrorsElement
 *
 * PHP version 5
 *
 * @category Class
 * @package  XeroAPI\XeroPHP
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */

/**
 * Xero Assets API
 *
 * The Assets API exposes fixed asset related functions of the Xero Accounting application and can be used for a variety of purposes such as creating assets, retrieving asset valuations etc.
 *
 * Contact: api@xero.com
 * Generated by: https://openapi-generator.tech
 * OpenAPI Generator version: 5.4.0
 */

/**
 * NOTE: This class is auto generated by OpenAPI Generator (https://openapi-generator.tech).
 * https://openapi-generator.tech
 * Do not edit the class manually.
 */

namespace XeroAPI\XeroPHP\Models\Asset;

use \ArrayAccess;
use \XeroAPI\XeroPHP\AssetObjectSerializer;
use \XeroAPI\XeroPHP\StringUtil;
use ReturnTypeWillChange;

/**
 * FieldValidationErrorsElement Class Doc Comment
 *
 * @category Class
 * @package  XeroAPI\XeroPHP
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class FieldValidationErrorsElement implements ModelInterface, ArrayAccess
{
    const DISCRIMINATOR = null;

    /**
      * The original name of the model.
      *
      * @var string
      */
    protected static $openAPIModelName = 'FieldValidationErrorsElement';

    /**
      * Array of property to type mappings. Used for (de)serialization
      *
      * @var string[]
      */
    protected static $openAPITypes = [
        'field_name' => 'string',
        'value_provided' => 'string',
        'localised_message' => 'string',
        'type' => 'string',
        'title' => 'string',
        'detail' => 'string'
    ];

    /**
      * Array of property to format mappings. Used for (de)serialization
      *
      * @var string[]
      */
    protected static $openAPIFormats = [
        'field_name' => null,
        'value_provided' => null,
        'localised_message' => null,
        'type' => null,
        'title' => null,
        'detail' => null
    ];

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @return array
     */
    public static function openAPITypes()
    {
        return self::$openAPITypes;
    }

    /**
     * Array of property to format mappings. Used for (de)serialization
     *
     * @return array
     */
    public static function openAPIFormats()
    {
        return self::$openAPIFormats;
    }

    /**
     * Array of attributes where the key is the local name,
     * and the value is the original name
     *
     * @var string[]
     */
    protected static $attributeMap = [
        'field_name' => 'fieldName',
        'value_provided' => 'valueProvided',
        'localised_message' => 'localisedMessage',
        'type' => 'type',
        'title' => 'title',
        'detail' => 'detail'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'field_name' => 'setFieldName',
        'value_provided' => 'setValueProvided',
        'localised_message' => 'setLocalisedMessage',
        'type' => 'setType',
        'title' => 'setTitle',
        'detail' => 'setDetail'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'field_name' => 'getFieldName',
        'value_provided' => 'getValueProvided',
        'localised_message' => 'getLocalisedMessage',
        'type' => 'getType',
        'title' => 'getTitle',
        'detail' => 'getDetail'
    ];

    /**
     * Array of attributes where the key is the local name,
     * and the value is the original name
     *
     * @return array
     */
    public static function attributeMap()
    {
        return self::$attributeMap;
    }

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @return array
     */
    public static function setters()
    {
        return self::$setters;
    }

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @return array
     */
    public static function getters()
    {
        return self::$getters;
    }

    /**
     * The original name of the model.
     *
     * @return string
     */
    public function getModelName()
    {
        return self::$openAPIModelName;
    }

    

    

    /**
     * Associative array for storing property values
     *
     * @var mixed[]
     */
    protected $container = [];

    /**
     * Constructor
     *
     * @param mixed[] $data Associated array of property values
     *                      initializing the model
     */
    public function __construct(array $data = null)
    {
        $this->container['field_name'] = isset($data['field_name']) ? $data['field_name'] : null;
        $this->container['value_provided'] = isset($data['value_provided']) ? $data['value_provided'] : null;
        $this->container['localised_message'] = isset($data['localised_message']) ? $data['localised_message'] : null;
        $this->container['type'] = isset($data['type']) ? $data['type'] : null;
        $this->container['title'] = isset($data['title']) ? $data['title'] : null;
        $this->container['detail'] = isset($data['detail']) ? $data['detail'] : null;
    }

    /**
     * Show all the invalid properties with reasons.
     *
     * @return array invalid properties with reasons
     */
    public function listInvalidProperties()
    {
        $invalidProperties = [];

        return $invalidProperties;
    }

    /**
     * Validate all the properties in the model
     * return true if all passed
     *
     * @return bool True if all properties are valid
     */
    public function valid()
    {
        return count($this->listInvalidProperties()) === 0;
    }


    /**
     * Gets field_name
     *
     * @return string|null
     */
    public function getFieldName()
    {
        return $this->container['field_name'];
    }

    /**
     * Sets field_name
     *
     * @param string|null $field_name The field name of the erroneous field
     *
     * @return $this
     */
    public function setFieldName($field_name)
    {

        $this->container['field_name'] = $field_name;

        return $this;
    }



    /**
     * Gets value_provided
     *
     * @return string|null
     */
    public function getValueProvided()
    {
        return $this->container['value_provided'];
    }

    /**
     * Sets value_provided
     *
     * @param string|null $value_provided The provided value
     *
     * @return $this
     */
    public function setValueProvided($value_provided)
    {

        $this->container['value_provided'] = $value_provided;

        return $this;
    }



    /**
     * Gets localised_message
     *
     * @return string|null
     */
    public function getLocalisedMessage()
    {
        return $this->container['localised_message'];
    }

    /**
     * Sets localised_message
     *
     * @param string|null $localised_message Explanation of the field validation error
     *
     * @return $this
     */
    public function setLocalisedMessage($localised_message)
    {

        $this->container['localised_message'] = $localised_message;

        return $this;
    }



    /**
     * Gets type
     *
     * @return string|null
     */
    public function getType()
    {
        return $this->container['type'];
    }

    /**
     * Sets type
     *
     * @param string|null $type Internal type of the field validation error message
     *
     * @return $this
     */
    public function setType($type)
    {

        $this->container['type'] = $type;

        return $this;
    }



    /**
     * Gets title
     *
     * @return string|null
     */
    public function getTitle()
    {
        return $this->container['title'];
    }

    /**
     * Sets title
     *
     * @param string|null $title Title of the field validation error
     *
     * @return $this
     */
    public function setTitle($title)
    {

        $this->container['title'] = $title;

        return $this;
    }



    /**
     * Gets detail
     *
     * @return string|null
     */
    public function getDetail()
    {
        return $this->container['detail'];
    }

    /**
     * Sets detail
     *
     * @param string|null $detail Detail of the field validation error
     *
     * @return $this
     */
    public function setDetail($detail)
    {

        $this->container['detail'] = $detail;

        return $this;
    }


    /**
     * Returns true if offset exists. False otherwise.
     *
     * @param integer $offset Offset
     *
     * @return boolean
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    /**
     * Gets offset.
     *
     * @param integer $offset Offset
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }

    /**
     * Sets value based on offset.
     *
     * @param integer $offset Offset
     * @param mixed   $value  Value to be set
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    /**
     * Unsets offset.
     *
     * @param integer $offset Offset
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    /**
     * Gets the string presentation of the object
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode(
            AssetObjectSerializer::sanitizeForSerialization($this),
            JSON_PRETTY_PRINT
        );
    }
}


