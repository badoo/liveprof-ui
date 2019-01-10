<?php declare(strict_types=1);

/**
 * A page with a list of last snapshots
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\Pages;

use Badoo\LiveProfilerUI\DataProviders\Interfaces\SourceInterface;
use Badoo\LiveProfilerUI\DataProviders\Interfaces\SnapshotInterface;
use Badoo\LiveProfilerUI\FieldList;
use Badoo\LiveProfilerUI\Interfaces\ViewInterface;

class ProfileListPage extends BasePage
{
    /** @var string */
    protected static $template_path = 'profile_list';
    /** @var SourceInterface */
    protected $Source;
    /** @var SnapshotInterface */
    protected $Snapshot;
    /** @var FieldList */
    protected $FieldList;

    public function __construct(
        ViewInterface $View,
        SourceInterface $Source,
        SnapshotInterface $Snapshot,
        FieldList $FieldList
    ) {
        $this->View = $View;
        $this->Source = $Source;
        $this->Snapshot = $Snapshot;
        $this->FieldList = $FieldList;
    }

    protected function cleanData() : bool
    {
        $this->data['app'] = isset($this->data['app']) ? trim($this->data['app']) : '';
        $this->data['label'] = isset($this->data['label']) ? trim($this->data['label']) : '';
        $this->data['date'] = isset($this->data['date']) ? trim($this->data['date']) : '';

        return true;
    }

    public function getTemplateData() : array
    {
        $snapshots = $this->Snapshot->getList($this->data['app']);

        $fields = $this->FieldList->getFields();
        $field_descriptions = $this->FieldList->getFieldDescriptions();

        $apps = $this->Snapshot->getAppList();

        $source_labels = $this->Source->getLabelList();
        $source_apps = $this->Source->getAppList();

        return [
            'app' => $this->data['app'],
            'label' => $this->data['label'],
            'date' => $this->data['date'],
            'apps' => $apps,
            'source_apps' => $source_apps,
            'source_labels' => $source_labels,
            'results' => $snapshots,
            'fields' => $fields,
            'field_descriptions' => $field_descriptions,
        ];
    }
}
