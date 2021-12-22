<?php
    echo $this->element('genericElements/Form/genericForm', [
        'data' => [
            'description' => __('Add connections to local tools via any of the available connectors below.'),
            'model' => 'LocalTools',
            'fields' => [
                [
                    'field' => 'connector',
                    'options' => $dropdownData['connectors'],
                    'type' => 'dropdown'
                ],
                [
                    'field' => 'name'
                ],
                [
                    'field' => 'exposed',
                    'type' => 'checkbox'
                ],
                [
                    'field' => 'settings',
                    'type' => 'codemirror',
                    'codemirror' => [
                        'height' => '10rem',
                        'hints' => $connectors[0]['connector_settings']
                    ]
                ],
                [
                    'field' => 'description',
                    'type' => 'textarea'
                ],
            ],
            'submit' => [
                'action' => $this->request->getParam('action')
            ],
            'url' => empty($redirect) ? null : $redirect
        ]
    ]);
?>
</div>
