<?php

return [
    'adminEmail' => 'keigonec@126.com',
    'XiiError' => ['_codes' => [403 => '验证失败非法请求.', 408 => '验证码已过期.', 409 => 'Model未设置.'],
                    '_errorIgnore' => false,
                    '_errorFormat' => 'json',
                    ],
    'XiiToken' => ['_encryptMethod' => 'sha256', 
                    '_privateKey' => '888888', 
                    '_tokenIndex'=> 'XII_API_TOKEN', 
                    '_whereStart' => 5, 
                    '_timeLimit' => 10,
                    '_useImplodePlus' => true],
    'XiiUploader' => ['_pathFolder' => 'upload', 
                        '_pathUseDateFormat' => true,
                        '_sizeLimit' => true,
                        '_sizeMin' => '50k',
                        '_sizeMax' => '5000k', 
                        '_fileTypeLimit' => true,
                        '_fileTypeAllow' => ['png', 'jpg', 'jpeg', 'gif'],
                        '_fileNameEncrypt' => true, 
                        '_thumbnailNeed' => true,
                        '_thumbnailNeedOff' => 'Thumbnail is Off!',
                        '_thumbnailSameType' => true,
                        '_thumbnailPercent' => 5, 
                        '_thumbnailWidth' => 200,
                        '_thumbnailHeight' => 0, 
                        '_thumbnailSuffix' => '_thumb',
                        '_singleOutputArray' => true],
    'XiiArPlus' => ['_deleteField' => 'isdelete',
                     '_deleteValue' => 1,
                     '_deleteForce' => true,
                     '_autoFill' => true,
                     '_autoFieldsPassword' => ['password'],
                     '_autoFieldsDateTime' => ['createDt'],
                     '_autoFieldsIp' => ['ip'],
                     '_autoMethodPassword' => 'md5',
                     '_autoParamsPassword' => '',
                     '_autoParamsDateTime' => '',
                     '_pageLinkPagerOn' => true,
                     '_selectExcept' => ['password','token'],
                     '_editForce' => true
                     ],
    'XiiPassword' => ['_algo' => PASSWORD_DEFAULT,
                        '_salt' => '',
                        '_cost' => 11],
    'XiiCurl' => ['_allowEmptyData' => true],
    'XiiUser' => ['_goHomeUrl' => '/user/',
                    '_goLoginUrl' => '/user/login',
                    '_fieldId' => 'id',
                    '_fieldAccount' => 'account',
                    '_fieldName' => 'nickname',
                    '_fieldPwd' => 'password',
                    '_fieldTimeout' => 'expired',
                    '_valueTimeout' => 3600
                    ],
    'XiiResponse' => ['_sendFormat' => 'json',
                        '_jsonpCallback' => '',
                        '_saveToMemcache' => true,
                        '_saveToRedis' => false,
                    ],
    'XiiController' => ['_apiHost' => 'http://api.cam.cn:88/',
                        ],
];