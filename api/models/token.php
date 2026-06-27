<?php

class Token
{
    private $id;
    private $user_id;
    private $token;
    private $expires_at;
    private $revoked;
    private $created_at;

    public function getId()
    {
        return $this->id;
    }
    public function setId($v)
    {
        $this->id = $v;
    }

    public function getUserId()
    {
        return $this->user_id;
    }
    public function setUserId($v)
    {
        $this->user_id = $v;
    }

    public function getToken()
    {
        return $this->token;
    }
    public function setToken($v)
    {
        $this->token = $v;
    }

    public function getExpiresAt()
    {
        return $this->expires_at;
    }
    public function setExpiresAt($v)
    {
        $this->expires_at = $v;
    }

    public function getRevoked()
    {
        return $this->revoked;
    }
    public function setRevoked($v)
    {
        $this->revoked = $v;
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
