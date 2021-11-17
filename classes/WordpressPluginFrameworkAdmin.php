<?php

namespace _;

defined('ABSPATH') || exit;

abstract class WordpressPluginFrameworkAdmin extends WordpressPluginFramework
{

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
