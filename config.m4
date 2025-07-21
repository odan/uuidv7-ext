PHP_ARG_ENABLE(uuidv7, whether to enable uuidv7 support,
[  --enable-uuidv7           Enable uuidv7 support])

if test "$PHP_UUIDV7" = "yes"; then
    PHP_NEW_EXTENSION(uuidv7, uuidv7.c, $ext_shared)
fi
