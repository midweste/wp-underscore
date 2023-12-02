<?php

namespace _;

// cache helper functions
function cache_enabled(): bool
{
    return true;
    $enabled = get_option('cache_enabled');
    return $enabled;
}

function cache_expire_default(): int
{
    return wp_rand(86400, 129600);
}

function cache_name_delimiter(): string
{
    return ':';
}

function cache_name_create(string $name = ''): string
{
    $arguments = func_get_args();
    $trace     = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
    $delimiter = cache_name_delimiter();
    $caller    = $trace[1];

    $type       = (!empty($caller['type'])) ? $caller['type'] : '';
    $class      = (!empty($caller['class'])) ? '\\' . $caller['class'] . $type : '';
    $function   = (!empty($caller['function'])) ? $caller['function'] : '';
    $methodPath = $class . $function;
    if ($function === '{closure}') {
        $methodPath = str_replace('/', '-', $trace[0]['file']);
    }
    $argumentSignature = md5(json_encode($arguments)); //phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
    $path              = array_filter([$name, $methodPath, $argumentSignature]);
    $nameSignature     = implode($delimiter, $path);
    return $nameSignature;
}

function cache_group(string $delimitedKey): string
{
    $parts = explode(cache_name_delimiter(), $delimitedKey);
    return $parts[0];
}

function cache_key(string $delimitedKey): string
{
    $group = cache_group($delimitedKey);
    $key   = str_replace($group . cache_name_delimiter(), '', $delimitedKey);
    return $key;
}

// cache manipulation functions

function cache_set(string $key, $value, $expiration = null)
{
    if (!cache_enabled()) {
        return $value;
    }
    $expire = ($expiration === 0 || is_int($expiration)) ? $expiration : cache_expire_default();
    wp_cache_set(cache_key($key), $value, cache_group($key), $expire);
    return $value;
}

function cache_delete($key): bool
{
    if (!cache_enabled()) {
        return false;
    }
    return wp_cache_delete(cache_key($key), cache_group($key));
}

function cache_get(string $key, $default = null)
{
    if (!cache_enabled()) {
        return $default;
    }
    return wp_cache_get(cache_key($key), cache_group($key));
}

function cache_has(string $key): bool
{
    if (!cache_enabled()) {
        return false;
    }
    $found = null;
    wp_cache_get(cache_key($key), cache_group($key), false, $found);
    return ($found === false) ? false : true;
}

function cache_delete_group(string $group): ?bool
{
    if (!cache_enabled()) {
        return null;
    }
    // group is like post-321: with or without :
    $groupNormalized = str_replace(' ', '', $group);
    if ($groupNormalized === '' || $groupNormalized === ':') {
        return null;
    }

    if (cache_is_memcached()) {
        // $pattern = rtrim($groupNormalized, ':') . ':*';
        return cache_memcached_delete_keys([$group]);
    } elseif (cache_is_redis()) {
        return cache_redis_delete_keys($group);
    }
    return null;
}

function cache_memcached_delete_keys(array $keyPatterns): bool
{
    if (!cache_enabled() || empty($keyPatterns)) {
        return false;
    }

    global $wp_object_cache;
    if (empty($wp_object_cache->mc)) {
        return false;
    }

    $cachedKeys = cache_memcached_get_all_keys();
    if (empty($cachedKeys) || !is_array($cachedKeys)) {
        return false;
    }

    // pattern is like post-321, term-3422, rest-post-234, rest-term-21551. with or without trailing :
    // change keyPatterns from rest-term-6729 to object cache key type d2959304386afc0cd0f04b9e9wp_:rest-term-6729:
    $memcachedPatterns = [];
    foreach ($keyPatterns as $keyPattern) {
        $memcachedPatterns[$wp_object_cache->key('', $keyPattern)] = $keyPattern;
    }

    // match against existing keys
    $result = false;
    foreach ($cachedKeys as $cacheKey) {
        foreach ($memcachedPatterns as $pattern => $group) {
            if (strpos($cacheKey, $pattern) === 0) {
                $id     = str_replace($pattern, '', $cacheKey);
                $result = $wp_object_cache->delete($id, $group);
                //$rstring = ($result) ? ' true' : ' false';
                //admin_alert('DEBUG', "Deleted $group:$id $rstring");
            }
        }
    }
    return $result;
}

function cache_redis_delete_keys(string $group): ?bool
{
    if (!cache_enabled()) {
        return false;
    }
    // group is like post-321: with or without :
    $groupNormalized = str_replace(' ', '', $group);
    if ($groupNormalized === '' || $groupNormalized === ':') {
        return null;
    }

    global $wp_object_cache;
    if (empty($wp_object_cache->redis)) {
        return null;
    }
    $redis = $wp_object_cache->redis;

    $pattern = rtrim($groupNormalized, ':') . ':*';
    $matches = $redis->keys($pattern);
    if (empty($matches)) {
        return null;
    }

    //setting all keys as parameter of "del" function. Using this we can achieve $redisObj->del("key1","key2);
    return call_user_func_array(array(&$redis, 'del'), $matches);
}

function cache_memcached_get_all_keys(string $host = '127.0.0.1', int $port = 11211)
{
    static $allKeys;
    if (!empty($allKeys)) {
        return $allKeys;
    }

    // https://www.php.net/manual/en/memcached.getallkeys.php
    $sock = fsockopen($host, $port, $errno, $errstr, 3);
    if ($sock === false) {
        throw new \Exception("Error connection to server {$host} on port {$port}: ({$errno}) {$errstr}");
    }

    if (fwrite($sock, "stats items\n") === false) {
        throw new \Exception("Error writing to socket");
    }

    $slabCounts = [];
    while (($line = fgets($sock)) !== false) {
        $line = trim($line);
        if ($line === 'END') {
            break;
        }

        // STAT items:8:number 3
        if (preg_match('!^STAT items:(\d+):number (\d+)$!', $line, $matches)) {
            $slabCounts[$matches[1]] = (int)$matches[2];
        }
    }

    $allKeys = [];
    foreach ($slabCounts as $slabNr => $slabCount) {
        if (fwrite($sock, "lru_crawler metadump {$slabNr}\n") === false) {
            throw new \Exception('Error writing to socket');
        }

        $count = 0;
        while (($line = fgets($sock)) !== false) {
            $line = trim($line);
            if ($line === 'CLIENT_ERROR lru crawler disabled') {
                throw new \Exception($line);
            }

            if ($line === 'END') {
                break;
            }

            // key=foobar exp=1596440293 la=1596439293 cas=8492 fetch=no cls=24 size=14908
            if (preg_match('!^key=(\S+)!', $line, $matches)) {
                $allKeys[] = urldecode($matches[1]);
                $count++;
            }
        }

        // if ($count !== $slabCount) {
        //     throw new Exception("Surprise, got {$count} keys instead of {$slabCount} keys");
        // }
    }

    if (fclose($sock) === false) {
        throw new \Exception('Error closing socket');
    }

    return $allKeys;
}

function cache_is_redis(): bool
{
    global $wp_object_cache;
    return !empty($wp_object_cache->redis);
}

function cache_is_memcached(): bool
{
    global $wp_object_cache;
    return !empty($wp_object_cache->mc);
}

function cache_provider_name(): string
{
    if (cache_is_memcached()) {
        return 'mc';
    } elseif (cache_is_redis()) {
        return 'redis';
    }
    return 'unknown';
}

function &cache_provider(): ?array
{
    global $wp_object_cache;
    if (empty($wp_object_cache) || empty($wp_object_cache->{cache_provider_name()})) {
        return null;
    }
    return $wp_object_cache->{cache_provider_name()};
}

function cache_glob_files(string $globPath, string $name, int $expires = 0, callable $callback = null): string
{
    $cacheName = cache_name_create($name);
    if (cache_has($cacheName)) {
        return cache_get($cacheName);
    }

    $content = files_get_contents($globPath, $callback);
    return cache_set($cacheName, $content, $expires);
}
