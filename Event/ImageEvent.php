<?php

namespace ResponsiveImageBundle\Event;


use ResponsiveImageBundle\Utils\ResponsiveImageInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class EnquiryEvent
 *
 * @package ResponsiveImageBundle\Event
 */
class ImageEvent extends Event {

    /**
     * @var
     */
    protected $image;

    /**
     * @var
     */
    protected $style;

    /**
     * EnquiryEvent constructor.
     * 
     * @param ResponsiveImageInterface $image
     */
    public function __construct(ResponsiveImageInterface $image)
    {
        $this->image = $image;
    }

    /**
     * @return mixed
     */
    public function getImage()
    {
        return $this->image;
    }
}