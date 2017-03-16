<?php

namespace Ddeboer\Imap\Message;

/**
 * An e-mail attachment
 */
class Attachment extends Part
{
    /**
     * Get attachment filename
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->parameters->get('filename')
            ?: $this->parameters->get('name');
    }

    /**
     * Get attachment content-type
     *
     * @return string
     */
    public function getContentType()
    {
        return $this->getType()
            ? strtolower($this->getType() . '/' . $this->getSubtype())
            : null;
    }

    /**
     * Get attachment content-disposition
     *
     * @return string
     */
    public function getContentDisposition()
    {
        return $this->getDisposition();
    }

    /**
     * Get attachment file size
     *
     * @return int Number of bytes
     */
    public function getSize()
    {
        return $this->parameters->get('size');
    }
}
