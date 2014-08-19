# Hi there!

Introducing the _lightweight_ blog **Honey**. Just clone the [github repository](http://github.com) into your php-enabled site, and you're good to go!

* Fast
* Secure
* Simple (uses markdown syntax)

You'll need mod-rewrite on your apache for this blog to work out-of-the-box. It will also secure your private folders, if you're not pointing to the webfoot folder (read "Improving Security" below).

## Technical

### Requirements
* PHP 5.4
* Javascript-enabled browser

### Improving security
* Point your website to the "webroot" folder.
* Change the security salt on config.php
