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
    protected static $pluginFile;
    protected static $pluginName;
    protected static $pluginSlug;

    // abstract public static function getFile(): string;
    // abstract public static function getName(): string;
    // abstract public static function getSlug(): string;

    public function __construct(string $name, string $slug, string $file)
    {
        static::$pluginName = $name;
        static::$pluginSlug = $slug;
        static::$pluginFile = $file;

        load_plugin_textdomain(static::getSlug(), false, dirname(plugin_basename(static::getFile())) . '/lang');

        if (is_admin()) {
            register_activation_hook(static::getFile(), [static::class, 'activate']);
            register_deactivation_hook(static::getFile(), [static::class, 'deactivate']);
            register_uninstall_hook(static::getFile(), [static::class, 'uninstall']);
        }
    }

    public function run()
    {
        $this->init();
        if (is_admin()) {
            $this->initAdmin();
        }
    }

    public static function getSlug()
    {
        return static::$pluginSlug;
    }

    public static function getName()
    {
        return static::$pluginName;
    }

    public static function getFile()
    {
        return static::$pluginFile;
    }


    /**
     * Runs when the plugin is initialized
     */
    protected function init()
    {
    }

    /**
     * Runs when the plugin is initialized on admin pages
     */
    protected function initAdmin()
    {
    }

    public function getOptions(): ?array
    {
        return get_option(static::getSlug(), null);
    }

    public function setOption(array $data): bool
    {
        $current = $this->getOptions();
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

    private function enqueueAdd($hook, string $handle, string $file_path, array $depends = [], bool $inline = false, bool $script = false): self
    {
        add_action($hook, function () use ($hook, $handle, $file_path, $depends) {
            if (\is_callable($hook) && !$hook()) {
                return;
            }
            \_\enqueue($handle, $file_path, $depends);
        });
        return $this;
    }

    protected function enqueue(string $handle, string $file_path, array $depends = []): self
    {
        return $this->enqueueAdd('wp_enqueue_scripts', $handle, $file_path, $depends);
    }

    protected function enqueueAdmin(string $handle, string $file_path, array $depends = []): self
    {
        return $this->enqueueAdd('wp_enqueue_scripts', $handle, $file_path, $depends);
    }

    protected function enqueueConditionally(string $handle, string $file_path, array $depends = [], callable $callback): self
    {
        return $this->enqueueAdd($callback, $handle, $file_path, $depends);
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
    }

    public function alpacaAdminForm(string $path, array $defaults = [])
    {
        // json schema definition
        $definition = file_get_contents($path);
        if (empty($definition)) {
            throw new \Exception(sprintf('Could not find json schema definition at %s', $path));
        }

        // nonce
        $nonce = wp_create_nonce(static::getSlug());
        if (isset($_POST['wpnonce'])) {
            if (wp_verify_nonce($_POST['wpnonce'], static::getSlug()) && $this->save($_POST)) {
                echo $this->createNotice('Settings were saved', 'success');
            } else {
                echo $this->createNotice('There was a problem saving the settings', 'error');
            }
        }

        // alpaca js and css
        enqueue('alpaca-lodash', '//cdn.jsdelivr.net/npm/lodash@4.17.15/lodash.min.js');
        enqueue('handlebars-script', '//cdnjs.cloudflare.com/ajax/libs/handlebars.js/4.0.5/handlebars.js');
        enqueue('basealpaca-style', '//cdn.jsdelivr.net/npm/alpaca@1.5.27/dist/alpaca/bootstrap/alpaca.min.css');
        enqueue('basealpaca-script', '//cdn.jsdelivr.net/npm/alpaca@1.5.27/dist/alpaca/bootstrap/alpaca.js', ['jquery']);

        // form setup
        $id = static::getSlug() . '-' .  hash('md5', $definition);
        $merged = array_replace_recursive((array) $defaults, (array) $this->getOptions());
        $data = json_encode((object) $merged);
        $path = '/' . str_replace(ABSPATH, '', __DIR__); //_\path_relative(__DIR__);
        $templates = file_get_contents(__DIR__ . '/WordpressPluginFrameworkAdmin.html');

        // html
        $loader = <<<HTML

            {$templates}

            <style>
                #wp-plugin-admin .alpaca-message {
                    color: var(--wc-red, '#a00');
                }
                #wp-plugin-admin .form-table td {
                    margin-bottom: 0;
                    padding-bottom: 0;
                }
                #wp-plugin-admin .form-table input[type="text"] {
                    width: 25em;
                }
                #wp-plugin-admin .alpaca-container-item:not(:first-child),
                #wp-plugin-admin .alpaca-control-buttons-container {
                    margin-top: 0px !important;
                }
                #wp-plugin-admin .alpaca-form-buttons-container {
                    text-align: left !important;
                }
            </style>

            <div id="wp-plugin-admin">
                <div id="{$id}" class="alpaca-form wrap"></div>
            </div>

            <script type="text/javascript">
                jQuery(document).ready(function() {
                    // json form definition
                    {$definition}

                    // setup nonce and submit button
                    _.set(jsonSchema, 'schema.properties.wpnonce', {
                        "required": true,
                        "type": "string",
                        "default": "{$nonce}"
                    });
                    _.set(jsonSchema, 'options.fields.wpnonce' , {
                        "type": "hidden"
                    });

                    // setup save for wp
                    _.set(jsonSchema, 'options.form.buttons.submit', {
                        "value": "Save Changes",
                        "styles": "btn btn-primary button button-primary"
                    });
                    _.set(jsonSchema, 'options.form.attributes', {
                        "action": "",
                        "method": "post"
                    });

                    // set view template
                    Alpaca.registerView({
                        "id": "wp-edit",
                        "parent": "web-edit",
                        "templates": {
                            "container": "#wp-edit-container",
                            //"container-object": "#wp-edit-object",
                            //"container-object-item": "#wp-edit-object-item",
                            "control": "#wp-edit-control"
                        }
                    });
                    _.set(jsonSchema, 'view.parent', "wp-edit");

                    // set default data
                    _.set(jsonSchema, 'data', $data);

                    // init
                    jQuery('#{$id}').alpaca(jsonSchema);
                });
            </script>
        HTML;
        return $loader;
    }

    protected function save(array $data): bool
    {
        unset($data['wpnonce']);
        $result = $this->setOption($data);
        $this->onSave($data);
        wp_cache_flush();
        return $result;
    }

    public function onSave(array $data)
    {
        return $data;
    }

    protected function createNotice(string $message, string $level = 'info')
    {
        $l = (in_array($level, ['error', 'warning', 'success', 'info'])) ? $level : 'info';
        $m = esc_html($message);
        $notice = <<<HTML
        <div class="notice notice-$l is-dismissible">
            <p>$m</p>
        </div>
        HTML;
        return $notice;
    }
}
