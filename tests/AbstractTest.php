<?php
namespace Ddeboer\Imap\Tests;

use Ddeboer\Imap\Exception\MailboxDoesNotExistException;
use Ddeboer\Imap\Mailbox;
use Ddeboer\Imap\Server;
use Ddeboer\Imap\Connection;

abstract class AbstractTest extends \PHPUnit_Framework_TestCase
{
    protected static $connection;

    public static function setUpBeforeClass()
    {
        if (false === \getenv('EMAIL_SERVER')) {
            throw new \RuntimeException(
                'Please set environment variable EMAIL_SERVER before running functional tests'
            );
        }

        if (false === \getenv('EMAIL_USERNAME')) {
            throw new \RuntimeException(
                'Please set environment variable EMAIL_USERNAME before running functional tests'
            );
        }

        if (false === \getenv('EMAIL_PASSWORD')) {
            throw new \RuntimeException(
                'Please set environment variable EMAIL_PASSWORD before running functional tests'
            );
        }

        $server = new Server(\getenv('EMAIL_SERVER'));

        static::$connection = $server->authenticate(\getenv('EMAIL_USERNAME'), \getenv('EMAIL_PASSWORD'));
    }

    /**
     * @return Connection
     */
    protected static function getConnection()
    {
        return static::$connection;
    }

    /**
     * Create a mailbox
     *
     * If the mailbox already exists, it will be deleted first
     *
     * @param string $name Mailbox name
     *
     * @return Mailbox
     */
    protected function createMailbox($name)
    {
        $uniqueName = $name . uniqid();

        try {
            $mailbox = static::getConnection()->getMailbox($uniqueName);
            $this->deleteMailbox($mailbox);
        } catch (MailboxDoesNotExistException $e) {
            // Ignore mailbox not found
        }

        return static::getConnection()->createMailbox($uniqueName);
    }

    /**
     * Delete a mailbox and all its messages
     *
     * @param Mailbox $mailbox
     */
    protected function deleteMailbox(Mailbox $mailbox)
    {
        $mailbox->delete();
    }

    protected function createTestMessage(
        Mailbox $mailbox,
        $subject = 'Don\'t panic!',
        $body = 'Don\'t forget your towel',
        $from = 'someone@there.com',
        $to = 'me@here.com'
    ) {
        $message = str_replace(
            [ '{{subject}}', '{{body}}', '{{from}}', '{{to}}' ],
            [ $subject, $body, $from, $to ],
            file_get_contents(__DIR__ . '/fixtures/email')
        );

        $mailbox->addMessage($message);
    }
    
    protected function getFixture($fixture)
    {
        return file_get_contents(__DIR__ . '/fixtures/' . $fixture);
    }
}
