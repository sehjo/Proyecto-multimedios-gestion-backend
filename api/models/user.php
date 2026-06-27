<?php

class User
{
    private $id;
    private $name;
    private $identifier;
    private $email;
    private $password;
    private $status;
    private $is_guest;
    private $created_at;
    private $updated_at;
    private $deleted_at;
    private $roles = [];

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

    public function getPassword()
    {
        return $this->password;
    }
    public function setPassword($v)
    {
        $this->password = $v;
    }

    public function getStatus()
    {
        return $this->status;
    }
    public function setStatus($v)
    {
        $this->status = $v;
    }

    public function getIsGuest()
    {
        return $this->is_guest;
    }
    public function setIsGuest($v)
    {
        $this->is_guest = $v;
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

    public function getDeletedAt()
    {
        return $this->deleted_at;
    }
    public function setDeletedAt($v)
    {
        $this->deleted_at = $v;
    }

    public function getRoles()
    {
        return $this->roles;
    }
    public function setRoles($v)
    {
        $this->roles = $v;
    }
}
