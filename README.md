# Shortcut stats report

To run:
* Add a file called `secrets.php` to `shortcut/lib`
* Paste in:
```php
<?php

namespace shortcut;

class Secrets
{
    const API_KEY = '{Shortcut API key}';
}

```
* To get a Shortcut API key see: https://help.shortcut.com/hc/en-us/articles/205701199-Shortcut-API-Tokens
* Open cmd
* cd {wherever you cloned to}/shortcut
* `php Shortcut help` to get a list of commands 
