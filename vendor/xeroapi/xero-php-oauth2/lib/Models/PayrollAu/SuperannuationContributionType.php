<?php
/**
 * SuperannuationContributionType
 *
 * PHP version 5
 *
 * @category Class
 * @package  XeroAPI\XeroPHP
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */

/**
 * Xero Payroll AU API
 *
 * This is the Xero Payroll API for orgs in Australia region.
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

namespace XeroAPI\XeroPHP\Models\PayrollAu;
use \XeroAPI\XeroPHP\PayrollAuObjectSerializer;
use \XeroAPI\XeroPHP\StringUtil;
use ReturnTypeWillChange;

/**
 * SuperannuationContributionType Class Doc Comment
 *
 * @category Class
 * @package  XeroAPI\XeroPHP
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class SuperannuationContributionType
{
    /**
     * Possible values of this enum
     */
    const SGC = 'SGC';
    const SALARYSACRIFICE = 'SALARYSACRIFICE';
    const EMPLOYERADDITIONAL = 'EMPLOYERADDITIONAL';
    const EMPLOYEE = 'EMPLOYEE';
    
    /**
     * Gets allowable values of the enum
     * @return string[]
     */
    public static function getAllowableEnumValues()
    {
        return [
            self::SGC,
            self::SALARYSACRIFICE,
            self::EMPLOYERADDITIONAL,
            self::EMPLOYEE,
        ];
    }
}


