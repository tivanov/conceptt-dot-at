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
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpress' );

/** MySQL database username */
define( 'DB_USER', 'wordpress' );

/** MySQL database password */
define( 'DB_PASSWORD', 'ba646bd5bcdc6f7dfc5fc85a053896f672e67dde9178f5fe' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

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
define( 'AUTH_KEY',         'Mc,YdOE,LPM(Yqf|C]w]}m/.mS@sTd<SM@h_w9o]#FOYp4m57B/yy:i&,;Y7wH}b' );
define( 'SECURE_AUTH_KEY',  '~~b( 3KQ{r&}w|=M>FV9.+XW9uz Z2HGyK>}`Hw+e#8u$[|32k00yo[UOCNO2I%L' );
define( 'LOGGED_IN_KEY',    '$^0pkP%R9<HcldYBY;/_fP]s>|KDdknIbnGcqua`P?aaRA$7exT}FwT1tE|D6?)A' );
define( 'NONCE_KEY',        '7u6M*)^c&_Mx8bPyb2[(=iR}O$#Ngmu%=(SMb}cDn?SNoS]?KD(QXDp}ijxt1m%w' );
define( 'AUTH_SALT',        '{hrM[J=8e#%td6_~<NC<z6H-,P/ uhjQU[ n0$pGunW#*jw)%kicO#HO]uz&eBB[' );
define( 'SECURE_AUTH_SALT', 'IN1C.*2ksv.il)_xNxJ9l;n+o4p&.xMaG(ss+L+iwdbk*}_KG~<.Ov1{XIii*]s7' );
define( 'LOGGED_IN_SALT',   'c2m:RB#NTv ;S>O6~OD1&+BYa G<d5/9;dJF=RuTnu]Z(Ezl*YlgH{g$oeHKQ[+9' );
define( 'NONCE_SALT',       '20Cb Am1^ev>psyJ$05{jVF?1O~AtPS)<XVF/9W-R:^v,Xt_f=hfwJihi%k>?~Lo' );

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
