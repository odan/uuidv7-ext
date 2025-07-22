# Native UUID v7 extension for PHP


## Features 

✅ UUIDv7-compliant<br>
✅ Cryptographically secure

## Usage

```php
echo uuidv7();
```

## Performance Comparison

* uuidv7-ext: ~4.000.000 UUIDs / second = 100%
* symfony/uid: todo
* ramsey/uuid: todo
* uuidv7_xhit: todo
* uuidv7_antonz: todo
* uuidv7_gpt_o3: todo
* uuidv7_gpt_4o: todo

## Compiling

```
sudo apt update
sudo apt install php-dev build-essential

git clone https://github.com/odan/uuidv7-ext.git

phpize
./configure
make
```

## Installation to PHP extensions directory

```
sudo make install
```

## Activate extension in php.ini

```ini
extension=uuidv7.so
```

Or load dynamically for testing:

```
php -dextension=modules/uuidv7.so -r "echo uuidv7(), PHP_EOL;"
```

## PHP function

Same UUIDv7 function logic as the C implementation, 
but written in PHP with cryptographic randomness.

```php
function uuidv7()
{
    $timestamp = (int)(microtime(true) * 1000);

    return sprintf(
        '%08x-%04x-%04x-%04x-%012x',
        ($timestamp >> 16) & 0xFFFFFFFF,
        $timestamp & 0xFFFF,
        random_int(0, 0x0FFF) | 0x7000,     // version 7
        random_int(0, 0x3FFF) | 0x8000,     // variant 10xx
        random_int(0, 0xFFFFFFFFFFFF)       // 48 random bits
    );
}
```

## Licence

MIT
