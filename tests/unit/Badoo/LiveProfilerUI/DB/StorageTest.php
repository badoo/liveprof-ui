<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI\DB\Adapters;

class StorageTest extends \unit\Badoo\BaseTestCase
{
    /** @var \Badoo\LiveProfilerUI\DB\Storage */
    protected $Storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->Storage = new \Badoo\LiveProfilerUI\DB\Storage('sqlite:///:memory:');
    }

    public function providerGetSelectQueryBuilder() : array
    {
        return [
            [
                'fields' => ['all'],
                'params' => [],
                'expected' => 'SELECT * FROM details'
            ],
            [
                'fields' => ['a', ['field' => 'b']],
                'params' => [],
                'expected' => 'SELECT a,b b FROM details'
            ],
            [
                'fields' => ['all'],
                'params' => ['filter' => [['a', 1]]],
                'expected' => 'SELECT * FROM details WHERE a = :dcValue1'
            ],
            [
                'fields' => [['field' => 'a', 'function' => 'sum', 'alias' => 'sum_a']],
                'params' => [
                    'group' => ['a'],
                    'order' => ['b' => 'desc'],
                    'having' => [['sum_a', 1, '>'], ['sum_a', 0]],
                    'limit' => 10
                ],
                'expected' => 'SELECT sum(a) sum_a FROM details GROUP BY a HAVING (sum_a > :dcValue1) AND (sum_a = :dcValue2) ORDER BY b desc LIMIT 10'
            ],
        ];
    }

    /**
     * @dataProvider providerGetSelectQueryBuilder
     * @param $fields
     * @param $params
     * @param $expected
     * @throws \ReflectionException
     */
    public function testGetSelectQueryBuilder(array $fields, array $params, string $expected)
    {
        $table = 'details';
        $QueryBuilder = $this->invokeMethod($this->Storage, 'getSelectQueryBuilder', [$table, $fields, $params]);

        self::assertEquals($expected, $QueryBuilder->getSql());
    }

    public function testInsertError()
    {
        $this->expectException(\Badoo\LiveProfilerUI\Exceptions\DatabaseException::class);
        $this->expectExceptionMessage('Can\'t insert into details');
        $this->Storage->insert('details', ['a' => 1]);
    }

    public function testInsertManyError()
    {
        $this->expectException(\Badoo\LiveProfilerUI\Exceptions\DatabaseException::class);
        $this->expectExceptionMessage('Can\'t insert into details');
        $this->Storage->insertMany('details', [['a' => 1]]);
    }

    public function testInsertManyEmptyData()
    {
        $this->expectException(\Badoo\LiveProfilerUI\Exceptions\InvalidFieldValueException::class);
        $this->expectExceptionMessage('Can\'t insert empty data');
        $this->Storage->insertMany('details', []);
    }

    public function testInsertManyEmptyOneOfData()
    {
        $this->expectException(\Badoo\LiveProfilerUI\Exceptions\InvalidFieldValueException::class);
        $this->expectExceptionMessage('Can\'t insert empty data');
        $this->Storage->insertMany('details', [[]]);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function testMultiQuery()
    {
        $sql = "SELECT 1;\n\nSELECT 2;";
        $result = $this->Storage->multiQuery($sql);

        self::assertTrue($result);
    }

    public function testDeleteEmptyData()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Can\'t delete without any conditions');
        $this->Storage->delete('details', []);
    }

    public function testUpdateEmptyParams()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Can\'t update without any conditions');
        $this->Storage->update('details', ['a' => 1], []);
    }

    public function testUpdateEmptyFields()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Can\'t update without any fields');
        $this->Storage->update('details', [], ['a' => 1]);
    }

    public function testQueryError()
    {
         $result = $this->Storage->query('SELECT * FROM t;');

         self::assertFalse($result);
    }
}
