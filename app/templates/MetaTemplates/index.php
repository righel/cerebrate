<?php
echo $this->element('genericElements/IndexTable/index_table', [
    'data' => [
        'data' => $data,
        'top_bar' => [
            'children' => [
                [
                    'type' => 'context_filters',
                    'context_filters' => $filteringContexts
                ],
                [
                    'type' => 'search',
                    'button' => __('Filter'),
                    'placeholder' => __('Enter value to search'),
                    'data' => '',
                    'searchKey' => 'value'
                ]
            ]
        ],
        'fields' => [
            [
                'name' => '#',
                'sort' => 'id',
                'data_path' => 'id',
            ],
            [
                'name' => 'Enabled',
                'sort' => 'enabled',
                'data_path' => 'enabled',
                'element' => 'toggle',
                'url' => '/metaTemplates/toggle/{{0}}',
                'url_params_vars' => ['id'],
                'toggle_data' => [
                    'editRequirement' => [
                        'function' => function($row, $options) {
                            return true;
                        },
                    ],
                    'skip_full_reload' => true
                ]
            ],
            [
                'name' => 'Default',
                'sort' => 'is_default',
                'data_path' => 'is_default',
                'element' => 'toggle',
                'url' => '/metaTemplates/toggle/{{0}}/{{1}}',
                'url_params_vars' => [['datapath' => 'id'], ['raw' => 'is_default']],
                'toggle_data' => [
                    'editRequirement' => [
                        'function' => function($row, $options) {
                            return true;
                        }
                    ],
                    'confirm' => [
                        'enable' => [
                            'titleHtml' => __('Make {{0}} the default template?'),
                            'bodyHtml' => $this->Html->nestedList([
                                __('Only one template per scope can be set as the default template'),
                                '{{0}}',
                            ]),
                            'type' => '{{0}}',
                            'confirmText' => __('Yes, set as default'),
                            'arguments' => [
                                'titleHtml' => ['name'],
                                'bodyHtml' => [
                                    [
                                        'function' => function($row, $data) {
                                            $conflictingTemplate = getConflictingTemplate($row, $data);
                                            if (!empty($conflictingTemplate)) {
                                                return sprintf(
                                                    "<span class=\"text-danger fw-bolder\">%s</span> %s.<br />
                                                    <ul><li><span class=\"fw-bolder\">%s</span> %s <span class=\"fw-bolder\">%s</span></li></ul>",
                                                    __('Conflict with:'),
                                                    $this->Html->link(
                                                        h($conflictingTemplate->name),
                                                        '/metaTemplates/view/' . h($conflictingTemplate->id),
                                                        ['target' => '_blank']
                                                    ),
                                                    __('By proceeding'),
                                                    h($conflictingTemplate->name),
                                                    __('will not be the default anymore')
                                                );
                                            }
                                            return __('Current scope: {0}', h($row->scope));
                                        },
                                        'data' => [
                                            'defaultTemplatePerScope' => $defaultTemplatePerScope
                                        ]
                                    ]
                                ],
                                'type' => [
                                    'function' => function($row, $data) {
                                        $conflictingTemplate = getConflictingTemplate($row, $data);
                                        if (!empty($conflictingTemplate)) {
                                            return 'confirm-danger';
                                        }
                                        return 'confirm-warning';
                                    },
                                    'data' => [
                                        'defaultTemplatePerScope' => $defaultTemplatePerScope
                                    ]
                                ]
                            ]
                        ],
                        'disable' => [
                            'titleHtml' => __('Remove {{0}} as the default template?'),
                            'type' => 'confirm-warning',
                            'confirmText' => __('Yes, do not set as default'),
                            'arguments' => [
                                'titleHtml' => ['name'],
                            ]
                        ]
                    ]
                ]
            ],
            [
                'name' => __('Scope'),
                'sort' => 'scope',
                'data_path' => 'scope',
            ],
            [
                'name' => __('Name'),
                'sort' => 'name',
                'data_path' => 'name',
            ],
            [
                'name' => __('Namespace'),
                'sort' => 'namespace',
                'data_path' => 'namespace',
            ],
            [
                'name' => __('UUID'),
                'sort' => 'uuid',
                'data_path' => 'uuid'
            ]
        ],
        'title' => __('Meta Field Templates'),
        'description' => __('The various templates used to enrich certain objects by a set of standardised fields.'),
        'pull' => 'right',
        'actions' => [
            [
                'url' => '/metaTemplates/view',
                'url_params_data_paths' => ['id'],
                'icon' => 'eye'
            ],
        ]
    ]
]);

function getConflictingTemplate($row, $data) {
    if (!empty($data['data']['defaultTemplatePerScope'][$row->scope])) {
        $conflictingTemplate = $data['data']['defaultTemplatePerScope'][$row->scope];
        if (!empty($conflictingTemplate)) {
            return $conflictingTemplate;
        }
    }
    return [];
}
?>
