# Native UUID v7 extension for PHP

## Compile on Ubuntu

```
sudo apt update
sudo apt install php-dev build-essential

git clone https://github.com/odan/uuidv7-ext.git

cd ~/uuidv7-ext
phpize
./configure
make
```

Optional: Installation to PHP extensions directory

```
sudo make install
```

Activate extension in php.ini

```ini
extension=uuidv7.so
```

Or load dynamically for testing:

```
php -dextension=modules/uuidv7.so -r "echo uuidv7(), PHP_EOL;"
```

## Usage

```php
echo uuidv7();
```

