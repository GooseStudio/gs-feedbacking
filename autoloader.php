<?php
namespace GooseStudio\Feedbacking;

spl_autoload_register( __NAMESPACE__ . '\\autoload' );
/**
 * @param string $class_name The fully-qualified name of the class to load.
 */
function autoload( $class_name ) {
	// If the specified $class_name does not include our namespace, duck out.
	if ( 0 !== strpos( $class_name, 'GooseStudio\\Feedbacking\\' ) ) {
		return;
	}
	$local_class          = substr( $class_name, strlen( 'GooseStudio\\Feedbacking\\' ) );
	$local_class          = implode( DIRECTORY_SEPARATOR, explode( '\\', $local_class ) );
	$localized_class_path = __DIR__ . '/src/' . $local_class . '.php';
	if ( file_exists( $localized_class_path ) ) {
		include $localized_class_path;//phpcs:ignore
	}
}
