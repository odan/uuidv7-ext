#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include <php.h>
#include <ext/random/php_random.h>
#include <sys/time.h>
#include <stdint.h>
#include <stdlib.h>
#include <inttypes.h>

PHP_FUNCTION(uuidv7)
{
    struct timeval tv;
    gettimeofday(&tv, NULL);
    uint64_t ts = (uint64_t)tv.tv_sec * 1000 + tv.tv_usec / 1000;

    unsigned char r[8];
    if (php_random_bytes(r, sizeof(r), 0) != SUCCESS) {
        RETURN_FALSE;
    }

    unsigned char extra[2];
    if (php_random_bytes(extra, sizeof(extra), 0) != SUCCESS) {
        RETURN_FALSE;
    }

    char uuid[37];
    snprintf(uuid, sizeof(uuid),
        "%02x%02x%02x%02x-%02x%02x-%04x-%04x-%04x%04x%04x",
        (int)((ts >> 40) & 0xFF),
        (int)((ts >> 32) & 0xFF),
        (int)((ts >> 24) & 0xFF),
        (int)((ts >> 16) & 0xFF),
        (int)((ts >> 8) & 0xFF),
        (int)(ts & 0xFF),

        ((r[0] << 8) | r[1]) & 0x0FFF | 0x7000,
        ((r[2] << 8) | r[3]) & 0x3FFF | 0x8000,

        (r[4] << 8) | r[5],
        (r[6] << 8) | r[7],
        (extra[0] << 8) | extra[1]
    );

    RETURN_STRING(uuid);
}


///

#include "uuidv7.h"

ZEND_BEGIN_ARG_WITH_RETURN_TYPE_INFO_EX(arginfo_uuidv7, 0, 0, IS_STRING, 0)
ZEND_END_ARG_INFO()

static const zend_function_entry uuidv7_functions[] = {
    PHP_FE(uuidv7, arginfo_uuidv7)
    PHP_FE_END
};

zend_module_entry uuidv7_module_entry = {
    STANDARD_MODULE_HEADER,
    "uuidv7",
    uuidv7_functions,
    NULL, NULL, NULL, NULL, NULL,
    "0.1",
    STANDARD_MODULE_PROPERTIES
};

#ifdef COMPILE_DL_UUIDV7
ZEND_GET_MODULE(uuidv7)
#endif
