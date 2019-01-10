<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace Badoo\LiveProfilerUI\DataProviders;

use Badoo\LiveProfilerUI\Interfaces\StorageInterface;
use Badoo\LiveProfilerUI\FieldList;

class Base
{
    /** @var StorageInterface */
    protected $AggregatorStorage;
    /** @var FieldList */
    protected $FieldList;

    public function __construct(StorageInterface $AggregatorStorage, FieldList $FieldList)
    {
        $this->AggregatorStorage = $AggregatorStorage;
        $this->FieldList = $FieldList;
    }
}
