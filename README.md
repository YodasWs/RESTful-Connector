RESTful-Connector
=================

Helping you connect to third-party RESTful APIs using HTTP and OAuth 2.0

## Setup

### HTTP

`http.class.php`

This class gives you more control over error-handling than PHP's file_get_contents

### OAuth2.0 Login

`oauth2.php`

* Rename 'example.keys.php' to 'keys.php' and add your apps' secrets and client IDs.

  If you're using this code as part of an open source project, do one of the following:
  * Save keys.php in a different folder and update either `require_once('keys.php')` or your PHP include_path
  * Add `keys.php` to your .gitignore

    I cannot be held responsible if you allow your apps' secrets to become public

## Supported APIs
* [x] [Facebook Login](https://developers.facebook.com/docs/facebook-login/)
* [x] [Google+ Login](https://developers.google.com/+/api/oauth)
* [ ] Twitter
* [ ] Windows Live
* [ ] Foursquare
* [ ] Easy-to-set Icon Sets

## Resources
* [Social Media Icon Sets](http://www.hongkiat.com/blog/free-social-media-icon-sets-best-of/)
