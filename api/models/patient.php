<?php

class Patient
{
    private $id;
    private $full_name;
    private $identifier;
    private $email;
    private $birth_date;
    private $user_type;
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

    public function getEmail()
    {
        return $this->email;
    }
    public function setEmail($v)
    {
        $this->email = $v;
    }

    public function getBirthDate()
    {
        return $this->birth_date;
    }
    public function setBirthDate($v)
    {
        $this->birth_date = $v;
    }

    public function getUserType()
    {
        return $this->user_type;
    }
    public function setUserType($v)
    {
        $this->user_type = $v;
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
