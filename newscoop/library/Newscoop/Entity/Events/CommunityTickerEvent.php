<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\Entity\Events;

use Newscoop\Entity\User;

/**
 * @Entity(repositoryClass="Newscoop\Entity\Repository\Events\CommunityTickerEventRepository")
 * @Table(name="community_ticker_event")
 */
class CommunityTickerEvent
{
    /**
     * @Id @GeneratedValue
     * @Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @Column(type="string", length="80")
     * @var string
     */
    private $event;

    /**
     * @Column(type="text", nullable=True)
     * @var string
     */
    private $params;

    /**
     * @Column(type="datetime")
     * @var DateTime
     */
    private $created;

    /**
     * @ManyToOne(targetEntity="Newscoop\Entity\User")
     * @JoinColumn(referencedColumnName="Id")
     * @var Newscoop\Entity\User
     */
    private $user;

    /**
     */
    public function __construct()
    {
        $this->created = new \DateTime();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set event
     *
     * @param string $event
     * @return Newscoop\Entity\Events\CommunityTickerEvent
     */
    public function setEvent($event)
    {
        $this->event = $event;
        return $this;
    }

    /**
     * Get event
     *
     * @return string
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Set params
     *
     * @param array $params
     * @return Newscoop\Entity\Events\CommunityTickerEvent
     */
    public function setParams(array $params)
    {
        $this->params = json_encode($params);
        return $this;
    }

    /**
     * Get params
     *
     * @return array
     */
    public function getParams()
    {
        return !empty($this->params) ? json_decode($this->params, true) : array();
    }

    /**
     * Get created
     *
     * @return DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set user
     *
     * @param Newscoop\Entity\User $user
     * @return Newscoop\Entity\Events\CommunityTickerEvent
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Get user
     *
     * @return Newscoop\Entity\User|null
     */
    public function getUser()
    {
        return $this->user;
    }
}
