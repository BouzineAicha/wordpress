<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'boom' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '!<OuWxPT/W1kTQc4qL/KfM`37Ab`NMD,AG@7, ,Gv` !Gxi?xZb#38ybJ Q&vP4)' );
define( 'SECURE_AUTH_KEY',  ']+j (<u&EuP^Q5O3W!/?ajwA(%R, ~23jHU;JJVaYt4:~|:J.<NJQpf6AtR/.2aZ' );
define( 'LOGGED_IN_KEY',    '>Me]&I56tn.<d:]Hdm~_@MEyt[vo`cwuw*92ch^a,?mzD~oxo.kBqZFK9T^^V=fU' );
define( 'NONCE_KEY',        'U>Ipp)<6`n|]!TW!V}Q&l}@tn;nb3<fD_5H0`34NFic1U-3Rc,k/|acHlaX;;$e3' );
define( 'AUTH_SALT',        'yJP`Mfbj)niE{R<:+g/cTR%u)v:vnFLkN{Va+L]gZ<}HQ7U4BJ3a2KE@xGQ{w2!,' );
define( 'SECURE_AUTH_SALT', '1A!3PhDe)F/--^Y7R6P%GRC62B=wOP]?vEi6uHy.T +a{ JE{D)uHd3F26}fzfvJ' );
define( 'LOGGED_IN_SALT',   'u]@m]QGKMQxozoxle_H!`E(uwPXI9vm*WB3gTk9x6XhsO{U-3zNlFgz{=xAQD2sG' );
define( 'NONCE_SALT',       'u35_8T{T!(9twX~},@7$[TNX?0Zd-_c*5.pN1[Lof|E(<j5vVMp!!_-poGR_kiXv' );

/**#@-*/

/**
 * WordPress database table prefix.
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
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
