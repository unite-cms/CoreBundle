<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 19.09.18
 * Time: 15:16
 */

namespace UniteCMS\CoreBundle\Model;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * We use this model only for validation!
 */
class StateTransition
{
    /**
     * @var string
     * @Assert\Type(type="string", message="invalid_transitions")
     * @Assert\NotBlank(message="invalid_transitions")
     */
    private $identifier;

    /**
     * @var string
     * @Assert\Type(type="string", message="invalid_transitions")
     * @Assert\NotBlank(message="invalid_transitions")
     */
    private $label;

    /**
     * @var array
     * @Assert\Type(type="array", message="invalid_transition_from")
     * @Assert\NotBlank(message="invalid_transition_from")
     */
    private $froms;

    /**
     * @var string
     * @Assert\Type(type="string", message="invalid_transition_to")
     * @Assert\NotBlank(message="invalid_transition_to")
     */
    private $to;

    public function __construct($identifier, $label, $froms, $to)
    {
        $this->identifier = $identifier;
        $this->label = $label;
        $this->froms = $froms;
        $this->to = $to;
    }

    /**
     * Get identifier
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     *
     * @return StateTransition
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     *
     * @return StateTransition
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return array
     */
    public function getFroms()
    {
        return $this->froms;
    }

    /**
     * @param array $froms
     * 
     * @return StateTransition
     */
    public function setFroms($froms)
    {
        $this->froms = $froms;

        return $this;
    }

    /**
     * @return string
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * @param string $to
     *
     * @return StateTransition
     */
    public function setTo($to)
    {
        $this->to = $to;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return ''.$this->label;
    }

}