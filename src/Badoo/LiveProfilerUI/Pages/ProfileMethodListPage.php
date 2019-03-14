<?php declare(strict_types=1);

/**
 * A page with a list of all methods of the snapshot
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\Pages;

use Badoo\LiveProfilerUI\DataProviders\Interfaces\MethodInterface;
use Badoo\LiveProfilerUI\DataProviders\Interfaces\MethodDataInterface;
use Badoo\LiveProfilerUI\DataProviders\Interfaces\MethodTreeInterface;
use Badoo\LiveProfilerUI\DataProviders\Interfaces\SnapshotInterface;
use Badoo\LiveProfilerUI\FieldList;
use Badoo\LiveProfilerUI\Interfaces\ViewInterface;

class ProfileMethodListPage extends BasePage
{
    /** @var string */
    protected static $template_path = 'profile_method_list';
    /** @var SnapshotInterface */
    protected $Snapshot;
    /** @var MethodInterface */
    protected $Method;
    /** @var MethodTreeInterface */
    protected $MethodTree;
    /** @var MethodDataInterface */
    protected $MethodData;
    /** @var FieldList */
    protected $FieldList;
    /** @var string */
    protected $calls_count_field = '';

    public function __construct(
        ViewInterface $View,
        SnapshotInterface $Snapshot,
        MethodInterface $Method,
        MethodTreeInterface $MethodTree,
        MethodDataInterface $MethodData,
        FieldList $FieldList,
        string $calls_count_field
    ) {
        $this->View = $View;
        $this->Snapshot = $Snapshot;
        $this->Method = $Method;
        $this->MethodTree = $MethodTree;
        $this->MethodData = $MethodData;
        $this->FieldList = $FieldList;
        $this->calls_count_field = $calls_count_field;
    }

    protected function cleanData() : bool
    {
        $this->data['app'] = isset($this->data['app']) ? trim($this->data['app']) : '';
        $this->data['label'] = isset($this->data['label']) ? trim($this->data['label']) : '';
        $this->data['snapshot_id'] = isset($this->data['snapshot_id']) ? (int)$this->data['snapshot_id'] : 0;
        $this->data['all'] = isset($this->data['all']) ? (bool)$this->data['all'] : false;

        if (!$this->data['snapshot_id'] && (!$this->data['app'] || !$this->data['label'])) {
            throw new \InvalidArgumentException('Empty snapshot_id, app and label');
        }

        return true;
    }

    /**
     * @return array
     * @throws \InvalidArgumentException
     */
    public function getTemplateData() : array
    {
        $link_base = '/profiler/tree-view.phtml?';
        $Snapshot = false;
        if ($this->data['snapshot_id']) {
            $Snapshot = $this->Snapshot->getOneById($this->data['snapshot_id']);
            $link_base .= 'snapshot_id=' . $this->data['snapshot_id'];
        } elseif ($this->data['app'] && $this->data['label']) {
            $Snapshot = $this->Snapshot->getOneByAppAndLabel($this->data['app'], $this->data['label']);
            $link_base .= 'app=' . urlencode($this->data['app']) . '&label=' . urlencode($this->data['label']);
        }

        if (empty($Snapshot)) {
            throw new \InvalidArgumentException('Can\'t get snapshot');
        }

        $all_fields = $this->data['all'] ?
            $this->FieldList->getAllFieldsWithVariations() :
            $this->FieldList->getFields();
        $fields = array_diff($this->FieldList->getFields(), [$this->calls_count_field]);
        $field_descriptions = $this->FieldList->getFieldDescriptions();

        $parents_data = $this->MethodTree->getSnapshotParentsData([$Snapshot->getId()]);
        $records = $this->MethodData->getDataBySnapshotId($Snapshot->getId());
        $records = $this->Method->injectMethodNames($records);

        foreach ($records as $Row) {
            /** @var \Badoo\LiveProfilerUI\Entity\MethodData $Row */
            $values = $Row->getValues();
            $values = array_intersect_key($values, array_flip($all_fields));

            foreach ($fields as $field) {
                $all_fields[$field . '_excl'] = $field . '_excl';
                $values[$field . '_excl'] = !empty($parents_data[$Row->getSnapshotId()][$Row->getMethodId()])
                    ? ($values[$field] - $parents_data[$Row->getSnapshotId()][$Row->getMethodId()][$field])
                    : 0;
            }
            $Row->setValues($values);
        }

        $this->sortList($records);

        $wall = $this->View->fetchFile(
            'profiler_result_view_part',
            [
                'data' => $records,
                'fields' => $all_fields,
                'field_descriptions' => $field_descriptions,
                'link_base' => $link_base
            ],
            false
        );

        return [
            'snapshot_id' => $Snapshot->getId(),
            'snapshot_app' => $Snapshot->getApp(),
            'snapshot_label' => $Snapshot->getLabel(),
            'snapshot_date' => $Snapshot->getDate(),
            'wall' => $wall,
            'all' => $this->data['all'],
        ];
    }

    protected function sortList(array &$records)
    {
        $sort_field = (string)current($this->FieldList->getFields());
        usort($records, function ($Element1, $Element2) use ($sort_field) : int {
            /** @var \Badoo\LiveProfilerUI\Entity\MethodData $Element1 */
            /** @var \Badoo\LiveProfilerUI\Entity\MethodData $Element2 */
            return $Element2->getValue($sort_field) > $Element1->getValue($sort_field) ? 1 : -1;
        });
    }
}
