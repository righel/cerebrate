<?php

namespace App\Settings\SettingsProvider;

use Cake\ORM\TableRegistry;

require_once(APP . 'Model' . DS . 'Table' . DS . 'SettingProviders' . DS . 'BaseSettingsProvider.php');

use App\Settings\SettingsProvider\BaseSettingsProvider;
use App\Settings\SettingsProvider\SettingValidator;

class CerebrateSettingsProvider extends BaseSettingsProvider
{

    public function __construct()
    {
        $this->settingValidator = new CerebrateSettingValidator();
        parent::__construct();
    }

    public function retrieveSettingPathsBasedOnBlueprint(): array
    {
        $blueprint = $this->generateSettingsConfiguration();
        $paths = [];
        foreach ($blueprint as $l1) {
            foreach ($l1 as $l2) {
                foreach ($l2 as $l3) {
                    foreach ($l3 as $k => $v) {
                        if ($k[0] !== '_') {
                            $paths[] = $k;
                        }
                    }
                }
            }
        }
        return $paths;
    }

    protected function generateSettingsConfiguration()
    {
        return [
            'Application' => [
                'General' => [
                    'Essentials' => [
                        '_description' => __('Ensentials settings required for the application to run normally.'),
                        '_icon' => 'user-cog',
                        'App.baseurl' => [
                            'name' => __('Base URL'),
                            'type' => 'string',
                            'description' => __('The base url of the application (in the format https://www.mymispinstance.com or https://myserver.com/misp). Several features depend on this setting being correctly set to function.'),
                            'default' => '',
                            'severity' => 'critical',
                            'test' => 'testBaseURL',
                        ],
                        'App.uuid' => [
                            'name' => 'UUID',
                            'type' => 'string',
                            'description' => __('The Cerebrate instance UUID. This UUID is used to identify this instance.'),
                            'default' => '',
                            'severity' => 'critical',
                            'test' => 'testUuid',
                        ],
                    ],
                    /*
                    'Miscellaneous' => [
                        'sc2.hero' => [
                            'description' => 'The true hero',
                            'default' => 'Sarah Kerrigan',
                            'name' => 'Hero',
                            'options' => [
                                'Jim Raynor' => 'Jim Raynor',
                                'Sarah Kerrigan' => 'Sarah Kerrigan',
                                'Artanis' => 'Artanis',
                                'Zeratul' => 'Zeratul',
                            ],
                            'type' => 'select'
                        ],
                        'sc2.antagonists' => [
                            'description' => 'The bad guys',
                            'default' => 'Amon',
                            'name' => 'Antagonists',
                            'options' => function ($settingsProviders) {
                                return [
                                    'Amon' => 'Amon',
                                    'Sarah Kerrigan' => 'Sarah Kerrigan',
                                    'Narud' => 'Narud',
                                ];
                            },
                            'severity' => 'warning',
                            'type' => 'multi-select'
                        ],
                    ],
                    'floating-setting' => [
                        'description' => 'floaringSetting',
                        // 'default' => 'A default value',
                        'name' => 'Uncategorized Setting',
                        // 'severity' => 'critical',
                        'severity' => 'warning',
                        // 'severity' => 'info',
                        'type' => 'integer'
                    ],
                    */
                ],
                'Network' => [
                    'Proxy' => [
                        'Proxy.host' => [
                            'name' => __('Host'),
                            'type' => 'string',
                            'description' => __('The hostname of an HTTP proxy for outgoing sync requests. Leave empty to not use a proxy.'),
                            'test' => 'testHostname',
                        ],
                        'Proxy.port' => [
                            'name' => __('Port'),
                            'type' => 'integer',
                            'description' => __('The TCP port for the HTTP proxy.'),
                            'test' => 'testForRangeXY',
                        ],
                        'Proxy.user' => [
                            'name' => __('User'),
                            'type' => 'string',
                            'description' => __('The authentication username for the HTTP proxy.'),
                            'default' => 'admin',
                            'dependsOn' => 'proxy.host',
                        ],
                        'Proxy.password' => [
                            'name' => __('Password'),
                            'type' => 'string',
                            'description' => __('The authentication password for the HTTP proxy.'),
                            'default' => '',
                            'dependsOn' => 'proxy.host',
                        ],
                    ],
                ],
                'UI' => [
                    'General' => [
                        'ui.bsTheme' => [
                            'description' => 'The Bootstrap theme to use for the application',
                            'default' => 'default',
                            'name' => 'UI Theme',
                            'options' => function ($settingsProviders) {
                                $instanceTable = TableRegistry::getTableLocator()->get('Instance');
                                $themes = $instanceTable->getAvailableThemes();
                                return array_combine($themes, $themes);
                            },
                            'severity' => 'info',
                            'type' => 'select'
                        ],
                    ],
                ],
            ],
            'Authentication' => [
                'Providers' => [
                    'KeyCloak' => [
                        'keycloak.enabled' => [
                            'name' => 'Enabled',
                            'type' => 'boolean',
                            'severity' => 'warning',
                            'description' => __('Enable keycloak authentication'),
                            'default' => false,
                        ],
                        'keycloak.provider.applicationId' => [
                            'name' => 'Client ID',
                            'type' => 'string',
                            'severity' => 'info',
                            'default' => '',
                            'description' => __('The Client ID configured for Cerebrate.'),
                            'dependsOn' => 'keycloak.enabled'
                        ],
                        'keycloak.provider.applicationSecret' => [
                            'name' => 'Client Secret',
                            'type' => 'string',
                            'severity' => 'info',
                            'default' => '',
                            'description' => __('The client secret in Cerebrate used to request tokens.'),
                            'dependsOn' => 'keycloak.enabled'
                        ],
                        'keycloak.provider.realm' => [
                            'name' => 'Realm',
                            'type' => 'string',
                            'severity' => 'info',
                            'default' => '',
                            'description' => __('The realm under which the Cerebrate client is enrolled in KeyCloak.'),
                            'dependsOn' => 'keycloak.enabled'
                        ],
                        'keycloak.provider.baseUrl' => [
                            'name' => 'Baseurl',
                            'type' => 'string',
                            'severity' => 'info',
                            'default' => '',
                            'description' => __('The baseurl of the keycloak authentication endpoint, such as https://foo.bar/baz/auth.'),
                            'dependsOn' => 'keycloak.enabled'
                        ],
                        'keycloak.authoritative' => [
                            'name' => 'Authoritative',
                            'type' => 'boolean',
                            'severity' => 'info',
                            'description' => __('Override local role and organisation settings based on the settings in KeyCloak'),
                            'default' => false,
                            'dependsOn' => 'keycloak.enabled'
                        ],
                        'keycloak.default_role_name' => [
                            'name' => 'Default role',
                            'type' => 'select',
                            'severity' => 'info',
                            'description' => __('Select the default role name to be used when creating users'),
                            'options' => function ($settingsProviders) {
                                $roleTable = TableRegistry::getTableLocator()->get('Roles');
                                $allRoleNames = $roleTable->find()->toArray();
                                $allRoleNames = array_column($allRoleNames, 'name');
                                return array_combine($allRoleNames, $allRoleNames);
                            },
                            'dependsOn' => 'keycloak.enabled'
                        ],
                        'keycloak.mapping.org_uuid' => [
                            'name' => 'org_uuid mapping',
                            'type' => 'string',
                            'severity' => 'info',
                            'default' => 'org_uuid',
                            'description' => __('org_uuid mapped name in keycloak'),
                            'dependsOn' => 'keycloak.enabled'
                        ],
                        'keycloak.mapping.role_name' => [
                            'name' => 'role_name mapping',
                            'type' => 'string',
                            'severity' => 'info',
                            'default' => 'role_name',
                            'description' => __('role_name mapped name in keycloak'),
                            'dependsOn' => 'keycloak.enabled'
                        ],
                        'keycloak.mapping.username' => [
                            'name' => 'username mapping',
                            'type' => 'string',
                            'severity' => 'info',
                            'default' => 'preferred_username',
                            'description' => __('username mapped name in keycloak'),
                            'dependsOn' => 'keycloak.enabled'
                        ],
                        'keycloak.mapping.email' => [
                            'name' => 'email mapping',
                            'type' => 'string',
                            'severity' => 'info',
                            'default' => 'email',
                            'description' => __('email mapped name in keycloak'),
                            'dependsOn' => 'keycloak.enabled'
                        ],
                        'keycloak.mapping.first_name' => [
                            'name' => 'first_name mapping',
                            'type' => 'string',
                            'severity' => 'info',
                            'default' => 'given_name',
                            'description' => __('first_name mapped name in keycloak'),
                            'dependsOn' => 'keycloak.enabled'
                        ],
                        'keycloak.mapping.family_name' => [
                            'name' => 'family_name mapping',
                            'type' => 'string',
                            'severity' => 'info',
                            'default' => 'family_name',
                            'description' => __('family_name mapped name in keycloak'),
                            'dependsOn' => 'keycloak.enabled'
                        ],
                    ]
                ]
            ],
            'Security' => [
                'Registration' => [
                    'Registration' => [
                        'security.registration.self-registration' => [
                            'name' => __('Allow self-registration'),
                            'type' => 'boolean',
                            'description' => __('Enable the self-registration feature where user can request account creation. Admin can view the request and accept it in the application inbox.'),
                            'default' => false,
                        ],
                    ]
                ],
                'Development' => [
                    'Debugging' => [
                        'debug' => [
                            'name' => __('Debug Level'),
                            'type' => 'select',
                            'description' => __('The debug level of the instance'),
                            'default' => 0,
                            'options' => [
                                0 => __('Debug Off'),
                                1 => __('Debug On'),
                                2 => __('Debug On + SQL Dump'),
                            ],
                            'test' => function ($value, $setting, $validator) {
                                $validator->range('value', [0, 3]);
                                return testValidator($value, $validator);
                            },
                        ],
                    ],
                ]
            ],
            /*
            'Features' => [
                'Demo Settings' => [
                    'demo.switch' => [
                        'name' => __('Switch'),
                        'type' => 'boolean',
                        'description' => __('A switch acting as a checkbox'),
                        'default' => false,
                        'test' => function () {
                            return 'Fake error';
                        },
                    ],
                ]
            ],
            */
        ];
    }
}

function testValidator($value, $validator)
{
    $errors = $validator->validate(['value' => $value]);
    return !empty($errors) ? implode(', ', $errors['value']) : true;
}

class CerebrateSettingValidator extends SettingValidator
{
    public function testUuid($value, &$setting)
    {
        if (empty($value) || !preg_match('/^\{?[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}\}?$/', $value)) {
            return __('Invalid UUID.');
        }
        return true;
    }


    public function testBaseURL($value, &$setting)
    {
        if (empty($value)) {
            return __('Cannot be empty');
        }
        if (!empty($value) && !preg_match('/^http(s)?:\/\//i', $value)) {
            return __('Invalid URL, please make sure that the protocol is set.');
        }
        return true;
    }
}
