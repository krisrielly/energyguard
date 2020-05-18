<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'eg_wpdb' );

/** MySQL database username */
define( 'DB_USER', 'eg_wpuser' );

/** MySQL database password */
define( 'DB_PASSWORD', 'D2qa&2S*O3nuw63Ch' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */

define('AUTH_KEY',         '0~ S~q<e3EV<@nFOlUja1PN:|{K[t|#K?|hVE4:<uQ8[*9|d@@z0vm-+2rj[!2k^');
define('SECURE_AUTH_KEY',  'Wj#f$ZH-8s9g[3F-,hFbiBG7u?2+8+@,:]AlvsCrL{(WCBK^JtR?xXw1-oWI#s{n');
define('LOGGED_IN_KEY',    ';aE%+-chql4b^43x+0O2bB&N=kjDbzPq-Gn<)p:RzbOi9CjO-uZXB+>l][BN+R(1');
define('NONCE_KEY',        'uJ-!j aVb,eZT?A_?y(@[aLnMID6F+N1Ap`_|b[2XI&$%jP f6JEt9EMH9Mg`dsg');
define('AUTH_SALT',        ')np,4*}3qoy2>z.ZMOlP8jhd&ez]X,0-B4}=T8pj=Q0`:dSL<_OU aQ|c)|,6S|9');
define('SECURE_AUTH_SALT', '3bv_+XOv)|k.Hb{?S?keGpNYPB7@f}xz6?fDe57o}8FqCsntO{x;wj!u6]h!OgA+');
define('LOGGED_IN_SALT',   '/`ez`Sd.w)kOF3.&!rewj6KuRK1./#h?bWXe=_H=>^s|BB}bU1kAIORrU@>u#PsZ');
define('NONCE_SALT',       'WD!/;jO8C=LvxBS[ao|Rd#,0bJlQo|ua/@az*?.]h<@/Wt2|Hk;e;i[y~+|54e%5');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );
