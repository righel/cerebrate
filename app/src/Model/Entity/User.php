<?php

namespace App\Model\Entity;

use App\Model\Entity\AppModel;
use Cake\ORM\Entity;
use Authentication\PasswordHasher\DefaultPasswordHasher;

require_once(APP . 'Model' . DS . 'Table' . DS . 'SettingProviders' . DS . 'UserSettingsProvider.php');
use App\Settings\SettingsProvider\UserSettingsProvider;

class User extends AppModel
{
    protected $_hidden = ['password', 'confirm_password'];

    protected $_virtual = ['user_settings_by_name', 'user_settings_by_name_with_fallback'];

    protected function _getUserSettingsByName()
    {
        $settingsByName = [];
        if (!empty($this->user_settings)) {
            foreach ($this->user_settings as $i => $setting) {
                $settingsByName[$setting->name] = $setting;
            }
        }
        return $settingsByName;
    }

    protected function _getUserSettingsByNameWithFallback()
    {
        if (!isset($this->SettingsProvider)) {
            $this->SettingsProvider = new UserSettingsProvider();
        }
        $settingsByNameWithFallback = [];
        if (!empty($this->user_settings)) {
            foreach ($this->user_settings as $i => $setting) {
                $settingsByNameWithFallback[$setting->name] = $setting->value;
            }
        }
        $settingsProvider = $this->SettingsProvider->getSettingsConfiguration($settingsByNameWithFallback);
        $settingsFlattened = $this->SettingsProvider->flattenSettingsConfiguration($settingsProvider);
        return $settingsFlattened;
    }

    protected function _setPassword(string $password) : ?string
    {
        if (strlen($password) > 0) {
            return (new DefaultPasswordHasher())->hash($password);
        }
    }
}
