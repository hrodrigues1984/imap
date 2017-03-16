<?php

namespace Ddeboer\Imap\Tests;

class MessageTest extends AbstractTest
{
    /**
     * @var \Ddeboer\Imap\Mailbox
     */
    protected $mailbox;

    public function setUp()
    {
        $this->mailbox = $this->createMailbox('test-message');
    }

    public function tearDown()
    {
        $this->deleteMailbox($this->mailbox);
    }

    public function testKeepUnseen()
    {
        $this->createTestMessage($this->mailbox, 'Message A');
        $this->createTestMessage($this->mailbox, 'Message B');
        $this->createTestMessage($this->mailbox, 'Message C');

        $messages = $this->mailbox->getMessages();

        $message = $this->mailbox->getMessage($messages[0]);
        $this->assertFalse($message->isSeen());

        $message->getBodyText();
        $this->assertTrue($message->isSeen());

        $message = $this->mailbox->getMessage($messages[1]);
        $this->assertFalse($message->isSeen());

        $message->keepUnseen()->getBodyText();
        $this->assertFalse($message->isSeen());
    }

//    public function testEncoding7Bit()
//    {
//        $this->createTestMessage($this->mailbox, 'lietuviškos raidės', 'lietuviškos raidės');
//        $messages = $this->mailbox->getMessages();
//
//        $message = $this->mailbox->getMessage($messages[0]);
//
//        $this->assertEquals('lietuviškos raidės', $message->getSubject());
//        $this->assertEquals('lietuviškos raidės', $message->getBodyText());
//    }

    public function testEncodingQuotedPrintable()
    {

        $this->mailbox->addMessage($this->getFixture('email_spain'));

        $messages = $this->mailbox->getMessages();

        $message = $this->mailbox->getMessage($messages[0]);
        $this->assertEquals('ESPAÑA', $message->getSubject());
        $this->assertEquals("<html><body>ESPAÑA</body></html>", $message->getBodyHtml());
        $this->assertEquals(new \DateTime('2014-06-13 17:18:44+0200'), $message->getDate());
    }
    
    public function testEmailAddress()
    {
        $this->mailbox->addMessage($this->getFixture('email_address'));
        $messages = $this->mailbox->getMessages();

        $message = $this->mailbox->getMessage($messages[0]);
        
        $from = $message->getFrom();
        $this->assertInstanceOf('\Ddeboer\Imap\Message\EmailAddress', $from);
        $this->assertEquals('some', $from->getMailbox());

        $cc = $message->getCc();
//        die(var_dump($cc));
        $this->assertCount(2, $cc);
        $this->assertInstanceOf('\Ddeboer\Imap\Message\EmailAddress', $cc[0]);
        $this->assertEquals('This one is right', $cc[0]->getName());
        $this->assertEquals('ping@pong.com', $cc[0]->getAddress());

        $this->assertInstanceOf('\Ddeboer\Imap\Message\EmailAddress', $cc[1]);
        $this->assertEquals('other', $cc[1]->getMailbox());
    }

    public function testBcc()
    {
        $raw = "Subject: Undisclosed recipients\n\n";
        $this->mailbox->addMessage($raw);

        $messages = $this->mailbox->getMessages();

        $message = $this->mailbox->getMessage($messages[0]);

        $this->assertEquals('Undisclosed recipients', $message->getSubject());
        $this->assertCount(0, $message->getTo());
    }

    public function testMessageWithBcc()
    {
        $this->mailbox->addMessage($this->getFixture('email_with_bcc'));

        $messages = $this->mailbox->getMessages();

        $message = $this->mailbox->getMessage($messages[0]);

        $bcc = $message->getBcc();
        $this->assertInstanceOf('\Ddeboer\Imap\Message\EmailAddress', $bcc[0]);
        $this->assertEquals('With bcc', $bcc[0]->getName());
        $this->assertEquals('ping@pong.com', $bcc[0]->getAddress());
    }

    public function testMessageWithReferences()
    {
        $this->mailbox->addMessage($this->getFixture('email_with_bcc'));

        $messages = $this->mailbox->getMessages();

        $message = $this->mailbox->getMessage($messages[0]);

        $this->assertEquals('<1@some.mail.com>,<2@some.mail.com>', $message->getReferences());
    }

    public function testMessageWithInReplyTo()
    {
        $this->mailbox->addMessage($this->getFixture('email_with_bcc'));

        $messages = $this->mailbox->getMessages();

        $message = $this->mailbox->getMessage($messages[0]);

        $this->assertEquals('<2@some.mail.com>', $message->getInReplyTo());
    }
    
    public function testDelete()
    {
        $this->createTestMessage($this->mailbox, 'Message A');
        $this->createTestMessage($this->mailbox, 'Message B');
        $this->createTestMessage($this->mailbox, 'Message C');

        $messages = $this->mailbox->getMessages();

        $this->mailbox->getMessage($messages[2])->delete();

        $this->assertCount(2, $this->mailbox);

        foreach ($this->mailbox->getMessages() as $message) {
            $this->assertNotEquals("Message C", $message->getSubject());
        }
    }

    /**
     * @dataProvider getAttachmentFixture
     */
    public function testGetAttachments()
    {
        $this->mailbox->addMessage(
            $this->getFixture('attachment_encoded_filename')
        );

        $messages = $this->mailbox->getMessages();

        $message = $this->mailbox->getMessage($messages[0]);

        $this->assertCount(1, $message->getAttachments());
        $attachment = $message->getAttachments()[0];
        $this->assertEquals(
            'Prostřeno_2014_poslední volné termíny.xls',
            $attachment->getFilename()
        );
        $this->assertEquals(
            'application/vnd.ms-excel',
            $attachment->getContentType()
        );
        $this->assertEquals(
            'attachment',
            $attachment->getContentDisposition()
        );
    }

    public function testAttachmentDispositionAndType()
    {
        $this->mailbox->addMessage($this->getFixture('attachment_encoded_filename'));

        $messages = $this->mailbox->getMessages();

        $message = $this->mailbox->getMessage($messages[0]);

        $this->assertCount(1, $message->getAttachments());
        $attachment = $message->getAttachments()[0];

        $this->assertEquals(
            'application/vnd.ms-excel',
            $attachment->getContentType()
        );
        $this->assertEquals(
            'attachment',
            $attachment->getContentDisposition()
        );
    }
    
    public function getAttachmentFixture()
    {
        return [
            [ 'attachment_no_disposition' ],
            [ 'attachment_encoded_filename' ]
        ];
    }
}
