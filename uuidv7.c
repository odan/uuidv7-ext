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
    uint64_t timestamp_ms = (uint64_t)tv.tv_sec * 1000 + tv.tv_usec / 1000;

    int rand_a = php_mt_rand() & 0x0FFF;
    int rand_b = php_mt_rand() & 0x3FFF;
    uint64_t rand_c = ((uint64_t)php_mt_rand() << 32) | php_mt_rand();

    rand_a |= 0x7000; // set version to 7
    rand_b |= 0x8000; // set variant to 10xx

    char uuid[37]; // UUID string length + null terminator

    snprintf(uuid, sizeof(uuid),
        "%02x%02x%02x%02x-%02x%02x-%04x-%04x-%012" PRIx64,
        (unsigned int)((timestamp_ms >> 40) & 0xFF),
        (unsigned int)((timestamp_ms >> 32) & 0xFF),
        (unsigned int)((timestamp_ms >> 24) & 0xFF),
        (unsigned int)((timestamp_ms >> 16) & 0xFF),
        (unsigned int)((timestamp_ms >> 8) & 0xFF),
        (unsigned int)(timestamp_ms & 0xFF),
        rand_a,
        rand_b,
        rand_c & 0xFFFFFFFFFFFF
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
