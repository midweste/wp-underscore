<?php


namespace _;

trait WordpressPluginTrait {


	abstract public static function pluginFile(): string;
	abstract public static function pluginName(): string;
	abstract public static function pluginSlug(): string;

	public function pluginRun() {
		\load_plugin_textdomain( static::pluginSlug(), false, dirname( \plugin_basename( static::pluginFile() ) ) . '/lang' );

		if ( \is_admin() ) {
			\register_activation_hook( static::pluginFile(), [ static::class, 'pluginActivate' ] );
			\register_deactivation_hook( static::pluginFile(), [ static::class, 'pluginDeactivate' ] );
			\register_uninstall_hook( static::pluginFile(), [ static::class, 'pluginUninstall' ] );
		}

		$this->pluginInit();
		if ( \is_admin() ) {
			$this->pluginInitAdmin();
		}
	}

	/**
	 * Runs when the plugin is initialized
	 */
	protected function pluginInit() {
	}

	/**
	 * Runs when the plugin is initialized on admin pages
	 */
	protected function pluginInitAdmin() {
	}

	public function pluginGetOptions(): array {
		$saved = \get_option( static::pluginSlug(), null );
		return ( is_null( $saved ) || ! is_array( $saved ) ) ? [] : $saved;
	}

	public function pluginGetOption( string $key, $default = null ) {
		$options = $this->pluginGetOptions();
		return ( isset( $options[ $key ] ) ) ? $options[ $key ] : $default;
	}

	public function pluginSetOption( array $data ): bool {
		$exists = option_exists( static::pluginSlug() );
		if ( ! $exists ) {
			return \add_option( static::pluginSlug(), $data, false );
		}

		$current = $this->pluginGetOptions();
		if ( $current === $data ) {
			// have to compare existing value to what is going to be saved
			// because wordpress is dumb and returns false if they are the same
			return true;
		}
		return \update_option( static::pluginSlug(), $data, false );
	}

	public function pluginAddShortcode( callable $function, string $shortcode = '' ): self {
		$sc = ( empty( $shortcode ) ) ? static::pluginSlug() : $shortcode;
		\add_shortcode($sc, function ( $atts, $content, $shortcode_tag ) use ( $function ) {
			$function( $atts, $content, $shortcode_tag );
		});
		return $this;
	}

	public function pluginAddAdminMenuPage( callable $callback, int $pos = 99, string $name = '', string $slug = '', string $perm = 'manage_options', string $icon = 'dashicons-schedule' ) {
		add_action('admin_menu', function () use ( $name, $slug, $callback, $perm, $icon, $pos ) {
			$n = ( empty( $name ) ) ? static::pluginName() : $name;
			$s = ( empty( $slug ) ) ? static::pluginSlug() : $slug;
			\add_menu_page(
				\__( $n, $s ),
				\__( $n, $s ),
				$perm,
				$s . '-admin',
				$callback,
				$icon,
				$pos
			);
		});
	}

	private function enqueueAdd( $hook, string $handle, string $file_path, array $depends = [], bool $inline = false, bool $script = false ): self {
		add_action($hook, function () use ( $hook, $handle, $file_path, $depends ) {
			if ( \is_callable( $hook ) && ! $hook() ) {
				return;
			}
			\_\enqueue( $handle, $file_path, $depends );
		});
		return $this;
	}

	protected function enqueue( string $handle, string $file_path, array $depends = [] ): self {
		return $this->enqueueAdd( 'wp_enqueue_scripts', $handle, $file_path, $depends );
	}

	protected function enqueueAdmin( string $handle, string $file_path, array $depends = [] ): self {
		return $this->enqueueAdd( 'wp_enqueue_scripts', $handle, $file_path, $depends );
	}

	protected function enqueueConditionally( string $handle, string $file_path, callable $callback, array $depends = [] ): self {
		return $this->enqueueAdd( $callback, $handle, $file_path, $depends );
	}


	/**
	 * https://wordpress.stackexchange.com/questions/25910/uninstall-activate-deactivate-a-plugin-typical-features-how-to/25979#25979
	 *
	 * @return void
	 */
	public static function pluginActivate() {
		if ( ! \current_user_can( 'activate_plugins' ) ) {
			return;
		}
		$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
		\check_admin_referer( "activate-plugin_{$plugin}" );

		\update_option( static::pluginSlug() . '_activated', 1, false );

		do_action( static::pluginSlug() . '_activated' );

		\wp_cache_flush();
	}

	public static function pluginOnActivate() {
	}

	/**
	 * https://wordpress.stackexchange.com/questions/25910/uninstall-activate-deactivate-a-plugin-typical-features-how-to/25979#25979
	 *
	 * @return void
	 */
	public static function pluginDeactivate() {
		if ( ! \current_user_can( 'activate_plugins' ) ) {
			return;
		}
		$plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
		\check_admin_referer( "deactivate-plugin_{$plugin}" );

		\update_option( static::pluginSlug() . '_activated', 0, false );

		do_action( static::pluginSlug() . '_deactivated' );

		\wp_cache_flush();
	}

	/**
	 * https://wordpress.stackexchange.com/questions/25910/uninstall-activate-deactivate-a-plugin-typical-features-how-to/25979#25979
	 *
	 * @return void
	 */
	public static function pluginUninstall() {
		if ( ! \current_user_can( 'activate_plugins' ) ) {
			return;
		}
		\check_admin_referer( 'bulk-plugins' );

		// Important: Check if the file is the one
		// that was registered during the uninstall hook.
		if ( __FILE__ != WP_UNINSTALL_PLUGIN ) {
			return;
		}

		\delete_option( static::pluginSlug() );
		\delete_option( static::pluginSlug() . '_activated' );

		do_action( static::pluginSlug() . '_uninstalled' );

		\wp_cache_flush();
	}

	public function pluginAdminForm( string $path, array $defaults = [] ) {
		// json schema definition
		$definition = file_get_contents( $path );
		if ( empty( $definition ) ) {
			throw new \Exception( sprintf( 'Could not find json schema definition at %s', $path ) );
		}

		// form actions
		if ( isset( $_POST['wpnonce'] ) && \wp_verify_nonce( $_POST['wpnonce'], static::pluginSlug() ) ) {
			if ( $_POST['action'] == 'save' ) {
				if ( $this->pluginAdminFormSave( $_POST ) ) {
					echo $this->pluginNotice( 'Settings were saved', 'success' );
				} else {
					echo $this->pluginNotice( 'There was a problem saving the settings', 'error' );
				}
			}
		}

		// alpaca js and css
		enqueue( 'alpaca-lodash', '//cdn.jsdelivr.net/npm/lodash@4.17.15/lodash.min.js' );
		enqueue( 'handlebars-script', '//cdnjs.cloudflare.com/ajax/libs/handlebars.js/4.0.5/handlebars.js' );
		enqueue( 'basealpaca-style', '//cdn.jsdelivr.net/npm/alpaca@1.5.27/dist/alpaca/bootstrap/alpaca.min.css' );
		enqueue( 'basealpaca-script', '//cdn.jsdelivr.net/npm/alpaca@1.5.27/dist/alpaca/bootstrap/alpaca.js', [ 'jquery' ] );
		enqueue( 'masked-input', '//cdnjs.cloudflare.com/ajax/libs/jquery.maskedinput/1.4.1/jquery.maskedinput.min.js', [ 'jquery' ] );

		// form setup
		$id         = static::pluginSlug() . '-' . hash( 'md5', $definition );
		$nonce      = \wp_create_nonce( static::pluginSlug() );
		$mergedData = array_replace_recursive( (array) $defaults, (array) $this->pluginGetOptions() );

		$jsonData  = json_encode( (object) $mergedData );
		$templates = file_get_contents( __DIR__ . '/WordpressPluginTraitAdmin.html' );

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

                    // set view template
                    Alpaca.registerView({
                        "id": "wp-edit",
                        "parent": "web-edit",
                        "templates": {
                            "form": "#wp-edit-form",
                            "container": "#wp-edit-container",
                            //"container-object": "#wp-edit-object",
                            //"container-object-item": "#wp-edit-object-item",
                            "control": "#wp-edit-control",
                            "control-checkbox": "#wp-edit-control-checkbox"
                        }
                    });
                    _.set(jsonSchema, 'view.parent', "wp-edit");

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
                        "type": "submit",
                        "styles": "btn btn-primary button button-primary",
                        "attributes": {
                            "name": "action",
                            "value": "save"
                        }
                    });

                    _.set(jsonSchema, 'options.form.attributes', {
                        "action": "",
                        "method": "post"
                    });

                    // set default data
                    _.set(jsonSchema, 'data', $jsonData);

                    // init
                    jQuery('#{$id}').alpaca(jsonSchema);
                });
            </script>
        HTML;
		return $loader;
	}

	protected function pluginAdminFormSave( array $data ): bool {
		// change string true false values to booleans
		$data = array_replace_values_recursive( $data, [ 'true', 'false' ], [ true, false ] );

		$data = \apply_filters( static::pluginSlug() . '_pre_save', $data );

		unset( $data['wpnonce'] );
		unset( $data['action'] );

		$result = $this->pluginSetOption( $data );

		\do_action( static::pluginSlug() . '_post_save', $data );

		\wp_cache_flush();
		return $result;
	}

	protected function pluginNotice( string $message, string $level = 'info' ) {
		$l      = ( in_array( $level, [ 'error', 'warning', 'success', 'info' ] ) ) ? $level : 'info';
		$m      = \esc_html( $message );
		$notice = <<<HTML
        <div class="notice notice-$l is-dismissible">
            <p>$m</p>
        </div>
        HTML;
		return $notice;
	}
}
