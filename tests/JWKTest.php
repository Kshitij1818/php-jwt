<?php

namespace Firebase\JWT;

use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use UnexpectedValueException;

class JWKTest extends TestCase
{
    private static $keys;
    private static $privKey1;
    private static $privKey2;

    public function testMissingKty()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('JWK must contain a "kty" parameter');

        $badJwk = ['kid' => 'foo'];
        $keys = JWK::parseKeySet(['keys' => [$badJwk]]);
    }

    public function testInvalidAlgorithm()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('No supported algorithms found in JWK Set');

        $badJwk = ['kty' => 'BADTYPE', 'alg' => 'RSA256'];
        $keys = JWK::parseKeySet(['keys' => [$badJwk]]);
    }

    public function testParsePrivateKey()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('RSA private keys are not supported');

        $jwkSet = json_decode(
            file_get_contents(__DIR__ . '/data/rsa-jwkset.json'),
            true
        );
        $jwkSet['keys'][0]['d'] = 'privatekeyvalue';

        JWK::parseKeySet($jwkSet);
    }

    public function testParsePrivateKeyWithoutAlg()
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('JWK must contain an "alg" parameter');

        $jwkSet = json_decode(
            file_get_contents(__DIR__ . '/data/rsa-jwkset.json'),
            true
        );
        unset($jwkSet['keys'][0]['alg']);

        JWK::parseKeySet($jwkSet);
    }

    public function testParseKeyWithEmptyDValue()
    {
        $jwkSet = json_decode(
            file_get_contents(__DIR__ . '/data/rsa-jwkset.json'),
            true
        );

        // empty or null values are ok
        $jwkSet['keys'][0]['d'] = null;

        $keys = JWK::parseKeySet($jwkSet);
        $this->assertTrue(is_array($keys));
    }

    public function testParseJwkKeySet()
    {
        $jwkSet = json_decode(
            file_get_contents(__DIR__ . '/data/rsa-jwkset.json'),
            true
        );
        $keys = JWK::parseKeySet($jwkSet);
        $this->assertTrue(is_array($keys));
        $this->assertArrayHasKey('jwk1', $keys);
        self::$keys = $keys;
    }

    public function testParseJwkKey_empty()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JWK must not be empty');

        JWK::parseKeySet(['keys' => [[]]]);
    }

    public function testParseJwkKeySet_empty()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JWK Set did not contain any keys');

        JWK::parseKeySet(['keys' => []]);
    }

    /**
     * @depends testParseJwkKeySet
     */
    public function testDecodeByJwkKeySetTokenExpired()
    {
        $privKey1 = file_get_contents(__DIR__ . '/data/rsa1-private.pem');
        $payload = ['exp' => strtotime('-1 hour')];
        $msg = JWT::encode($payload, $privKey1, 'RS256', 'jwk1');

        $this->expectException(ExpiredException::class);

        JWT::decode($msg, self::$keys);
    }

    /**
     * @depends testParseJwkKeySet
     */
    public function testDecodeByJwkKeySet()
    {
        $privKey1 = file_get_contents(__DIR__ . '/data/rsa1-private.pem');
        $payload = ['sub' => 'foo', 'exp' => strtotime('+10 seconds')];
        $msg = JWT::encode($payload, $privKey1, 'RS256', 'jwk1');

        $result = JWT::decode($msg, self::$keys);

        $this->assertEquals("foo", $result->sub);
    }

    /**
     * @depends testParseJwkKeySet
     */
    public function testDecodeByMultiJwkKeySet()
    {
        $privKey2 = file_get_contents(__DIR__ . '/data/rsa2-private.pem');
        $payload = ['sub' => 'bar', 'exp' => strtotime('+10 seconds')];
        $msg = JWT::encode($payload, $privKey2, 'RS256', 'jwk2');

        $result = JWT::decode($msg, self::$keys);

        $this->assertEquals("bar", $result->sub);
    }
}
