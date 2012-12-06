<?php
class NonCompliantPerson
{
    protected $name;

    public function __construct($name)
    {
        if (empty($name))
        {
            $name = '';
        }

        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}