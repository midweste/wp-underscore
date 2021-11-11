<?php

namespace _;

defined('ABSPATH') || exit;

abstract class WordpressPluginFrameworkAdmin extends WordpressPluginFramework
{

    private $defaults = [];

    public function alpacaAdminForm(string $path)
    {

        $nonce = wp_create_nonce(static::getSlug());
        if (isset($_POST['wpnonce'])) {
            if (wp_verify_nonce($_POST['wpnonce'], static::getSlug()) && $this->save($_POST)) {
                echo $this->createNotice('Settings were saved', 'success');
            } else {
                echo $this->createNotice('There was a problem saving the settings', 'error');
            }
        }

        $definition = file_get_contents($path);
        if (empty($definition)) {
            return;
        }

        $this->enqueueExternalFile('alpaca-lodash', '//cdn.jsdelivr.net/npm/lodash@4.17.15/lodash.min.js');
        // Styles
        //$this->enqueueExternalFile('bootstrap-style', '//maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css');
        //$this->enqueueExternalFile('bootstrap-script', '//maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js', ['jquery']);

        // Alpaca
        $this->enqueueExternalFile('handlebars-script', '//cdnjs.cloudflare.com/ajax/libs/handlebars.js/4.0.5/handlebars.js');
        $this->enqueueExternalFile('basealpaca-style', '//cdn.jsdelivr.net/npm/alpaca@1.5.27/dist/alpaca/bootstrap/alpaca.min.css');
        $this->enqueueExternalFile('basealpaca-script', '//cdn.jsdelivr.net/npm/alpaca@1.5.27/dist/alpaca/bootstrap/alpaca.js', ['jquery']);

        $id = static::getSlug() . '-' .  hash('md5', $definition);
        $data = json_encode((object)$this->getData());
        $path = '/' . str_replace(ABSPATH, '', __DIR__); //_\path_relative(__DIR__);
        $templates = file_get_contents(__DIR__ . '/WordpressPluginFrameworkAdmin.html');

        $loader = <<<HTML

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

            {$templates}

            <script type="text/javascript">
                $ = (typeof $ == "undefined" && typeof jQuery !== "undefined") ? jQuery : $;
                console = (typeof console == "undefined") ? {} : console;
                console.log = (typeof console.log != "function") ? function () { } : console.log;

                $(document).ready(function($) {
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
                    //_.set(jsonSchema, 'options.type', "table")
                    // _.set(jsonSchema, 'view.templates', {
                    //     "control": "template"
                    // })
                    //_.set(jsonSchema, 'view.templates.message', "<span>{{{message}}}</span>");

                    // set default data
                    _.set(jsonSchema, 'data', $data);

                    // init
                    $('#{$id}').alpaca(jsonSchema);
                });
            </script>
        HTML;
        return $loader;
    }

    public function getData(): array
    {
        return array_replace_recursive((array) $this->getDefaults(), (array) $this->getOption());
    }

    public function getDefaults(): array
    {
        return $this->defaults;
    }

    public function setDefaults(array $defaults): self
    {
        $this->defaults = $defaults;
        return $this;
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
