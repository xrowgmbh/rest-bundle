<?php

namespace Xrow\Bundle\RestBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/hello/Fabien');

        $this->assertTrue($crawler->filter('html:contains("Hello Fabien")')->count() > 0);
    }
    
    /**
     * @depends testCreateUser
     * @covers POST /user/sessions
     * @return string The created session href
     */
    public function testCreateSession()
    {
        $text = $this->addTestSuffix( 'testCreateUser' );
        $xml = <<< XML
<?xml version="1.0" encoding="UTF-8"?>
<SessionInput>
  <login>$text</login>
  <password>$text</password>
</SessionInput>
XML;
        $request = $this->createHttpRequest(
                "POST",
                "/api/ezp/v2/user/sessions",
                "SessionInput+xml",
                "Session+json"
        );
        $request->setContent( $xml );
        $response = $this->sendHttpRequest( $request );
    
        self::assertHttpResponseCodeEquals( $response, 201 );
        self::assertHttpResponseHasHeader( $response, 'Location' );
    
        $href = $response->getHeader( 'Location' );
        $this->addCreatedElement( $href );
        return $href;
    }
    
    /**
     * @depends testCreateSession
     * @covers DELETE /user/sessions/{sessionId}
     */
    public function testDeleteSession( $sessionHref )
    {
        self::markTestSkipped( "@todo improve. The session can only be deleted if started !" );
        $response = $this->sendHttpRequest(
                $request = $this->createHttpRequest( "DELETE", $sessionHref )
        );
    
        self::assertHttpResponseCodeEquals( $response, 204 );
    }
}
