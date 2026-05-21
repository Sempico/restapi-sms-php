# Sempico RESTAPI SMS Library

## Requirements

- PHP with Curl Extension Support
- Sempico Account

## How to use
### Install with Composer
Recommended install via [composer](http://getcomposer.org)
```bash
composer require sempico/restapi-sms-php
```

#### Usage with client
```php
use Sempico\RestApi\ApiClient;

// create api cient for usage
$client = new ApiClient('Dfdda23vwWv2khHs'); //parameter is your restapi token

// for example info about me
$result = $client->getInfoAboutMe();
```

#### Registration
```php
use Sempico\RestApi\Auth;

$auth = new Auth(); 

//Regster new customer
$result = $auth->register([
  'login'        => 'restap141i', // login pf a new account (required)
  'password'     => 'restapirestapi', // password a new account (required)
  'name'         => 'Dennis', // name of the responsbale person pf a new account (required)
  'phone'        => '380662185299', // phone pf a new account (required)
  'skype'        => 'desrodman', // email pf a new account (required)
  'email'        => 'some_email@gatum.io', // a company name pf a new account
  'company_name' => 'SMS LTD' // skype pf a new account
]);
```

#### SMS
```php
use Sempico\RestApi\Sms;

$sms = new Sms($client); // client is ApiClient object

// Send sms
$result = $sms->send([
  [
    'number' => [ // list of the phone numbers (required)
      '12345678901'
    ],
    'senderID'  => 'SMS', // sender ID from which your sms will be send (required)
    'text'      => 'Hello\\nWorld!', // content of the message (required)
    'type'      => 'sms', // type of message (SMS, HLR, WAPPUSH, FLASHSMS)
    'beginDate' => '2026-09-01', // plan date where message will go
    'beginTime' => '11:00', // plan time where message will go
    'lifetime'  => 86400, // period how long message will live
    'delivery'  => false, // enable of authomatical back of DLR reports
    'callback_url' => 'https://yourdomain.com/get-dlr'  // URL for sending DLR reports once it will received by the server
  ],
  [
    'number': [ // list of the phone numbers (required)
      '12345678902',
      '12345678900'
    ],
    'senderID' => 'SMSSend', // sender ID from which your sms will be send (required)
    'text'     => 'Hello\\nHow are you?' // content of the message (required)
  ]
]); 

// Make SMS sending by Group's numbers via RESTAPI
$result = $sms->sendBulk([
  'senderID'  => 'SMS', // sender ID from which your sms will be send (required)
  'text'      => 'Hello #first_name#, We send sms to #number#', // content of the message (required)
  'dateStart' => '2022-09-01', // plan date where message will go
  'timeStart' => '11:00', // plan time where message will go
  'timeStop'  => '11:00', // plan time of sending stop
  'phone'     => [
    '12345678822'
  ], // the number
  'id_group'  => [
    13
  ], // array of groups for sending
  'id_group_excluded' => [
    24,
    26
  ] // array of groups for blocking sending
]); 

// Get info about sms
$result = $sms->getInfo([
  'MCC'    => 224, // MCC for search
  'MNC'    => 2, // MNC for search (better use togather with MCC)
  'sender' => 'Sempico', // senderID
  'phone'  => [ // array of phone numbers
    '380660977229',
    '17133010164'
  ],
  'id_base' => [ // array of IDs of the DB
    2,
    4,
    65,
    22
  ],
  'limit'       => null, // limit of sms in response
  'time_period' => '2023-02-12 00:00:00 - 2023-02-12 23:59:59', // time period for check
  'type_sms'    => 'sms', // type of the message
  'state'       => [ // state or states on SMS which you need to get
    'DELIVERD'
  ],
  'offset' => 2 // page of view
]); 

// Make refactoring of sms
$result = $sms->refactoring([
  'number' => [ // array of numbers
    '821032015777',
    12345678901
  ],
  'senderID' => 'Sempico', // sender of SMS
  'text'     => 'Hello\\nHow are you?' // content of SMS
]); 
```

#### Groups of numbers
```php
use Sempico\RestApi\NumbersGroup;

$group = new NumbersGroup($client); // client is ApiClient object

// Get all groups
$result = $group->getAll([
  'id_group' => 'gbBX8dB7kDvBeo0H-VN2CX0bAXXbyJ',   // id of selected Group
  'limit'    => 12345678910,                        // limit of Groups for view
  'offset'   => 'Sempico',                          // page of view
]); 

// Create group
$result = $group->create([
  'name_group'       => 'bigRest', // Group name
  'time_birth'       => '23:22', // time sending birthday greetings
  'originator_birth' => 'aadfd', // SenderID for birthday greetings
  'text_birth'       => 'Hello #first_name#! Good luck', // text for birthday greetings
  'on_birth'         => false // switch of birthday greetings
]); 

// Delete groups
$result = $group->delete([
  'id_group': [ // the list of IDs
    1,
    2,
    3,
  ]
]); 

// Add number in group
$result = $group->addNumber([
  'id_group' => 13, // id of Group
  'numbers' => [ // array of numbers
    {
      'number'     => 12345678901,
      'name'       => 'Iogan',
      'surname'    => 'Bah',
      'patronymic' => 'Sebastian',
      'date_birth' => '1992-12-31',
      'male'       => 'm',
      'note_1'     => 'tralivali',
      'note_2'     => 'tralivali2',
      'note_3'     => 'tralivali3',
      'note_4'     => 'tralivali4',
      'note_5'     => 'tralivali5',
      'note_6'     => 'tralivali6',
      'note_7'     => 'tralivali7',
      'note_8'     => 'tralivali8',
      'note_9'     => 'tralivali9',
      'note_10'    => 'tralivali10'
    },
    {
      'number'     => 12345678900,
      'name'       => 'Liza',
      'surname'    => 'Bah2',
      'patronymic' => '',
      'date_birth' => '1992-12-31',
      'male'       => 'f',
      'note_1'     => 'tralivali',
      'note_2'     => 'tralivali2',
      'note_3'     => 'tralivali3',
      'note_4'     => 'tralivali4',
      'note_5'     => 'tralivali5',
      'note_6'     => 'tralivali6',
      'note_7'     => 'tralivali7',
      'note_8'     => 'tralivali8',
      'note_9'     => 'tralivali9',
      'note_10'    => 'tralivali10'
    }
  ]
]); 

// Search number in groups
$result = $group->searchNumber([
  'id_group' => 13, // id of Group
  'numbers' => [ // array of numbers
    12345678900,
    12345678901,
    17133010164
  ]
]); 

// Delete number from group
$result = $group->deleteNumber([
  'id_group': 13, // id of Group
  'numbers': [ // array of numbers
    12345678900,
    12345678901,
    17133010164
  ]
]); 

// Send bulk
$result = $group->sendBulk([
  'senderID'  => 'SMS', // Sender for sending
  'text'      => 'Hello #first_name#, We send sms to #number#', // Content of SMS
  'dateStart' => '2022-12-14', // Date of sending
  'timeStart' => '22:30', // Time of sending begin
  'timeStop'  => '22:30', // Time of sending stop
  'phone'     => [ // array of numbers
    '12345678822'
  ],
  'id_group' => [ // array of groups for sending
    13
  ],
  'id_group_excluded' => [ // array of groups for blocking sending
    24,
    26
  ]
]); 
```

#### Black lists
```php
use Sempico\RestApi\BlackList;

$blackList = new BlackList($client); // client is ApiClient object

// Add numbers in black list
$result = $blackList->add([
  'numbers' => [
    12345665777,
    12345665772,
    12345663377
  ]
]); 

// Search numbers in black list
$result = $blackList->search([
  'numbers' => [
    12345665777,
    12345665772,
    12345663377
  ]
]); 

// Delete numbers from black list
$result = $blackList->delete([
  'numbers' => [
    12345665777,
    12345665772,
    12345663377
  ]
]); 
```

#### Additional possibilities
```php
use Sempico\RestApi\Additional;

$additional = new Additional($client); // client is ApiClient object

// Get all product prices
$result = $additional->productPrice([
  'ISO' => 'full', // can be set ISO code of country (like US), but not required
  'MCC' => 255, // can be set MCC code of country (like 310), but not required
  'MNC' => 2 // can be set MNC code of country (like 1), but not required (better use with MCC)
]); 

// Get all client prices
$result = $additional->accountPrice([
  'ISO' => 'full', // can be set ISO code of country (like US), but not required
  'MCC' => 255, // can be set MCC code of country (like 310), but not required
  'MNC' => 2 // can be set MNC code of country (like 1), but not required (better use with MCC)
]); 

// Get all prices for country
$result = $additional->countryPrice([
  'ISO' => 'full', // can be set ISO code of country (like US), but not required
  'MCC' => 255, // can be set MCC code of country (like 310), but not required
]); 

// Get all countries by continent
$result = $additional->countryPrice(); 
```

### License
MIT License
