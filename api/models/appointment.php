<?php

class Cita
{
    private $id;
    private $accepted_by_id;
    private $patient_id;
    private $attention_area;
    private $reason;
    private $appointment_datetime;
    private $status;
    private $cancellation_reason;
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

    public function getAcceptedById()
    {
        return $this->accepted_by_id;
    }
    public function setAcceptedById($v)
    {
        $this->accepted_by_id = $v;
    }

    public function getPatientId()
    {
        return $this->patient_id;
    }
    public function setPatientId($v)
    {
        $this->patient_id = $v;
    }

    public function getAttentionArea()
    {
        return $this->attention_area;
    }
    public function setAttentionArea($v)
    {
        $this->attention_area = $v;
    }

    public function getReason()
    {
        return $this->reason;
    }
    public function setReason($v)
    {
        $this->reason = $v;
    }

    public function getAppointmentDatetime()
    {
        return $this->appointment_datetime;
    }
    public function setAppointmentDatetime($v)
    {
        $this->appointment_datetime = $v;
    }

    public function getStatus()
    {
        return $this->status;
    }
    public function setStatus($v)
    {
        $this->status = $v;
    }

    public function getCancellationReason()
    {
        return $this->cancellation_reason;
    }
    public function setCancellationReason($v)
    {
        $this->cancellation_reason = $v;
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
