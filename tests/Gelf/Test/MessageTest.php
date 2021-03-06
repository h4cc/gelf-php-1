<?php

/*
 * This file is part of the php-gelf package.
 *
 * (c) Benjamin Zikarsky <http://benjamin-zikarsky.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gelf\Test;

use Gelf\Message;
use PHPUnit_Framework_TestCase as TestCase;
use Psr\Log\LogLevel;

class MessageTest extends TestCase
{

    /**
     * @var Message
     */
    private $message;

    public function setUp()
    {
        $this->message = new Message();
    }

    public function testTimestamp()
    {
        $this->assertTrue(microtime(true) >= $this->message->getTimestamp());
        $this->assertTrue(0 < $this->message->getTimestamp());

        $this->message->setTimestamp(123);
        $this->assertEquals(123, $this->message->getTimestamp());

        $this->message->setTimestamp("abc");
        $this->assertEquals(0, $this->message->getTimestamp());

        $this->message->setTimestamp("1.23");
        $this->assertEquals(1, $this->message->getTimestamp());
    }

    public function testVersion()
    {
        $this->assertEquals("1.0", $this->message->getVersion());
    }

    public function testHost()
    {
        // default is current hostname
        $this->assertEquals(gethostname(), $this->message->getHost());

        $this->message->setHost("example.local");
        $this->assertEquals("example.local", $this->message->getHost());
    }

    public function testLevel()
    {
        $this->assertEquals(1, $this->message->getSyslogLevel());
        $this->assertEquals(LogLevel::ALERT, $this->message->getLevel());

        $this->message->setLevel(0);
        $this->assertEquals(0, $this->message->getSyslogLevel());
        $this->assertEquals(LogLevel::EMERGENCY, $this->message->getLevel());

        $this->message->setLevel(LogLevel::EMERGENCY);
        $this->assertEquals(0, $this->message->getSyslogLevel());
        $this->assertEquals(LogLevel::EMERGENCY, $this->message->getLevel());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testLevelInvalidString()
    {
        $this->message->setLevel("invalid");
    }

    /**
     * @expectedException RuntimeException
     */
    public function testLevelInvalidInteger()
    {
        $this->message->setLevel(8);
    }

    public function testLogLevelToPsr()
    {
        $this->assertEquals(LogLevel::ALERT, Message::logLevelToPsr("alert"));
        $this->assertEquals(LogLevel::ALERT, Message::logLevelToPsr("ALERT"));
        $this->assertEquals(LogLevel::ALERT, Message::logLevelToPsr(1));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testLogLevelToPsrInvalidString()
    {
        Message::logLevelToPsr("invalid");
    }

    /**
     * @expectedException RuntimeException
     */
    public function testLogLevelToPsrInvalidInt()
    {
        Message::logLevelToPsr(-1);
    }

    public function testOptionalMessageFields()
    {
        $fields = array("Line", "File", "Facility", "FullMessage", "ShortMessage");
        foreach ($fields as $field) {
            $g = "get$field";
            $s = "set$field";
            $this->assertEmpty($this->message->$g());

            $this->message->$s("test");
            $this->assertEquals("test", $this->message->$g());
        }
    }

    public function testAdditionals()
    {
        $this->assertTrue(is_array($this->message->getAllAdditionals()));
        $this->assertTrue(0 == count($this->message->getAllAdditionals()));

        $this->message->setAdditional("foo", "bar");
        $this->assertEquals("bar", $this->message->getAdditional("foo"));
        $this->assertTrue(1 == count($this->message->getAllAdditionals()));

        $this->message->setAdditional("foo", "buk");
        $this->assertEquals("buk", $this->message->getAdditional("foo"));
        $this->assertTrue(1 == count($this->message->getAllAdditionals()));

        $this->assertEquals(array("foo" => "buk"), $this->message->getAllAdditionals());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testSetAdditionalEmptyKey()
    {
        $this->message->setAdditional("", "test");
    }
    /**
     * @expectedException RuntimeException
     */
    public function testGetAdditionalInvalidKey()
    {
        $this->message->getAdditional("invalid");
    }

    public function testSetTimestamp()
    {
        $dt = new \DateTime();
        $this->message->setTimestamp($dt);

        $this->assertEquals($dt->format("U"), $this->message->getTimestamp());
    }

    public function testMethodChaining()
    {
        $this->message
            ->setTimestamp(new \DateTime())
            ->setAdditional("test", "value")
            ->setFacility("test")
            ->setHost("test")
            ->setFile("test")
            ->setFullMessage("testtest")
            ->setShortMessage("test")
            ->setLevel("ERROR")
            ->setLine(1)
        ;
    }

    public function testToArray()
    {
        $this->message->setAdditional("foo", "bar");
        $data = $this->message->toArray();
        $this->assertTrue(is_array($data));
        $this->assertArrayHasKey("_foo", $data);
        $this->assertEquals("bar", $data["_foo"]);

        $map = array(
            "version"       => "getVersion",
            "host"          => "getHost",
            "timestamp"     => "getTimestamp",
            "full_message"  => "getFullMessage",
            "short_message" => "getShortMessage",
            "line"          => "getLine",
            "file"          => "getFile",
            "facility"      => "getFacility",
            "level"         => "getSyslogLevel"
        );

        foreach ($map as $k => $method) {
            $r = $this->message->$method();
            if (empty($r)) {
                $this->assertFalse(
                    array_key_exists($k, $data),
                    "When method $method returns an empty value, $k should not be in array"
                );
            } else {
                $this->assertEquals($data[$k], $this->message->$method());
            }
        }
    }
}
