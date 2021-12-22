<?php
namespace BreadcrumbNavigation;

require_once(APP . 'Controller' . DS . 'Component' . DS . 'Navigation' . DS . 'base.php'); 

class UsersNavigation extends BaseNavigation
{
    public function addRoutes()
    {
        $this->bcf->addRoute('Users', 'settings', [
            'label' => __('User settings'),
            'url' => '/users/settings/',
            'icon' => 'user-cog'
        ]);
    }

    public function addParents()
    {
        // $this->bcf->addParent('Users', 'settings', 'Users', 'view');
    }

    public function addLinks()
    {
        $bcf = $this->bcf;
        $request = $this->request;
        $passedData = $this->request->getParam('pass');
        $this->bcf->addLink('Users', 'view', 'UserSettings', 'index', function ($config) use ($bcf, $request, $passedData) {
            if (!empty($passedData[0])) {
                $user_id = $passedData[0];
                $linkData = [
                    'label' => __('Account settings', h($user_id)),
                    'url' => sprintf('/users/settings/%s', h($user_id))
                ];
                return $linkData;
            }
            return [];
        });
        $this->bcf->addLink('Users', 'view', 'UserSettings', 'index', function ($config) use ($bcf, $request, $passedData) {
            if (!empty($passedData[0])) {
                $user_id = $passedData[0];
                $linkData = [
                    'label' => __('User Setting [{0}]', h($user_id)),
                    'url' => sprintf('/user-settings/index?Users.id=%s', h($user_id))
                ];
                return $linkData;
            }
            return [];
        });
        $this->bcf->addLink('Users', 'edit', 'UserSettings', 'index', function ($config) use ($bcf, $request, $passedData) {
            if (!empty($passedData[0])) {
                $user_id = $passedData[0];
                $linkData = [
                    'label' => __('Account settings', h($user_id)),
                    'url' => sprintf('/users/settings/%s', h($user_id))
                ];
                return $linkData;
            }
            return [];
        });
        $this->bcf->addLink('Users', 'edit', 'UserSettings', 'index', function ($config) use ($bcf, $request, $passedData) {
            if (!empty($passedData[0])) {
                $user_id = $passedData[0];
                $linkData = [
                    'label' => __('User Setting [{0}]', h($user_id)),
                    'url' => sprintf('/user-settings/index?Users.id=%s', h($user_id))
                ];
                return $linkData;
            }
            return [];
        });

        $this->bcf->addLink('Users', 'settings', 'Users', 'view', function ($config) use ($bcf, $request, $passedData) {
            if (!empty($passedData[0])) {
                $user_id = $passedData[0];
                $linkData = [
                    'label' => __('View user', h($user_id)),
                    'url' => sprintf('/users/view/%s', h($user_id))
                ];
                return $linkData;
            }
            return [];
        });
        $this->bcf->addSelfLink('Users', 'settings', [
            'label' => __('Account settings')
        ]);
    }
}
