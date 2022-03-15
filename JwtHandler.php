<?php
require './src/JWT.php';
require './src/Key.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
class JwtHandler
{
    protected $jwt_secret;
    protected $token;
    protected $issuedAt;
    protected $expire;
    protected $jwt;

    public function __construct()
    {
        date_default_timezone_set('Asia/Kolkata');
        $this->issuedAt = time();

        $this->expire = $this->issuedAt + 3600;

        $this->jwt_secret = "ABCDEFABCDEF0123";
    }

    public function jwtEncodeData($iss, $data)
    {

        $this->token = array(
            "iss" => $iss,
            "aud" => $iss,
            "iat" => $this->issuedAt,
            "exp" => $this->expire,
            "data" => $data
        );

        $this->jwt = JWT::encode($this->token, $this->jwt_secret, 'HS256');
        return $this->jwt;
    }

    public function jwtDecodeData($jwt_token)
    {
        try {
            $decode = JWT::decode($jwt_token,new key($this->jwt_secret,'HS256'));
            return $decode->data;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
