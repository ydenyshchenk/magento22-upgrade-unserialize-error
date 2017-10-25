# This tool checks values for specified table columns if it may be unserialized successfully

### If you receiving the below error during upgrade from 2.1.x to 2.2.x using setup:upgrade
```sh
Upgrading data.. Error converting field `value` in table `quote_item_option` where `option_id`=435925 using Magento\Framework\DB\DataConverter\SerializedToJson.
Fix data or replace with a valid value.
Failure reason: 'Unable to unserialize value, string is corrupted.'
```
### execute this tool to get UPDATE SQL command to fix it.

## Installation:
Just fill your values to this array in file:
```sh
$dbConfig = [
    'host' => 'localhost',
    'username' => 'm',
    'password' => ''
];
```

## Options:
- table - should be set with DB name, example: table=DB_NAME.TABLE
- columns - column to check, may be set multiple values with coma separator, example: columns=COLUMN1,COLUMN2
- where [optional] - you can specify some simple where condition here, example: where="code='info_buyRequest'"

## Usage
```sh
$ php checkSerializedData.php table=magento22.quote_item_option columns=value where="code='info_buyRequest'"
.........................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................EEE........................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................................

Reviewed 188886 items 
Please execute the next SQL command to set NULL for columns value in all broken values in table m6393.quote_item_option: 

update m6393.quote_item_option set `value` = null where `option_id` in (435925,435929,435931); 
```