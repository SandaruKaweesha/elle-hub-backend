<?php

class JWT
{
    /**
     * Encode a payload into a JWT token
     */
    public static function encode(array $payload, string $secret): string
    {
        // Define header
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        
        // Base64Url encode header and payload
        $base64UrlHeader = self::base64urlEncode($header);
        $base64UrlPayload = self::base64urlEncode(json_encode($payload));
        
        // Create signature hash
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
        $base64UrlSignature = self::base64urlEncode($signature);
        
        // Return the token
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    /**
     * Decode a JWT token and verify signature
     */
    public static function decode(string $token, string $secret)
    {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }

        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $parts;

        // Verify signature
        $signature = self::base64urlEncode(hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true));

        if (!hash_equals($signature, $base64UrlSignature)) {
            return false; // Signature doesn't match
        }

        $payload = json_decode(self::base64urlDecode($base64UrlPayload), true);

        // Check token expiration if 'exp' claim is present
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false; // Token expired
        }

        return $payload;
    }

    /**
     * Base64Url string encoding
     */
    private static function base64urlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    /**
     * Base64Url string decoding
     */
    private static function base64urlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $data .= str_repeat('=', $padlen);
        }
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
}
