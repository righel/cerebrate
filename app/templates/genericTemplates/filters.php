<?php

use Cake\Utility\Inflector;

$tableItems = array_map(function ($fieldName) {
    return [
        'fieldname' => $fieldName,
    ];
}, $filters);

$filteringForm = $this->Bootstrap->table(
    [
        'small' => true,
        'striped' => false,
        'hover' => false,
        'tableClass' => ['indexFilteringTable'],
    ],
    [
        'fields' => [
            [
                'key' => 'fieldname', 'label' => __('Field'), 'formatter' => function ($field, $row) {
                    return sprintf('<span class="fieldName" data-fieldname="%s">%s</span>', h($field), h($field));
                }
            ],
            [
                'key' => 'operator', 'label' => __('Operator'), 'formatter' => function ($field, $row) {
                    $options = [
                        sprintf('<option value="%s">%s</option>', '=', '='),
                        sprintf('<option value="%s">%s</option>', '!=', '!='),
                    ];
                    return sprintf('<select class="fieldOperator form-select form-select-sm">%s</select>', implode('', $options));
                }
            ],
            [
                'key' => 'value',
                'labelHtml' => sprintf(
                    '%s %s',
                    __('Value'),
                    sprintf('<sup class="fa fa-info" title="%s"><sup>', __('Supports strict matches and LIKE matches with the `%` character.&#10;Example: `%.com`'))
                ),
                'formatter' => function ($field, $row) {
                    return sprintf('<input type="text" class="fieldValue form-control form-control-sm">');
                }
            ],
        ],
        'items' => $tableItems
    ]
);

if ($taggingEnabled) {
    $helpText = $this->Bootstrap->genNode('sup', [
        'class' => ['ms-1 fa fa-info'],
        'title' => __('Supports negation matches (with the `!` character) and LIKE matches (with the `%` character).&#10;Example: `!exportable`, `%able`'),
        'data-bs-toggle' => 'tooltip',
    ]);
    $filteringTags = $this->Bootstrap->genNode('h5', [], __('Tags') . $helpText);
    $filteringTags .= $this->Tag->tags([], [
        'allTags' => $allTags,
        'picker' => true,
        'editable' => false,
    ]);
} else {
    $filteringTags = '';
}
$modalBody = sprintf('%s%s', $filteringForm, $filteringTags);

echo $this->Bootstrap->modal([
    'title' => __('Filtering options for {0}', Inflector::singularize($this->request->getParam('controller'))),
    'size' => 'lg',
    'type' => 'confirm',
    'bodyHtml' => $modalBody,
    'confirmText' => __('Filter'),
    'confirmFunction' => 'filterIndex'
]);
?>

<script>
    $(document).ready(() => {
        const $filteringTable = $('table.indexFilteringTable')
        initFilteringTable($filteringTable)
    })

    function filterIndex(modalObject, tmpApi) {
        const controller = '<?= $this->request->getParam('controller') ?>';
        const action = 'index';
        const $tbody = modalObject.$modal.find('table.indexFilteringTable tbody')
        const $rows = $tbody.find('tr:not(#controlRow)')
        const activeFilters = {}
        $rows.each(function() {
            const rowData = getDataFromRow($(this))
            let fullFilter = rowData['name']
            if (rowData['operator'] == '!=') {
                fullFilter += ' !='
            }
            if (rowData['value'].length > 0) {
                activeFilters[fullFilter] = rowData['value']
            }
        })
        $select = modalObject.$modal.find('select.tag-input')
        activeFilters['filteringTags'] = $select.select2('data').map(tag => tag.text)
        const searchParam = jQuery.param(activeFilters);
        const url = `/${controller}/${action}?${searchParam}`

        const randomValue = getRandomValue()
        UI.reload(url, $(`#table-container-${randomValue}`), $(`#table-container-${randomValue} table.table`), [{
            node: $(`#toggleFilterButton-${randomValue}`),
            config: {}
        }])
    }

    function initFilteringTable($filteringTable) {
        const $controlRow = $filteringTable.find('#controlRow')
        const randomValue = getRandomValue()
        const activeFilters = Object.assign({}, $(`#toggleFilterButton-${randomValue}`).data('activeFilters'))
        const tags = activeFilters['filteringTags'] !== undefined ? Object.assign({}, activeFilters)['filteringTags'] : []
        delete activeFilters['filteringTags']
        for (let [field, value] of Object.entries(activeFilters)) {
            const fieldParts = field.split(' ')
            let operator = '='
            if (fieldParts.length == 2 && fieldParts[1] == '!=') {
                operator = '!='
                field = fieldParts[0]
            } else if (fieldParts.length > 2) {
                console.error('Field contains multiple spaces. ' + field)
            }
            setFilteringValues($filteringTable, field, value, operator)
        }
        $select = $filteringTable.closest('.modal-body').find('select.tag-input')
        let passedTags = []
        tags.forEach(tagname => {
            const existingOption = $select.find('option').filter(function() {
                return $(this).val() === tagname
            })
            if (existingOption.length == 0) {
                passedTags.push(new Option(tagname, tagname, true, true))
            }
        })
        $select
            .append(passedTags)
            .val(tags)
            .trigger('change')
    }

    function setFilteringValues($filteringTable, field, value, operator) {
        $row = $filteringTable.find('td > span.fieldName').filter(function() {
            return $(this).data('fieldname') == field
        }).closest('tr')
        $row.find('.fieldOperator').val(operator)
        $row.find('.fieldValue').val(value)
    }

    function getDataFromRow($row) {
        const rowData = {};
        rowData['name'] = $row.find('td > span.fieldName').data('fieldname')
        rowData['operator'] = $row.find('select.fieldOperator').val()
        rowData['value'] = $row.find('input.fieldValue').val()
        return rowData
    }

    function getRandomValue() {
        const $container = $('div[id^="table-container-"]')
        const randomValue = $container.attr('id').split('-')[2]
        return randomValue
    }
</script>