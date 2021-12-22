<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use Cake\ORM\TableRegistry;
use \Cake\Database\Expression\QueryExpression;
use Cake\Http\Exception\UnauthorizedException;
use Cake\Core\Configure;

class AuditLogsController extends AppController
{
    public $filterFields = ['model_id', 'model', 'request_action', 'user_id', 'title'];
    public $quickFilterFields = ['model', 'request_action', 'title'];
    public $containFields = ['Users'];

    public function index()
    {
        $this->CRUD->index([
            'contain' => $this->containFields,
            'filters' => $this->filterFields,
            'quickFilters' => $this->quickFilterFields,
            'afterFind' => function($data) {
                $data['request_ip'] = inet_ntop(stream_get_contents($data['request_ip']));
                $data['changed'] = stream_get_contents($data['changed']);
                return $data;
            }
        ]);
        $responsePayload = $this->CRUD->getResponsePayload();
        if (!empty($responsePayload)) {
            return $responsePayload;
        }
        $this->set('metaGroup', 'Administration');
    }
}
