<?php
declare (strict_types=1);
namespace test;

require_once 'phpunit.phar';

use GuzzleHttp\Client;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class BaseCase extends TestCase
{
    private Client $client;

    protected array $user = [
        'user_id' => 1,
        'nickname' => '测试'
    ];

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->client = new Client([
            'timeout' => 5,
            'base_uri' => 'http://api.ym.com'
        ]);
    }

    protected function request(string $api, array $params = [], $method = 'post'): ResponseInterface
    {
        $data = [
            'headers' => [
                'Authorization' => $this->getToken($this->user['user_id'], $this->user['nickname'])
            ]
        ];
        if ($method == 'post') {
            $data['json'] = $params;
        } else {
            $data['query'] = $params;
        }
        return $this->client->request($method, $api, $data);
    }

    protected function get($api, $params = [])
    {
        $r = $this->request($api, $params, 'get');
        return json_decode((string)$r->getBody(), true);
    }

    protected function post($api, $params = [])
    {
        $r = $this->request($api, $params, 'post');
        return json_decode((string)$r->getBody(), true);
    }

    protected function assertApiResult(array $data)
    {
        $this->assertArrayHasKeys(['code', 'message', 'data'], $data);
    }

    protected function assertApiSuccess(array $data)
    {
        $this->assertApiResult($data);
        $this->assertEquals(0, $data['code']);
    }

    protected function assertApiError(array $data)
    {
        $this->assertApiResult($data);
        $this->assertEquals(1, $data['code']);
        $this->assertNotEmpty($data['message']);
    }

    protected function assertArrayHasKeys(array $keys, array $data)
    {
        foreach($keys as $key) {
            $this->assertArrayHasKey($key, $data);
        }
    }

    protected function assertApiListSuccess(array $data)
    {
        $this->assertApiSuccess($data);
        $this->assertArrayHasKey('list', $data['data']);
        $this->assertArrayHasKey('data', $data['data']['list']);
    }

    protected function getToken(int $userId, string $name) :string
    {
        $now = new \DateTimeImmutable();
        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText('f!Q3ym1#El')
        );
        $expire = '+30 day';
        $token = $config->builder()
            ->issuedAt($now)
            ->expiresAt($now->modify($expire))
            ->withClaim('user_id', $userId)
            ->withClaim('nickname', $name)
            ->getToken($config->signer(), $config->signingKey());
        return $token->toString();
    }
}