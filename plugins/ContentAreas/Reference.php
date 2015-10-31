<?php

namespace phpList\plugin\ContentAreas;

/**
 * Class to provide a reference for a content area.
 */
class Reference
{
    /** @var string name of the repeat */
    public $repeat = null;

    /** @var int instance of the repeat */
    public $instance = null;

    /** @var string name of the content area */
    public $name = null;

    /**
     * Assign object properties. The method has three signatures:
     *  assign($name)
     *  assign($repeat, $instance)
     *  assign($repeat, $instance, $name).
     *
     * @param   mixed
     *
     * @return none
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

    public function __construct()
    {
        if (func_num_args() > 0) {
            call_user_func_array(array($this, 'assign'), func_get_args());
        }
    }

    /**
     * Serialise the reference.
     *
     * @param   none
     *
     * @return string Either the name or the concatenation of repeat, instance and name
     */
    public function __toString()
    {
        $r = $this->repeat
            ? "$this->repeat,$this->instance,$this->name"
            : $this->name;

        return $r;
    }

    /**
     * Convert the reference to a value to be used as an html id by removing invalid
     * characters.
     *
     * @param   none
     *
     * @return string id value
     */
    public function toId()
    {
        return preg_replace('/[^0-9A-Za-z\-_:.]/', '', $this);
    }

    /**
     * Deserialise the reference.
     *
     * @param string $p The stringified reference
     *
     * @return Reference
     */
    public static function decode($p)
    {
        $ref = new self();
        call_user_func_array(array($ref, 'assign'), explode(',', $p));

        return $ref;
    }
}
