<?php

namespace Ang3\Component\Serializer\Normalizer\Tests\Model;

use DateTime;

/**
 * @author Joanis ROUANET
 */
class TestRecord
{
    /**
     * Record constants.
     */
    const FOO = 'bar';
    const BAZ = 'qux';

    /**
     * @var mixed
     */
    public $id;

    /**
     * @var mixed
     */
    private $type;

    /**
     * @var DateTime
     */
    private $date;

    /**
     * @param mixed $id
     */
    public function setId($id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $type
     */
    public function setType($type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    public function setDate(DateTime $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getDate(): DateTime
    {
        return $this->date;
    }
}
