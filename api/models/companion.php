<?php

class Companion
{
    private $id;
    private $full_name;
    private $identifier;
    private $phone;
    private $created_at;

    public function getId()
    {
        return $this->id;
    }
    public function setId($v)
    {
        $this->id = $v;
    }

    public function getFullName()
    {
        return $this->full_name;
    }
    public function setFullName($v)
    {
        $this->full_name = $v;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }
    public function setIdentifier($v)
    {
        $this->identifier = $v;
    }

    public function getPhone()
    {
        return $this->phone;
    }
    public function setPhone($v)
    {
        $this->phone = $v;
    }

    public function getCreatedAt()
    {
        return $this->created_at;
    }
    public function setCreatedAt($v)
    {
        $this->created_at = $v;
    }
}
