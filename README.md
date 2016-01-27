# Example of InApp Purchase validator (php server)

### Pre-requisites
 - PHP >=5.5 (5.6 or newer is strongly recommended)
 - Access PHP by Commmand Line Interface (for windows case, view [this video](https://www.youtube.com/watch?v=jB5TvzzggWw))
 
### Get started

In your Terminal (cmd for windows), goto project directory and run:

```
$ php composer.phar install
```

Edit the file `config.xml` with info of your project, following this requirements:

 - For get Google refresh_token, you must to go to [Google Console](https://developers.google.com/android-publisher/authorization) and follow the instructions.
 - Also you must to setup the apple environment, according with [Apple Requirements](https://developer.apple.com/library/ios/releasenotes/General/ValidateAppStoreReceipt/Chapters/ValidateRemotely.html).

### Example of config.xml file:

```xml
<?xml version="1.0"?>
<config>
	<apple>
		<!-- In case of tests, use 'SANDBOX' -->
		<environment>PRODUCTION</environment>
	</apple>
	<!-- For Google Setup, please view this link: -->
	<!-- https://developers.google.com/android-publisher/authorization -->
	<google>
		<clientId>1234</clientId>
		<clientSecret>5678</clientSecret>
		<refreshToken>LoremIpsum12345678</refreshToken>
		<packageName>com.mcl.app.XmlLoremIpsum</packageName>
	</google>
</config>
```
 
### HTTP Interface
 
```
GET /validate/apple/{receiptId}
```
 - Replace the `{receiptId}` variable by data received from apple store (in base64).
 

```
GET /validate/google/product/{productId}/token/{purchaseToken}
```
 - Replace the `{productId}` by requested product identifier.
 - Replace the `{purchaseToken}` by received token from google play store.
 
 
### Responses

This is the response format:

```json
{
 	"success": true,
    "status": 200,
    "message": "This receipt is valid.",
    "other_data": "Other data here."
}
```
