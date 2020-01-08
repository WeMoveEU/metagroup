<?php
namespace KenSh\MetabaseApi\Common;

use stdClass;

/**
 * @method Result getVisualizationSettings()
 * @method Result getColumnSettings()
 * @method Result getTableColumns()
 */
class Result
{
    /**
     * @var stdClass
     */
    private $object;
    /**
     * @var stdClass
     */
    private $rootObject;

    public function __construct(stdClass $object)
    {
        $this->object = $object;
        $this->rootObject = $this->object;
    }

    /**
     * @param $name
     * @param $arguments
     * @return Result
     */
    public function __call($name, $arguments)
    {
        if ($arguments['root']) {
            $this->object = $this->rootObject;
        }


        $normalizedProperty = $this->underscore(str_replace('get', '', $name));

        if (isset($this->object->{$normalizedProperty})) {
            $this->object = $this->object->{$normalizedProperty};
        } elseif (isset($this->object->{str_replace('_', '.', $normalizedProperty)})) {
            $this->object = $this->object->{str_replace('_', '.', $normalizedProperty)};
        }

        if (is_array($this->object)) {
            foreach ($this->object as &$obj) {
                $obj = new Result($obj);
            }
        }

        return $this;
    }

    /**
     * @return stdClass
     */
    public function value()
    {
        return $this->object;
    }

    /**
     * @param $camelCase
     * @return string
     */
    private function underscore($camelCase) {
        return implode(
            '_',
            array_map(
                'strtolower',
                preg_split('/([A-Z]{1}[^A-Z]*)/', $camelCase, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY)));
    }
}