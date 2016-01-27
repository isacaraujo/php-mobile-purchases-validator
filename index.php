<?php

// Inspired in: https://github.com/aporat/store-receipt-validator

// https://www.youtube.com/watch?v=jB5TvzzggWw
require_once 'vendor/autoload.php';

use ReceiptValidator\iTunes\Validator as ITunesValidator;
use ReceiptValidator\GooglePlay\Validator as PlayValidator;

$app = new \Slim\App();
$config = simplexml_load_file('config.xml');

function sendResponse($res, $message, $status = 404, $options = []) {
  $options = array_merge([
    'success' => $status === 200,
    'status' => $status,
    'message' => $message
  ], $options);
  $res = $res->withStatus($status)
      ->withHeader('Content-type', 'application/json');
  $res->getBody()
      ->write(json_encode($options));
  return $res;
}

$app->get('/validate/apple/{receiptId}', function ($req, $res, $args) use ($config) {
  $env = strtoupper("{$config->apple->environment}") === 'PRODUCTION' ? ITunesValidator::ENDPOINT_PRODUCTION : ITunesValidator::ENDPOINT_SANDBOX;
  $receipt = $args['receiptId'];
  $validator = new ITunesValidator($env);
  try {
    $response = $validator->setReceiptData($receipt)->validate();
  } catch (\Exception $e) {
    return sendResponse($res, $e->getMessage(), 500);
  }

  if ($response->isValid()) {
    return sendResponse($res, 'This receipt is valid.', 200, [
      'receipt' => $response->getReceipt()
    ]);
  } else {
    return sendResponse($res, 'This receipt is invalid.', 403, [
      'result_code' => $response->getResultCode()
    ]);
  }
});

$app->get('/validate/google/product/{productId}/token/{purchaseToken}', function ($req, $res, $args) use ($config) {
  $clientId = "{$config->google->clientId}";
  $clientSecret = "{$config->google->clientSecret}";
  $refreshToken = "{$config->google->refreshToken}";
  $packageName = "{$config->google->packageName}";
  $productId = $args['productId'];
  $purchaseToken = $args['purchaseToken'];

  
  try {
    $validator = new PlayValidator([
      'client_id' => $clientId,
      'client_secret' => $clientSecret,
      'refresh_token' => $refreshToken
    ]);
    $response = $validator->setPackageName($packageName)
      ->setProductId($productId)
      ->setPurchaseToken($purchaseToken)
      ->validate();
  } catch (\Exception $e) {
    $message = $e->getMessage();
    $status = preg_match('/\(404\)/', $message) ? 404 : 403;
    return sendResponse($res, $message, $status);
  }
  return sendResponse($res, 'This product is valid.');
});

$app->run();
