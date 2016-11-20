<?php

// Inspired in: https://github.com/aporat/store-receipt-validator

// https://www.youtube.com/watch?v=jB5TvzzggWw
require_once 'vendor/autoload.php';

use ReceiptValidator\iTunes\Validator     as ITunesValidator,
    ReceiptValidator\GooglePlay\Validator as PlayValidator;

$config = simplexml_load_file('config.xml');

function param($key, $default = null) {
  if (!isset($_REQUEST[$key])) return $default;
  return $_REQUEST[$key];
}

function sendResponse($message, $status = 200, $options = []) {
  $options = array_merge([
    'success' => $status === 200,
    'status' => $status,
    'message' => $message
  ], $options);
  
  header('Content-type: application/json');
  print json_encode($options);
  exit;
}

function dispatchApple() {
  global $config;
  $endpoint  = strtoupper("{$config->apple->environment}") === 'PRODUCTION' ? 
    ITunesValidator::ENDPOINT_PRODUCTION : 
    ITunesValidator::ENDPOINT_SANDBOX;
  $receipt   = param('receiptId');
  $validator = new ITunesValidator($endpoint);
  try {
    $response = $validator->setReceiptData($receipt)->validate();
  } catch (\Exception $e) {
    return sendResponse($e->getMessage(), 500);
  }

  if ($response->isValid()) {
    return sendResponse('This receipt is valid.', 200, [
      'receipt' => $response->getReceipt()
    ]);
  } else {
    return sendResponse('This receipt is invalid.', 403, [
      'result_code' => $response->getResultCode()
    ]);
  }
}

function dispatchGoogle() {
  global $config;
  $clientId      = "{$config->google->clientId}";
  $clientSecret  = "{$config->google->clientSecret}";
  $refreshToken  = "{$config->google->refreshToken}";
  $packageName   = "{$config->google->packageName}";
  $productId     = param('productId');
  $purchaseToken = param('purchaseToken');
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
    return sendResponse($message, $status);
  }
  return sendResponse('This product is valid.');
}

function main() {

  ini_set('display_errors', true);
  ini_set('error_reporting', E_ALL);

  $requestName = 'dispatch' . ucfirst(param('service'));

  if (!function_exists($requestName)) {
    return sendResponse('Service Not Found', 404);
  }

  return call_user_func($requestName, $_REQUEST);
}

main();
