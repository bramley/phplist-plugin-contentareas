<?php

namespace phpList\plugin\ContentAreas;

/**
 * Class to provide a reference for a content area
 *
 */
class Reference
{
/*
 *  Public variables
 */
    public $repeat = null;
    public $instance = null;
    public $name = null;

/*
 *  Private functions
 */

    /**
     * Assign object properties. The method has three signatures:
     *  assign($name)
     *  assign($repeat, $instance)
     *  assign($repeat, $instance, $name)
     *
     * @access  private
     * @param   mixed
     * @return  none
     */
    private function assign()
    {
        $args = func_get_args();

        if (count($args) == 1) {
            $this->name = $args[0];
            return;
        }
        $this->repeat = $args[0];
        $this->instance = $args[1];

        if (count($args) == 3) {
            $this->name = $args[2];
        }
    }

/*
 *  Public functions
 */
    public function __construct()
    {
        if (func_num_args() > 0) {
            call_user_func_array(array($this, 'assign'), func_get_args());
        }
    }

    /**
     * Serialise the reference
     *
     * @access  public
     * @param   none
     * @returns string  Either the name or the concatenation of repeat, instance and name
     * @return  none
     */
    public function __toString()
    {
        $r = $this->repeat
            ? "$this->repeat,$this->instance,$this->name"
            : $this->name;
        return $r;
    }

    /**
     * Deserialise the reference
     *
     * @access  public
     * @param   string  $p  The stringified reference
     * @returns Reference
     * @return  none
     */
    public static function decode($p)
    {
        $ref = new self;
        call_user_func_array(array($ref, 'assign'), explode(',', $p));
        return $ref;
    }
}
