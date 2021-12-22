<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Event\EventInterface;
use Cake\Utility\Text;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Error\Debugger;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link https://book.cakephp.org/4/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{

    public $isRest = null;
    public $restResponsePayload = null;
    public $user = null;
    public $breadcrumb = [];

    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('FormProtection');`
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('RequestHandler');
        $this->loadComponent('Flash');
        $this->loadComponent('RestResponse');
        $this->loadComponent('Security');
        $this->loadComponent('ParamHandler', [
            'request' => $this->request
        ]);
        $this->loadModel('MetaFields');
        $this->loadModel('MetaTemplates');
        $table = $this->getTableLocator()->get($this->modelClass);
        $this->loadComponent('CRUD', [
            'request' => $this->request,
            'table' => $table,
            'MetaFields' => $this->MetaFields,
            'MetaTemplates' => $this->MetaTemplates
        ]);
        $this->loadComponent('Authentication.Authentication');
        $this->loadComponent('ACL', [
            'request' => $this->request,
            'Authentication' => $this->Authentication
        ]);
        $this->loadComponent('Navigation', [
            'request' => $this->request,
        ]);
        if (Configure::read('debug')) {
            Configure::write('DebugKit.panels', ['DebugKit.Packages' => true]);
            Configure::write('DebugKit.forceEnable', true);
        }
        $this->loadComponent('CustomPagination');
        /*
         * Enable the following component for recommended CakePHP form protection settings.
         * see https://book.cakephp.org/4/en/controllers/components/form-protection.html
         */
        //$this->loadComponent('FormProtection');
    }

    public function beforeFilter(EventInterface $event)
    {
        $this->loadModel('Users');
        $this->Users->checkForNewInstance();
        $this->authApiUser();
        if ($this->ParamHandler->isRest()) {
            $this->Security->setConfig('unlockedActions', [$this->request->getParam('action')]);
        }
        $this->ACL->setPublicInterfaces();
        if (!empty($this->request->getAttribute('identity'))) {
            $user = $this->Users->get($this->request->getAttribute('identity')->getIdentifier(), [
                'contain' => ['Roles', 'Individuals' => 'Organisations', 'UserSettings', 'Organisations']
            ]);
            if (!empty($user['disabled'])) {
                $this->Authentication->logout();
                $this->Flash->error(__('The user account is disabled.'));
                return $this->redirect(\Cake\Routing\Router::url('/users/login'));
            }
            unset($user['password']);
            $this->ACL->setUser($user);
            $this->request->getSession()->write('authUser', $user);
            $this->isAdmin = $user['role']['perm_admin'];
            if (!$this->ParamHandler->isRest()) {
                $this->set('menu', $this->ACL->getMenu());
                $this->set('loggedUser', $this->ACL->getUser());
                $this->set('roleAccess', $this->ACL->getRoleAccess(false, false));
            }
        } else if ($this->ParamHandler->isRest()) {
            throw new MethodNotAllowedException(__('Invalid user credentials.'));
        }

        if ($this->request->getParam('action') === 'index') {
            $this->Security->setConfig('validatePost', false);
        }
        $this->Security->setConfig('unlockedActions', ['index']);
        if ($this->ParamHandler->isRest()) {
            $this->Security->setConfig('unlockedActions', [$this->request->getParam('action')]);
            $this->Security->setConfig('validatePost', false);
        }

        $this->ACL->checkAccess();
        if (!$this->ParamHandler->isRest()) {
            $this->set('breadcrumb', $this->Navigation->getBreadcrumb());
            $this->set('ajax', $this->request->is('ajax'));
            $this->request->getParam('prefix');
            $this->set('baseurl', Configure::read('App.fullBaseUrl'));
            if (!empty($user) && !empty($user->user_settings_by_name['ui.bsTheme']['value'])) {
                $this->set('bsTheme', $user->user_settings_by_name['ui.bsTheme']['value']);
            } else {
                $this->set('bsTheme', Configure::check('ui.bsTheme') ? Configure::read('ui.bsTheme') : 'default');
            }

            if ($this->modelClass == 'Tags.Tags') {
                $this->set('metaGroup', !empty($this->isAdmin) ? 'Administration' : 'Cerebrate');
            }
        }
    }

    private function authApiUser(): void
    {
        if (!empty($_SERVER['HTTP_AUTHORIZATION']) && strlen($_SERVER['HTTP_AUTHORIZATION'])) {
            $this->loadModel('AuthKeys');
            $logModel = $this->Users->auditLogs();
            $authKey = $this->AuthKeys->checkKey($_SERVER['HTTP_AUTHORIZATION']);
            if (!empty($authKey)) {
                $this->loadModel('Users');
                $user = $this->Users->get($authKey['user_id']);
                $logModel->insert([
                    'request_action' => 'login',
                    'model' => 'Users',
                    'model_id' => $user['id'],
                    'model_title' => $user['username'],
                    'changed' => []
                ]);
                if (!empty($user)) {
                    $this->Authentication->setIdentity($user);
                }
            } else {
                $user = $logModel->userInfo();
                $logModel->insert([
                    'request_action' => 'login',
                    'model' => 'Users',
                    'model_id' => $user['id'],
                    'model_title' => $user['name'],
                    'changed' => []
                ]);
            }
        }
    }

    public function generateUUID()
    {
        $uuid = Text::uuid();
        return $this->RestResponse->viewData(['uuid' => $uuid], 'json');
    }

    public function queryACL()
    {
        return $this->RestResponse->viewData($this->ACL->findMissingFunctionNames());
    }

    public function getRoleAccess()
    {
        return $this->RestResponse->viewData($this->ACL->getRoleAccess(false, false));
    }
}
