<?php

class Rol
{
    private $id;
    private $name;
    private $created_at;
    private $updated_at;

    public function getId()
    {
        return $this->id;
    }
    public function setId($v)
    {
        $this->id = $v;
    }

    public function getName()
    {
        return $this->name;
    }
    public function setName($v)
    {
        $this->name = $v;
    }

    public function getCreatedAt()
    {
        return $this->created_at;
    }
    public function setCreatedAt($v)
    {
        $this->created_at = $v;
    }

    public function getUpdatedAt()
    {
        return $this->updated_at;
    }
    public function setUpdatedAt($v)
    {
        $this->updated_at = $v;
    }
}
