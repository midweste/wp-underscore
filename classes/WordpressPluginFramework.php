<?php
/*
 *
 * @link              https://github.com/midweste
 * @since             1.0.0
 * @package           Wordpress Plugin Framework
 *
 * @wordpress-plugin
 * Plugin Name:       Wordpress Plugin Framework
 * Plugin URI:        https://github.com/midweste
 * Description:       Wordpress Plugin Framework
 * Version:           1.0.0
 * Author:            Midweste
 * Author URI:        https://github.com/midweste
 * License:           GPL-2.0+
 *
 */

namespace _;

defined('ABSPATH') || exit;

define('WPPLUGIN_DIR', __DIR__);

abstract class WordpressPluginFramework
{

    private $initHook = 'init';
    private $status = true;

    //abstract public static function getFile(): string;
    abstract public static function getName(): string;
    abstract public static function getSlug(): string;

    public function __construct()
    {
        if ($this->getStatus() === false) {
            return;
        }

        register_activation_hook(static::getFile(), [__CLASS__, 'activate']);
        register_deactivation_hook(static::getFile(), [__CLASS__, 'deactivate']);
        register_uninstall_hook(static::getFile(), [__CLASS__, 'uninstall']);

        add_action($this->getInitHook(), function () {
            $this->init();
        });
    }

    /**
     * Runs when the plugin is initialized
     */
    protected function init(): void
    {
        // Setup localization
        load_plugin_textdomain(static::getSlug(), false, dirname(plugin_basename(static::getFile())) . '/lang');

        if (is_admin()) {
            //this will run when in the WordPress admin
        } else {
            //this will run when on the frontend
        }
    }

    public function getInitHook(): string
    {
        return $this->initHook;
    }

    public function setInitHook(string $hook): self
    {
        $this->initHook = $hook;
        return $this;
    }

    public function getStatus(): bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getOption(): ?array
    {
        return get_option(static::getSlug(), null);
    }

    public function setOption(array $data): bool
    {
        $current = $this->getOption();
        if (!is_null($current)) {
            if ($current === $data) {
                // have to compare existing value to what is going to be saved
                // because wordpress is dumb and returns false if they are the same
                return true;
            } else {
                return update_option(static::getSlug(), $data, false);
            }
        } else {
            return add_option(static::getSlug(), $data);
        }
    }

    public static function getFile()
    {
        $called = get_called_class();
        d($called, \WP_PLUGIN_DIR);
    }


    public function addShortcode(callable $function, string $shortcode = ''): self
    {
        $sc = (empty($shortcode)) ? static::getSlug() : $shortcode;
        add_shortcode($sc, function ($atts, $content, $shortcode_tag) use ($function) {
            $function($atts, $content, $shortcode_tag);
        });
        return $this;
    }

    public function addAdminMenuPage(callable $callback, int $pos = 99, string $name = '', string $slug = '', string $perm = 'manage_options', string $icon = 'dashicons-schedule')
    {
        add_action('admin_menu', function () use ($name, $slug, $callback, $perm, $icon, $pos) {
            $n = (empty($name)) ? static::getName() : $name;
            $s = (empty($slug)) ? static::getSlug() : $slug;
            add_menu_page(
                __($n, $s),
                __($n, $s),
                $perm,
                $s . '-admin',
                $callback,
                $icon,
                $pos
            );
        });
    }


    /**
     * Helper function for registering and enqueueing scripts and styles.
     *
     * @name			The ID to register with WordPress
     * @file_path		The path to the actual file
     * @is_script		Optional argument for if the incoming file_path is a JavaScript source file.
     */
    protected function enqueueFile(string $handle, string $file_path, array $depends = []): void
    {
        $pathinfo = pathinfo($file_path);
        $is_script = (isset($pathinfo['extension']) && $pathinfo['extension'] == 'js') ? true : false;

        $url = plugins_url($file_path, static::getFile());
        $file = plugin_dir_path(static::getFile()) . $file_path;

        if (!file_exists($file)) {
            return;
        }

        if ($is_script) {
            wp_register_script($handle, $url, $depends); //depends on jquery
            wp_enqueue_script($handle);
        } else {
            wp_register_style($handle, $url);
            wp_enqueue_style($handle);
        }
    }

    /**
     * Helper function for registering and enqueueing scripts and styles.
     *
     * @name	The 	ID to register with WordPress
     * @file_path		The path to the actual file
     * @is_script		Optional argument for if the incoming file_path is a JavaScript source file.
     */
    protected function enqueueExternalFile($handle, $file_path, array $depends = []): void
    {
        $pathinfo = pathinfo($file_path);
        $is_script = (isset($pathinfo['extension']) && $pathinfo['extension'] == 'js') ? true : false;

        $handle = sprintf('%s-%s', static::getSlug(), $handle);
        if ($is_script) {
            wp_register_script($handle, $file_path, $depends);
            wp_enqueue_script($handle);
        } else {
            wp_register_style($handle, $file_path);
            wp_enqueue_style($handle);
        }
    }

    protected function enqueueInline(string $handle, string $content, bool $script = false)
    {
        $handle = sprintf('%s-%s', static::getSlug(), $handle);
        if ($script) {
            wp_register_script($handle, false);
            wp_enqueue_script($handle);
            wp_add_inline_script($handle, $content);
        } else {
            wp_register_style($handle, false);
            wp_enqueue_style($handle);
            wp_add_inline_style($handle, $content);
        }
    }

    /**
     * https://wordpress.stackexchange.com/questions/25910/uninstall-activate-deactivate-a-plugin-typical-features-how-to/25979#25979
     *
     * @return void
     */
    public static function activate()
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }
        $plugin = isset($_REQUEST['plugin']) ? $_REQUEST['plugin'] : '';
        check_admin_referer("activate-plugin_{$plugin}");

        update_option(static::getSlug() . '_activated', 'yes', false);

        static::onActivate();

        wp_cache_flush();
    }

    public static function onActivate()
    {
        return;
    }

    /**
     * https://wordpress.stackexchange.com/questions/25910/uninstall-activate-deactivate-a-plugin-typical-features-how-to/25979#25979
     *
     * @return void
     */
    public static function deactivate()
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }
        $plugin = isset($_REQUEST['plugin']) ? $_REQUEST['plugin'] : '';
        check_admin_referer("deactivate-plugin_{$plugin}");

        update_option(static::getSlug() . '_activated', 'no', false);

        static::onDeactivate();

        wp_cache_flush();
    }

    public static function onDeactivate()
    {
        return;
    }

    /**
     * https://wordpress.stackexchange.com/questions/25910/uninstall-activate-deactivate-a-plugin-typical-features-how-to/25979#25979
     *
     * @return void
     */
    public static function uninstall()
    {
        if (!current_user_can('activate_plugins')) {
            return;
        }
        check_admin_referer('bulk-plugins');

        // Important: Check if the file is the one
        // that was registered during the uninstall hook.
        if (__FILE__ != WP_UNINSTALL_PLUGIN) {
            return;
        }

        delete_option(static::getSlug());
        delete_option(static::getSlug() . '_activated');

        static::onUninstall();

        wp_cache_flush();
    }

    public static function onUninstall()
    {
        return;
    }
}
